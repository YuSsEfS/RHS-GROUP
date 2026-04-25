<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class CvExtractionService
{
    public function hashBinary(string $binary): string
    {
        return hash('sha256', $binary);
    }

    public function extractTextFromFile(string $filePath, ?string $extension = null): string
    {
        $extension = strtolower((string) ($extension ?: pathinfo($filePath, PATHINFO_EXTENSION)));

        try {
            if ($extension === 'pdf') {
                return trim((new PdfParser())->parseFile($filePath)->getText());
            }

            if (in_array($extension, ['doc', 'docx'], true)) {
                $phpWord = IOFactory::load($filePath);
                $text = '';

                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        $text .= $this->extractPhpWordElementText($element);
                    }
                }

                return trim($text);
            }

            if ($extension === 'txt') {
                return trim((string) file_get_contents($filePath));
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    public function extractTextFromBinary(string $binary, string $extension): string
    {
        $tempPath = 'temp/cv-extraction/' . uniqid('cv_', true) . '.' . strtolower($extension);

        Storage::disk('local')->put($tempPath, $binary);

        try {
            return $this->extractTextFromFile(Storage::disk('local')->path($tempPath), $extension);
        } finally {
            Storage::disk('local')->delete($tempPath);
        }
    }

    public function guessMimeTypeFromExtension(string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    private function extractPhpWordElementText($element): string
    {
        $text = '';

        if (method_exists($element, 'getText')) {
            $value = $element->getText();

            if (is_string($value)) {
                $text .= $value . "\n";
            }
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $text .= $this->extractPhpWordElementText($child);
            }
        }

        if (method_exists($element, 'getRows')) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $child) {
                        $text .= $this->extractPhpWordElementText($child);
                    }
                }
            }
        }

        return $text;
    }
}
