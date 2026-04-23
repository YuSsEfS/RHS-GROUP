<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\JobApplication;
use App\Models\Cv;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class ImportOldJobApplicationCvs extends Command
{
    protected $signature = 'cvs:import-old';
    protected $description = 'Import old CVs from job_applications into cvs table';

    public function handle(): int
    {
        $applications = JobApplication::query()
            ->whereNotNull('cv_path')
            ->get();

        $this->info('Applications with cv_path: ' . $applications->count());

        $imported = 0;
        $skipped = 0;
        $missing = 0;
        $failed = 0;

        foreach ($applications as $application) {
            $relativePath = ltrim($application->cv_path, '/');

            if (!Storage::disk('public')->exists($relativePath)) {
                $missing++;
                $this->warn("Missing file for application #{$application->id}: {$relativePath}");
                continue;
            }

            try {
                $binary = Storage::disk('public')->get($relativePath);
                $hash = hash('sha256', $binary);

                if (Cv::where('file_hash', $hash)->exists()) {
                    $skipped++;
                    continue;
                }

                $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
                $tempPath = storage_path('app/temp_' . uniqid() . '.' . $extension);

                file_put_contents($tempPath, $binary);

                $text = $this->safeExtractTextFromFile($tempPath, $extension);

                @unlink($tempPath);

                $text = $this->cleanUtf8($text);

                $profile = [
                    'full_name' => $this->cleanUtf8($application->full_name ?? null),
                    'email' => $this->cleanUtf8($application->email ?? null),
                    'phone' => $this->cleanUtf8($application->phone ?? null),
                    'title' => $this->cleanUtf8($application->position ?? null),
                    'years_experience' => null,
                    'education' => null,
                    'languages' => [],
                    'technical_skills' => [],
                    'soft_skills' => [],
                    'industries' => [],
                    'certifications' => [],
                    'summary' => $text ? mb_substr($text, 0, 1500) : null,
                ];

                $storedPath = 'private/cvs/' . uniqid() . '.' . $extension;
                Storage::disk('local')->put($storedPath, $binary);

                Cv::create([
                    'candidate_name' => $this->cleanUtf8($application->full_name ?? null),
                    'email' => $this->cleanUtf8($application->email ?? null),
                    'phone' => $this->cleanUtf8($application->phone ?? null),
                    'original_filename' => basename($relativePath),
                    'mime_type' => $this->guessMimeTypeFromExtension($extension),
                    'file_size' => strlen($binary),
                    'encrypted_path' => $storedPath,
                    'encrypted_extracted_text' => $text,
                    'structured_profile' => $profile,
                    'file_hash' => $hash,
                    'uploaded_at' => now(),
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Failed application #{$application->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Imported: {$imported}");
        $this->line("Skipped duplicates: {$skipped}");
        $this->line("Missing files: {$missing}");
        $this->line("Failed: {$failed}");

        return self::SUCCESS;
    }

    private function safeExtractTextFromFile(string $filePath, string $extension): string
    {
        try {
            return $this->extractTextFromFile($filePath, $extension);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractTextFromFile(string $filePath, string $extension): string
    {
        if ($extension === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return trim($pdf->getText());
        }

        if (in_array($extension, ['doc', 'docx'])) {
            $phpWord = IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }

            return trim($text);
        }

        if ($extension === 'txt') {
            return trim(file_get_contents($filePath));
        }

        return '';
    }

    private function cleanUtf8($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        $value = preg_replace('/[^\P{C}\n\r\t]/u', '', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    private function guessMimeTypeFromExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}