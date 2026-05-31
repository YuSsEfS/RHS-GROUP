<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Smalot\PdfParser\Parser as PdfParser;

class ExtractPdfTextCommand extends Command
{
    protected $signature = 'cvs:extract-pdf-text {input} {output}';

    protected $description = 'Extract PDF text into a file from an isolated process.';

    public function handle(): int
    {
        $input = (string) $this->argument('input');
        $output = (string) $this->argument('output');

        if (!is_file($input) || !is_readable($input)) {
            return self::FAILURE;
        }

        $directory = dirname($output);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return self::FAILURE;
        }

        $maxBytes = (int) config('external_cv_parser.pdf_max_parse_size_bytes', 25 * 1024 * 1024);

        if ($maxBytes > 0 && filesize($input) > $maxBytes) {
            file_put_contents($output, '');

            return self::FAILURE;
        }

        @ini_set('memory_limit', (string) config('external_cv_parser.pdf_extraction_memory_limit', '512M'));

        try {
            $text = trim((new PdfParser())->parseFile($input)->getText());
        } catch (\Throwable $e) {
            file_put_contents($output, '');

            return self::FAILURE;
        }

        file_put_contents($output, $text);

        return self::SUCCESS;
    }
}
