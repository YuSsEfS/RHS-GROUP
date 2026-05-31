<?php

namespace App\Console\Commands;

use App\Models\JobApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncJobApplicationReadStatusFromSql extends Command
{
    protected $signature = 'applications:sync-read-status
        {file : Chemin du dump SQL contenant job_applications}
        {--dry-run : Analyse sans ecrire en base}';

    protected $description = 'Realigne job_applications.is_read sur les valeurs presentes dans un dump SQL historique';

    public function handle(): int
    {
        $path = $this->resolveFilePath((string) $this->argument('file'));

        if ($path === null || !is_file($path) || !is_readable($path)) {
            $this->error('Fichier SQL introuvable ou illisible.');

            return self::FAILURE;
        }

        [$statuses, $parsedRows] = $this->extractStatusesFromDump($path);

        if ($parsedRows === 0 || empty($statuses)) {
            $this->warn('Aucune ligne job_applications exploitable n a ete trouvee dans le dump.');

            return self::FAILURE;
        }

        $currentStatuses = JobApplication::query()
            ->whereIn('id', array_keys($statuses))
            ->pluck('is_read', 'id')
            ->map(fn ($value) => (int) (bool) $value)
            ->all();

        $changes = [];
        $unchanged = 0;
        $missing = 0;

        foreach ($statuses as $id => $status) {
            if (!array_key_exists($id, $currentStatuses)) {
                $missing++;
                continue;
            }

            if ((int) $currentStatuses[$id] === (int) $status) {
                $unchanged++;
                continue;
            }

            $changes[$id] = (int) $status;
        }

        $this->info("Lignes lues dans le dump: {$parsedRows}");
        $this->line('IDs distincts trouves: ' . count($statuses));
        $this->line('Applications deja conformes: ' . $unchanged);
        $this->line('Applications absentes localement: ' . $missing);
        $this->line('Applications a corriger: ' . count($changes));

        if ($this->option('dry-run')) {
            $this->comment('Mode dry-run: aucune mise a jour n a ete appliquee.');

            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(function () use ($changes, &$updated) {
            foreach (array_chunk($changes, 200, true) as $chunk) {
                foreach ($chunk as $id => $status) {
                    $updated += DB::table('job_applications')
                        ->where('id', $id)
                        ->update([
                            'is_read' => $status,
                        ]);
                }
            }
        });

        $this->info("Applications mises a jour: {$updated}");

        return self::SUCCESS;
    }

    private function resolveFilePath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $relativePath = base_path($path);

        return is_file($relativePath) ? $relativePath : null;
    }

    /**
     * @return array{0: array<int, int>, 1: int}
     */
    private function extractStatusesFromDump(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [[], 0];
        }

        $collecting = false;
        $buffer = '';
        $statuses = [];
        $parsedRows = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                if (!$collecting) {
                    if (str_contains($line, 'INSERT INTO `job_applications`')) {
                        $collecting = true;
                        $valuesPosition = strpos($line, 'VALUES');
                        $buffer = $valuesPosition === false
                            ? ''
                            : substr($line, $valuesPosition + strlen('VALUES'));

                        if (str_contains($line, ';')) {
                            $this->parseInsertBuffer($buffer, $statuses, $parsedRows);
                            $buffer = '';
                            $collecting = false;
                        }
                    }

                    continue;
                }

                $buffer .= $line;

                if (str_contains($line, ';')) {
                    $this->parseInsertBuffer($buffer, $statuses, $parsedRows);
                    $buffer = '';
                    $collecting = false;
                }
            }
        } finally {
            fclose($handle);
        }

        return [$statuses, $parsedRows];
    }

    /**
     * @param  array<int, int>  $statuses
     */
    private function parseInsertBuffer(string $buffer, array &$statuses, int &$parsedRows): void
    {
        foreach ($this->extractTuples($buffer) as $tuple) {
            $fields = $this->parseTuple($tuple);

            if (count($fields) < 12) {
                continue;
            }

            $id = $this->parseIntegerField($fields[0]);
            $isRead = $this->parseIntegerField($fields[11]);

            if ($id === null || $isRead === null) {
                continue;
            }

            $statuses[$id] = $isRead === 1 ? 1 : 0;
            $parsedRows++;
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractTuples(string $buffer): array
    {
        $tuples = [];
        $length = strlen($buffer);
        $depth = 0;
        $inString = false;
        $escaped = false;
        $start = null;

        for ($index = 0; $index < $length; $index++) {
            $char = $buffer[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'") {
                $inString = true;
                continue;
            }

            if ($char === '(') {
                if ($depth === 0) {
                    $start = $index;
                }

                $depth++;
                continue;
            }

            if ($char === ')') {
                if ($depth === 0) {
                    continue;
                }

                $depth--;

                if ($depth === 0 && $start !== null) {
                    $tuples[] = substr($buffer, $start, $index - $start + 1);
                    $start = null;
                }
            }
        }

        return $tuples;
    }

    /**
     * @return array<int, string>
     */
    private function parseTuple(string $tuple): array
    {
        $tuple = trim($tuple);

        if (str_starts_with($tuple, '(') && str_ends_with($tuple, ')')) {
            $tuple = substr($tuple, 1, -1);
        }

        $fields = [];
        $buffer = '';
        $length = strlen($tuple);
        $inString = false;
        $escaped = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $tuple[$index];

            if ($inString) {
                $buffer .= $char;

                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'") {
                $inString = true;
                $buffer .= $char;
                continue;
            }

            if ($char === ',') {
                $fields[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $fields[] = trim($buffer);

        return $fields;
    }

    private function parseIntegerField(string $value): ?int
    {
        $value = trim($value);

        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
