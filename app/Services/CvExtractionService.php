<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\Process\Process;

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
                return $this->extractPdfTextInIsolatedProcess($filePath);
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

    private function extractPdfTextInIsolatedProcess(string $filePath): string
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return '';
        }

        $outputPath = Storage::disk('local')->path('temp/cv-extraction/' . uniqid('pdf_text_', true) . '.txt');
        $memoryLimit = (string) config('external_cv_parser.pdf_extraction_memory_limit', '384M');
        $timeout = (int) config('external_cv_parser.pdf_extraction_timeout', 90);

        $process = new Process([
            PHP_BINARY,
            '-d',
            'memory_limit=' . $memoryLimit,
            base_path('artisan'),
            'cvs:extract-pdf-text',
            $filePath,
            $outputPath,
        ], base_path());

        $process->setTimeout(max(30, $timeout));

        try {
            $process->run();

            if (!$process->isSuccessful() || !is_file($outputPath)) {
                return '';
            }

            return trim((string) file_get_contents($outputPath));
        } catch (\Throwable $e) {
            return '';
        } finally {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
        }
    }
}
