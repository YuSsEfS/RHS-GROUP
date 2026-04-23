<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\ExternalCv;
use App\Models\ExternalCvBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ExternalCvIndexingService
{
    protected array $cities = [];
    protected array $cityAliases = [];
    protected array $titles = [];
    protected array $titleKeywords = [];
    protected array $titleBlockers = [];
    protected array $nameBlockers = [];
    protected array $nameSectionBlockers = [];
    protected array $companyWords = [];
    protected array $filenameNoise = [];
    protected array $firstNames = [];
    protected array $lastNames = [];
    protected array $normalizedFirstNames = [];
    protected array $normalizedLastNames = [];
    protected array $badTitleSentences = [];

    public function __construct()
    {
        $config = config('external_cv_parser', []);

        $this->cities = $config['cities'] ?? [];
        $this->cityAliases = $config['city_aliases'] ?? [];

        $this->titles = array_values(array_unique(array_merge(
            $config['titles'] ?? [],
            $this->fallbackTitles()
        )));

        $this->titleKeywords = array_values(array_unique(array_merge(
            $config['title_keywords'] ?? [],
            $this->fallbackTitleKeywords()
        )));

        $this->titleBlockers = array_values(array_unique(array_merge(
            $config['title_blockers'] ?? [],
            $this->fallbackTitleBlockers()
        )));

        $this->nameBlockers = array_values(array_unique(array_merge(
            $config['name_blockers'] ?? [],
            $this->fallbackNameBlockers()
        )));

        $this->nameSectionBlockers = array_values(array_unique(array_merge(
            $config['name_section_blockers'] ?? [],
            $this->fallbackNameSectionBlockers()
        )));

        $this->companyWords = array_values(array_unique(array_merge(
            $config['company_words'] ?? [],
            $this->fallbackCompanyWords()
        )));

        $this->filenameNoise = array_values(array_unique(array_merge(
            $config['filename_noise'] ?? [],
            $this->fallbackFilenameNoise()
        )));

        $this->firstNames = array_values(array_unique(array_merge(
            $config['first_names'] ?? [],
            $this->fallbackFirstNames()
        )));

        $this->lastNames = array_values(array_unique(array_merge(
            $config['last_names'] ?? [],
            $this->fallbackLastNames()
        )));

        $this->normalizedFirstNames = array_values(array_unique(array_filter(array_map(
            fn ($v) => $this->normalizeText($v),
            $this->firstNames
        ))));

        $this->normalizedLastNames = array_values(array_unique(array_filter(array_map(
            fn ($v) => $this->normalizeText($v),
            $this->lastNames
        ))));

        $this->badTitleSentences = [
            'jai precedemment travaille',
            'j ai precedemment travaille',
            'jai travaille',
            'j ai travaille',
            'jai travaillé',
            'j ai travaillé',
            'je travaille',
            'je suis',
            'je souhaite',
            'je recherche',
            'a la recherche',
            'passionne',
            'passionné',
            'mission',
            'missions',
            'taches',
            'tâches',
            'responsabilites',
            'responsabilités',
            'experience professionnelle',
            'formation',
            'formations',
            'competences',
            'compétences',
            'langues',
            'loisirs',
            'profil personnel',
            'objectif',
            'curriculum vitae',
            'telephone',
            'email',
            'adresse',
            'né le',
            'nee le',
            'date de naissance',
        ];
    }

    public function indexBatch(ExternalCvBatch $batch, bool $force = false): void
    {
        if ($force) {
            ExternalCv::query()
                ->where('batch_id', $batch->id)
                ->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'indexed_at' => null,
                ]);
        }

        $batch->update([
            'status' => 'processing',
            'indexed_files' => 0,
            'failed_files' => 0,
        ]);

        foreach (
            ExternalCv::query()
                ->where('batch_id', $batch->id)
                ->whereIn('status', ['pending', 'failed'])
                ->cursor() as $externalCv
        ) {
            try {
                $this->indexOne($externalCv);
            } catch (\Throwable $e) {
                $externalCv->update([
                    'status' => 'failed',
                    'error_message' => $this->safeDbText($e->getMessage(), 1000),
                    'indexed_at' => now(),
                ]);
            }
        }

        $this->refreshBatchStats($batch);
    }

    public function reindexBatch(ExternalCvBatch $batch): void
    {
        $this->indexBatch($batch, true);
    }

    public function refreshBatchStats(ExternalCvBatch $batch): void
    {
        $indexed = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'indexed')
            ->count();

        $failed = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'failed')
            ->count();

        $pending = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'pending')
            ->count();

        $status = 'completed';

        if ($indexed === 0 && $failed > 0 && $pending === 0) {
            $status = 'failed';
        } elseif ($pending > 0) {
            $status = 'processing';
        }

        $batch->update([
            'status' => $status,
            'indexed_files' => $indexed,
            'failed_files' => $failed,
        ]);
    }

    public function indexOne(ExternalCv $externalCv): void
    {
        if (empty($externalCv->stored_path) || !Storage::disk('local')->exists($externalCv->stored_path)) {
            throw new \RuntimeException('Fichier externe introuvable.');
        }

        $externalCv->loadMissing('batch');

        $fullPath = Storage::disk('local')->path($externalCv->stored_path);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $hash = @hash_file('sha256', $fullPath) ?: null;

        $rawText = $this->extractTextFromFile($fullPath, $extension);
        $text = $this->safeDbText($this->normalizeLinesForParsing($rawText), 60000);

        if (trim((string) $text) === '') {
            throw new \RuntimeException('Impossible d’extraire le texte du CV. PDF scanné ou fichier illisible.');
        }

        $normalizedText = $this->normalizeText($text);

        $resolvedEmail = $this->extractEmail($text);
        $resolvedPhone = $this->extractPhone($text);
        $resolvedCity = $this->extractCity($normalizedText);
        $resolvedTitle = $this->extractTitle($text, $normalizedText, $externalCv->original_filename);
        $resolvedName = $this->extractName(
            $text,
            $resolvedEmail,
            $resolvedPhone,
            $resolvedTitle,
            $resolvedCity,
            $externalCv->original_filename
        );

        $profile = $this->safeProfile([
            'full_name' => $resolvedName,
            'email' => $resolvedEmail,
            'phone' => $resolvedPhone,
            'title' => $resolvedTitle,
            'city' => $resolvedCity,
            'summary' => $this->safeDbText(mb_substr($text, 0, 2500), 2500),
        ]);

        DB::transaction(function () use (
            $externalCv,
            $hash,
            $text,
            $profile,
            $resolvedName,
            $resolvedEmail,
            $resolvedPhone,
            $resolvedCity,
            $resolvedTitle
        ) {
            $externalCv->update([
                'candidate_name' => $this->safeDbText($resolvedName, 255),
                'email' => $this->safeDbText($resolvedEmail, 255),
                'phone' => $this->safeDbText($resolvedPhone, 50),
                'city' => $this->safeDbText($resolvedCity, 255),
                'current_title' => $this->safeDbText($resolvedTitle, 255),
                'file_hash' => $hash,
                'extracted_text' => $text,
                'structured_profile' => $profile,
            ]);

            $existingCv = $externalCv->cv_id ? Cv::find($externalCv->cv_id) : null;

            if (!$existingCv && $hash && Schema::hasColumn('cvs', 'file_hash')) {
                $existingCv = Cv::query()
                    ->where('file_hash', $hash)
                    ->first();
            }

            $cvData = [
                'candidate_name' => $this->safeDbText($resolvedName, 255),
                'email' => $this->safeDbText($resolvedEmail, 255),
                'phone' => $this->safeDbText($resolvedPhone, 50),
                'original_filename' => $this->safeDbText($externalCv->original_filename, 255),
                'mime_type' => $this->safeDbText($externalCv->mime_type, 255),
                'file_size' => $externalCv->file_size,
                'encrypted_path' => $externalCv->stored_path,
                'encrypted_extracted_text' => $text,
                'structured_profile' => $profile,
                'uploaded_at' => now(),
            ];

            if (Schema::hasColumn('cvs', 'file_hash')) {
                $cvData['file_hash'] = $hash;
            }

            if (Schema::hasColumn('cvs', 'source_type')) {
                $cvData['source_type'] = 'external_db';
            }

            if (Schema::hasColumn('cvs', 'source_id')) {
                $cvData['source_id'] = $externalCv->id;
            }

            if (Schema::hasColumn('cvs', 'city')) {
                $cvData['city'] = $this->safeDbText($resolvedCity, 255);
            }

            if (Schema::hasColumn('cvs', 'current_title')) {
                $cvData['current_title'] = $this->safeDbText($resolvedTitle, 255);
            }

            if (Schema::hasColumn('cvs', 'cv_folder_id')) {
                $cvData['cv_folder_id'] = $externalCv->batch?->cv_folder_id;
            }

            if (Schema::hasColumn('cvs', 'is_active')) {
                $cvData['is_active'] = true;
            }

            $cv = $existingCv
                ? tap($existingCv)->update($cvData)
                : Cv::create($cvData);

            $externalCv->update([
                'cv_id' => $cv->id,
                'status' => 'indexed',
                'error_message' => null,
                'indexed_at' => now(),
            ]);
        });
    }

    private function extractTextFromFile(string $filePath, string $extension): string
    {
        try {
            if ($extension === 'pdf') {
                $text = (new PdfParser())->parseFile($filePath)->getText();

                return $this->safeDbText($text, 60000) ?? '';
            }

            if (in_array($extension, ['doc', 'docx'], true)) {
                $phpWord = IOFactory::load($filePath);
                $text = '';

                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        $text .= $this->extractPhpWordElementText($element);
                    }
                }

                return $this->safeDbText($text, 60000) ?? '';
            }

            if ($extension === 'txt') {
                return $this->safeDbText((string) file_get_contents($filePath), 60000) ?? '';
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
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

    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            return strtolower(trim($m[0]));
        }

        return null;
    }

    private function extractPhone(string $text): ?string
    {
        $patterns = [
            '/(?:\+212|00212)\s*[5-7](?:[\s.\-]?[0-9]{2}){4}/',
            '/\b0\s*[5-7](?:[\s.\-]?[0-9]{2}){4}\b/',
            '/\b[5-7](?:[\s.\-]?[0-9]{2}){4}\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return preg_replace('/\s+/', '', trim($m[0]));
            }
        }

        return null;
    }

    private function extractCity(string $normalizedText): ?string
    {
        $best = null;
        $bestLength = 0;

        foreach ($this->cityAliases as $alias => $city) {
            $aliasNorm = $this->normalizeText($alias);

            if ($aliasNorm !== '' && preg_match('/(^|\s)' . preg_quote($aliasNorm, '/') . '(\s|$)/u', $normalizedText)) {
                $len = mb_strlen($aliasNorm);

                if ($len > $bestLength) {
                    $best = $city;
                    $bestLength = $len;
                }
            }
        }

        if ($best) {
            return $this->beautifyText($best);
        }

        foreach ($this->cities as $city) {
            $cityNorm = $this->normalizeText($city);

            if ($cityNorm !== '' && preg_match('/(^|\s)' . preg_quote($cityNorm, '/') . '(\s|$)/u', $normalizedText)) {
                return $this->beautifyText($city);
            }
        }

        return null;
    }

    private function extractTitle(string $text, string $normalizedText, string $filename): ?string
    {
        $candidates = [];
        $lines = array_slice($this->importantLines($text), 0, 180);

        foreach ($this->titleRuleMap() as $needles => $title) {
            foreach ((array) $needles as $needle) {
                $n = $this->normalizeText($needle);

                if ($n !== '' && preg_match('/(^|\s)' . preg_quote($n, '/') . '(\s|$)/u', $normalizedText)) {
                    $candidates[] = [
                        'title' => $title,
                        'score' => 200 + mb_strlen($n),
                        'line' => 999,
                    ];
                }
            }
        }

        foreach ($lines as $i => $line) {
            $clean = $this->cleanTitleLine($line);
            $norm = $this->normalizeText($clean);

            if (!$this->isPossibleTitleLine($clean, $norm)) {
                continue;
            }

            $title = $this->normalizeTitle($clean);

            if (!$title) {
                continue;
            }

            $score = $this->scoreTitleCandidate($clean, $norm, $i);

            if ($score >= 35) {
                $candidates[] = [
                    'title' => $title,
                    'score' => $score,
                    'line' => $i,
                ];
            }
        }

        $filenameTitle = $this->extractTitleFromFilename($filename);

        if ($filenameTitle) {
            $candidates[] = [
                'title' => $filenameTitle,
                'score' => 40,
                'line' => 1000,
            ];
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $a['line'] <=> $b['line'];
            }

            return $b['score'] <=> $a['score'];
        });

        return $candidates[0]['title'] ?? null;
    }

    private function cleanTitleLine(string $line): string
    {
        $line = preg_replace('/\b(poste|fonction|titre|emploi|métier|metier|profession|objective|objectif|profil recherché|profil recherche)\b\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/[|•●■◆►▶]+/u', ' ', $line);
        $line = preg_replace('/\s+/', ' ', $line);

        return trim($line, " \t\n\r\0\x0B:-");
    }

    private function isPossibleTitleLine(string $clean, string $norm): bool
    {
        if ($clean === '' || mb_strlen($clean) < 4 || mb_strlen($clean) > 100) {
            return false;
        }

        if (preg_match('/@|https?:|www\.|\+212|00212|\b0[5-7]/i', $clean)) {
            return false;
        }

        if ($this->containsAny($norm, $this->badTitleSentences)) {
            return false;
        }

        if ($this->containsAny($norm, $this->nameBlockers) && !$this->containsAny($norm, $this->titleKeywords)) {
            return false;
        }

        if ($this->containsAny($norm, $this->titleBlockers) && !$this->containsAny($norm, $this->titleKeywords)) {
            return false;
        }

        if ($this->looksLikeSchoolOrCompany($norm)) {
            return false;
        }

        if ($this->looksLikeNameOnly($clean)) {
            return false;
        }

        if ($this->looksLikeLongExperienceSentence($norm)) {
            return false;
        }

        return $this->containsAny($norm, $this->titleKeywords);
    }

    private function scoreTitleCandidate(string $clean, string $norm, int $line): int
    {
        $score = 0;

        if ($line <= 8) {
            $score += 25;
        } elseif ($line <= 25) {
            $score += 12;
        }

        foreach ($this->titleKeywords as $kw) {
            $kwNorm = $this->normalizeText($kw);

            if ($kwNorm !== '' && preg_match('/(^|\s)' . preg_quote($kwNorm, '/') . '(\s|$)/u', $norm)) {
                $score += 8;
            }
        }

        foreach ($this->titles as $known) {
            similar_text($norm, $this->normalizeText($known), $pct);

            if ($pct >= 78) {
                $score += 65;
            }
        }

        if (preg_match('/\b(technicien|technicienne|agent|assistant|assistante|responsable|chef|monteur|controleur|contrôleur|declarant|déclarant|developpeur|développeur|comptable|commercial|magasinier|cariste|mecanicien|mécanicien|electricien|électricien|qualiticien)\b/ui', $clean)) {
            $score += 30;
        }

        if (preg_match('/\b(de|en|des|du|d\’|d\')\b/ui', $clean)) {
            $score += 10;
        }

        if (mb_strlen($clean) > 65) {
            $score -= 18;
        }

        return $score;
    }

    private function normalizeTitle(string $title): ?string
    {
        $n = $this->normalizeText($title);

        foreach ($this->titleRuleMap() as $needles => $value) {
            foreach ((array) $needles as $needle) {
                $needleNorm = $this->normalizeText($needle);

                if ($needleNorm !== '' && preg_match('/(^|\s)' . preg_quote($needleNorm, '/') . '(\s|$)/u', $n)) {
                    return $value;
                }
            }
        }

        foreach ($this->titles as $known) {
            $knownNorm = $this->normalizeText($known);

            if ($knownNorm !== '' && str_contains($n, $knownNorm)) {
                return $this->beautifyText($known);
            }
        }

        return $this->beautifyText($title);
    }

    private function titleRuleMap(): array
    {
        return [
            'declarant en douane' => 'Déclarant en Douane',
            'déclarant en douane' => 'Déclarant en Douane',
            'aide declarant' => 'Aide Déclarant en Douane',
            'aide déclarant' => 'Aide Déclarant en Douane',
            'responsable douane' => 'Responsable Douane',
            'responsable en douane' => 'Responsable Douane',
            'agent de transit' => 'Agent de Transit',
            'transitaire' => 'Transitaire',
            'import export' => 'Agent Import Export',
            'dedouanement' => 'Agent de Dédouanement',
            'dédouanement' => 'Agent de Dédouanement',

            'monteur cableur' => 'Monteur Câbleur',
            'monteur câbleur' => 'Monteur Câbleur',
            'cablage des armoires' => 'Monteur Câbleur Armoires Électriques',
            'câblage des armoires' => 'Monteur Câbleur Armoires Électriques',
            'armoires electriques' => 'Monteur Câbleur Armoires Électriques',
            'armoires électriques' => 'Monteur Câbleur Armoires Électriques',

            'technicien maintenance' => 'Technicien de Maintenance',
            'maintenance industrielle' => 'Technicien Maintenance Industrielle',
            'electricite industrielle' => 'Technicien Électricité Industrielle',
            'électricité industrielle' => 'Technicien Électricité Industrielle',
            'technicien electricite' => 'Technicien Électricité',
            'technicien électricité' => 'Technicien Électricité',
            'electromecanique' => 'Technicien Électromécanique',
            'électromécanique' => 'Technicien Électromécanique',
            'mecanicien industriel' => 'Mécanicien Industriel',
            'mécanicien industriel' => 'Mécanicien Industriel',

            'controle qualite' => 'Contrôleur Qualité',
            'contrôle qualité' => 'Contrôleur Qualité',
            'technicien qualite' => 'Technicien Qualité',
            'technicien qualité' => 'Technicien Qualité',
            'responsable qualite' => 'Responsable Qualité',
            'responsable qualité' => 'Responsable Qualité',
            'assistant qualite' => 'Assistant Qualité',
            'assistant qualité' => 'Assistant Qualité',
            'iso 9001' => 'Assistant Qualité ISO 9001',
            'hse' => 'Technicien HSE',

            'magasinier' => 'Magasinier',
            'cariste' => 'Cariste',
            'preparateur de commande' => 'Préparateur de Commande',
            'préparateur de commande' => 'Préparateur de Commande',
            'responsable logistique' => 'Responsable Logistique',
            'assistant logistique' => 'Assistant Logistique',
            'agent logistique' => 'Agent Logistique',

            'technicien informatique' => 'Technicien Informatique',
            'support informatique' => 'Technicien Support Informatique',
            'developpeur web' => 'Développeur Web',
            'développeur web' => 'Développeur Web',
            'full stack' => 'Développeur Full Stack',

            'assistant administratif' => 'Assistant Administratif',
            'assistante administrative' => 'Assistante Administrative',
            'comptable' => 'Comptable',
            'commercial terrain' => 'Commercial Terrain',
            'commercial' => 'Commercial',
        ];
    }

    private function extractName(
        string $text,
        ?string $email,
        ?string $phone,
        ?string $title,
        ?string $city,
        string $filename
    ): ?string {
        $emailParts = $email ? $this->emailNameParts($email) : [];
        $lines = array_slice($this->importantLines($text), 0, 180);
        $candidates = [];

        foreach ($lines as $index => $line) {
            $clean = $this->cleanNameLine($line);
            $norm = $this->normalizeText($clean);

            if (!$this->isPossibleNameLine($clean, $norm, $email, $phone, $title, $city, $emailParts)) {
                continue;
            }

            $completed = $this->completeNameWithEmail($clean, $emailParts);
            $completedNorm = $this->normalizeText($completed);

            $score = $this->scoreNameCandidate($completed, $completedNorm, $index, $lines, $email, $phone, $emailParts);

            if ($score > 0) {
                $candidates[] = [
                    'name' => $this->beautifyName($completed),
                    'score' => $score,
                    'line' => $index,
                ];
            }
        }

        $emailGuess = $email ? $this->extractNameFromEmail($email) : null;

        if ($emailGuess) {
            $candidates[] = [
                'name' => $emailGuess,
                'score' => 140,
                'line' => 998,
            ];
        }

        $filenameGuess = $this->extractNameFromFilename($filename, $emailParts);

        if ($filenameGuess) {
            $candidates[] = [
                'name' => $filenameGuess,
                'score' => 85,
                'line' => 999,
            ];
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $a['line'] <=> $b['line'];
            }

            return $b['score'] <=> $a['score'];
        });

        return $candidates[0]['name'] ?? null;
    }

    private function emailNameParts(string $email): array
    {
        $local = strtolower(explode('@', $email)[0]);

        $local = preg_replace('/[0-9]+/', ' ', $local);
        $local = preg_replace('/[^a-zA-ZÀ-ÿ]+/u', ' ', $local);

        $noise = [
            'cv',
            'cvs',
            'resume',
            'profil',
            'profile',
            'pro',
            'officiel',
            'official',
            'job',
            'jobs',
            'work',
            'contact',
            'mail',
            'gmail',
            'hotmail',
            'outlook',
            'yahoo',
        ];

        $tokens = array_values(array_filter(array_map(function ($token) use ($noise) {
            $token = $this->normalizeText($token);

            return $token !== '' && !in_array($token, $noise, true) ? $token : null;
        }, $this->nameTokens($local))));

        if (count($tokens) > 1) {
            return $this->orderEmailNameParts($tokens);
        }

        return $this->orderEmailNameParts($this->splitJoinedEmailName($this->normalizeText($tokens[0] ?? $local)));
    }

    private function orderEmailNameParts(array $parts): array
    {
        $parts = array_values(array_unique(array_filter($parts)));

        if (count($parts) !== 2) {
            return $parts;
        }

        [$a, $b] = $parts;

        $aIsFirst = in_array($a, $this->normalizedFirstNames, true);
        $bIsFirst = in_array($b, $this->normalizedFirstNames, true);
        $aIsLast = in_array($a, $this->normalizedLastNames, true);
        $bIsLast = in_array($b, $this->normalizedLastNames, true);

        if ($aIsLast && $bIsFirst) {
            return [$b, $a];
        }

        if ($aIsFirst && $bIsLast) {
            return [$a, $b];
        }

        if ($bIsFirst && !$aIsFirst) {
            return [$b, $a];
        }

        return [$a, $b];
    }

    private function splitJoinedEmailName(string $joined): array
    {
        if ($joined === '') {
            return [];
        }

        $firsts = $this->normalizedFirstNames;
        $lasts = $this->normalizedLastNames;

        usort($firsts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        usort($lasts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($firsts as $first) {
            if ($first !== '' && str_starts_with($joined, $first)) {
                $rest = mb_substr($joined, mb_strlen($first));

                if ($rest !== '' && mb_strlen($rest) >= 3) {
                    return $this->orderEmailNameParts([$first, $rest]);
                }
            }

            if ($first !== '' && str_ends_with($joined, $first)) {
                $before = mb_substr($joined, 0, mb_strlen($joined) - mb_strlen($first));

                if ($before !== '' && mb_strlen($before) >= 3) {
                    return $this->orderEmailNameParts([$before, $first]);
                }
            }
        }

        foreach ($lasts as $last) {
            if ($last !== '' && str_starts_with($joined, $last)) {
                $rest = mb_substr($joined, mb_strlen($last));

                if ($rest !== '' && mb_strlen($rest) >= 3) {
                    return $this->orderEmailNameParts([$last, $rest]);
                }
            }

            if ($last !== '' && str_ends_with($joined, $last)) {
                $before = mb_substr($joined, 0, mb_strlen($joined) - mb_strlen($last));

                if ($before !== '' && mb_strlen($before) >= 3) {
                    return $this->orderEmailNameParts([$before, $last]);
                }
            }
        }

        return [$joined];
    }

    private function completeNameWithEmail(string $name, array $emailParts): string
    {
        $tokens = array_map(fn ($t) => $this->normalizeText($t), $this->nameTokens($name));

        if (count($tokens) >= 2 || count($emailParts) < 2) {
            return $name;
        }

        $only = $tokens[0] ?? null;

        if (!$only) {
            return $name;
        }

        foreach ($emailParts as $part) {
            if ($part === $only || str_contains($part, $only) || str_contains($only, $part)) {
                return implode(' ', $emailParts);
            }
        }

        return $name;
    }

    private function cleanNameLine(string $line): string
    {
        $line = preg_replace('/\b(nom complet|full name|nom et prénom|nom et prenom|nom|name|prenom|prénom|candidate|candidat)\b\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/[|•●■◆►▶]+/u', ' ', $line);
        $line = preg_replace('/\b(cv|curriculum vitae|resume|profil|profile|pro)\b/iu', ' ', $line);
        $line = preg_replace('/\s+/', ' ', $line);

        return trim($line, " \t\n\r\0\x0B:-");
    }

    private function isPossibleNameLine(
        string $clean,
        string $normalized,
        ?string $email,
        ?string $phone,
        ?string $title,
        ?string $city,
        array $emailParts = []
    ): bool {
        if ($clean === '' || mb_strlen($clean) < 3 || mb_strlen($clean) > 55) {
            return false;
        }

        if (preg_match('/\d|@|https?:|www\.|\+212|00212/i', $clean)) {
            return false;
        }

        if (!preg_match('/^[A-Za-zÀ-ÿ\'\-\s]+$/u', $clean)) {
            return false;
        }

        $tokens = $this->nameTokens($clean);
        $count = count($tokens);

        if ($count < 1 || $count > 4) {
            return false;
        }

        if ($title && str_contains($normalized, $this->normalizeText($title))) {
            return false;
        }

        if ($city && str_contains($normalized, $this->normalizeText($city))) {
            return false;
        }

        if (
            $this->containsAny($normalized, $this->nameBlockers)
            || $this->containsAny($normalized, $this->nameSectionBlockers)
            || $this->containsAny($normalized, $this->companyWords)
            || $this->containsAny($normalized, $this->titleKeywords)
        ) {
            return false;
        }

        if ($this->looksLikeSchoolOrCompany($normalized)) {
            return false;
        }

        if ($this->looksLikeExperienceOrSkill($normalized) && !$this->hasEmailOverlap($tokens, $emailParts)) {
            return false;
        }

        return true;
    }

    private function scoreNameCandidate(
        string $clean,
        string $normalized,
        int $index,
        array $lines,
        ?string $email,
        ?string $phone,
        array $emailParts = []
    ): int {
        $score = $index <= 3 ? 45 : ($index <= 8 ? 32 : ($index <= 20 ? 16 : 4));

        $tokens = $this->nameTokens($clean);
        $emailOverlap = 0;
        $dictionaryHits = 0;

        foreach ($tokens as $token) {
            $n = $this->normalizeText($token);

            if (in_array($n, $this->normalizedFirstNames, true)) {
                $score += 45;
                $dictionaryHits++;
            }

            if (in_array($n, $this->normalizedLastNames, true)) {
                $score += 38;
                $dictionaryHits++;
            }

            foreach ($emailParts as $part) {
                if ($part !== '' && ($part === $n || str_contains($part, $n) || str_contains($n, $part))) {
                    $emailOverlap++;
                    $score += 80;
                }
            }
        }

        if ($emailOverlap > 0) {
            $score += 60;
        }

        if ($dictionaryHits === 0 && $emailOverlap === 0) {
            $score -= 45;
        }

        if ($this->isMostlyUpperOrTitleCase($clean)) {
            $score += 10;
        }

        if (count($tokens) === 2 || count($tokens) === 3) {
            $score += 25;
        }

        if (count($tokens) === 1 && $emailOverlap === 0) {
            $score -= 40;
        }

        if (count($tokens) >= 4) {
            $score -= 18;
        }

        return $score;
    }

    private function hasEmailOverlap(array $tokens, array $emailParts): bool
    {
        foreach ($tokens as $token) {
            $n = $this->normalizeText($token);

            foreach ($emailParts as $part) {
                if ($part !== '' && ($part === $n || str_contains($part, $n) || str_contains($n, $part))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function extractNameFromFilename(string $filename, array $emailParts = []): ?string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = preg_replace('/[_\-.]+/', ' ', $base);
        $base = preg_replace('/\s+/', ' ', trim($base));

        if (preg_match('/^[a-f0-9]{15,}$/i', str_replace(' ', '', $base))) {
            return null;
        }

        $base = preg_replace('/\b(cv|resume|profil|profile|final|version|copie|copy|scan|pdf|docx|doc|pro)\b/i', ' ', $base);
        $base = preg_replace('/\s+/', ' ', trim($base));
        $norm = $this->normalizeText($base);

        if (!$this->isPossibleNameLine($base, $norm, null, null, null, null, $emailParts)) {
            return null;
        }

        return $this->scoreNameCandidate($base, $norm, 0, [$base], null, null, $emailParts) >= 80
            ? $this->beautifyName($base)
            : null;
    }

    private function extractNameFromEmail(string $email): ?string
    {
        $parts = $this->emailNameParts($email);

        if (empty($parts)) {
            return null;
        }

        return implode(' ', array_map(fn ($p) => $this->beautifyName($p), $parts));
    }

    private function extractTitleFromFilename(string $filename): ?string
    {
        $base = preg_replace('/[_\-.]+/', ' ', pathinfo($filename, PATHINFO_FILENAME));
        $base = preg_replace('/\s+/', ' ', trim($base));

        if ($base === '' || preg_match('/^[a-f0-9]{15,}$/i', str_replace(' ', '', $base))) {
            return null;
        }

        return $this->containsAny($this->normalizeText($base), $this->titleKeywords)
            ? $this->normalizeTitle($base)
            : null;
    }

    private function importantLines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);

        return array_values(array_filter(array_map(
            fn ($l) => trim(preg_replace('/\s+/', ' ', $l)),
            $lines
        ), fn ($l) => $l !== ''));
    }

    private function normalizeLinesForParsing(string $text): string
    {
        $text = $this->safeDbText($text, 60000) ?? '';
        $text = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }

    private function safeProfile(array $profile): array
    {
        $json = json_encode($profile, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }

    private function safeDbText(?string $text, int $limit = 60000): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = (string) $text;

        if ($text === '') {
            return null;
        }

        $text = @mb_convert_encoding(
            $text,
            'UTF-8',
            'UTF-8, Windows-1252, ISO-8859-1, ISO-8859-15'
        );

        $text = str_replace(["\x00"], '', $text);

        $text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);

        $text = preg_replace('/[^\P{C}\n\r\t]+/u', '', $text);

        $text = preg_replace('/\s+/', ' ', $text);

        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text, 'UTF-8') > $limit) {
            $text = mb_substr($text, 0, $limit, 'UTF-8');
        }

        return $text;
    }

    private function looksLikeNameOnly(string $text): bool
    {
        $norm = $this->normalizeText($text);
        $tokens = $this->nameTokens($text);

        if (count($tokens) < 1 || count($tokens) > 4) {
            return false;
        }

        $hits = 0;

        foreach ($tokens as $token) {
            $n = $this->normalizeText($token);

            if (
                in_array($n, $this->normalizedFirstNames, true)
                || in_array($n, $this->normalizedLastNames, true)
            ) {
                $hits++;
            }
        }

        return $hits >= 1 && !$this->containsAny($norm, $this->titleKeywords);
    }

    private function looksLikeLongExperienceSentence(string $normalized): bool
    {
        return $this->containsAny($normalized, [
            'jai',
            'j ai',
            'je suis',
            'je travaille',
            'experience',
            'experiences',
            'expérience',
            'expériences',
            'mission',
            'missions',
            'realisation',
            'réalisation',
            'responsabilite',
            'responsabilité',
            'poste occupe',
            'poste occupé',
            'actuellement',
            'precedemment',
            'précédemment',
        ]);
    }

    private function looksLikeSchoolOrCompany(string $normalized): bool
    {
        return $this->containsAny($normalized, [
            'lycee',
            'lycée',
            'ecole',
            'école',
            'institut',
            'universite',
            'université',
            'faculte',
            'faculté',
            'ofppt',
            'ista',
            'isgi',
            'ensam',
            'ensa',
            'societe',
            'société',
            'sarl',
            'groupe',
            'group',
            'company',
            'morocco',
            'maroc',
            'freelance',
            'services',
            'industrie',
            'industries',
        ]);
    }

    private function looksLikeExperienceOrSkill(string $normalized): bool
    {
        return $this->containsAny($normalized, [
            'stage',
            'garage',
            'mecanique',
            'mécanique',
            'monteur',
            'cableur',
            'câbleur',
            'passionne',
            'passionné',
            'magasins',
            'iso',
            'management',
            'qualite',
            'qualité',
            'mise en service',
            'expedition',
            'exportation',
            'missions',
            'taches',
            'tâches',
            'automates',
            'cablage',
            'câblage',
            'armoires',
            'encadrement',
            'validation',
            'produits',
            'travaille',
            'travaillé',
            'install',
            'installation',
            'maintenance',
        ]);
    }

    private function nameTokens(string $text): array
    {
        preg_match_all('/[A-Za-zÀ-ÿ\'\-]+/u', $text, $m);

        return $m[0] ?? [];
    }

    private function isMostlyUpperOrTitleCase(string $text): bool
    {
        return $text === mb_strtoupper($text, 'UTF-8')
            || $text === mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    private function containsAny(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            $n = $this->normalizeText($term);

            if ($n !== '' && str_contains($text, $n)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(?string $text): string
    {
        $text = $this->safeDbText($text, 10000) ?? '';

        $text = mb_strtolower($text, 'UTF-8');

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($ascii !== false) {
            $text = $ascii;
        }

        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function beautifyText(string $text): string
    {
        $text = $this->safeDbText($text, 255) ?? '';

        return trim(mb_convert_case(preg_replace('/\s+/', ' ', $text), MB_CASE_TITLE, 'UTF-8'));
    }

    private function beautifyName(string $text): string
    {
        return $this->beautifyText(str_replace(['_', '.'], ' ', $text));
    }

    private function fallbackTitles(): array
    {
        return [
            'Technicien Automatisme et Câblage des Armoires',
            'Monteur Câbleur Armoires Électriques',
            'Monteur Câbleur',
            'Technicien de Maintenance',
            'Technicien Maintenance Industrielle',
            'Technicien Électricité Industrielle',
            'Technicien Électricité',
            'Technicien Électromécanique',
            'Électromécanicien',
            'Mécanicien Industriel',
            'Technicien Mécanique',
            'Technicien Qualité',
            'Contrôleur Qualité',
            'Responsable Qualité',
            'Assistant Qualité',
            'Assistant Qualité ISO 9001',
            'Déclarant en Douane',
            'Aide Déclarant en Douane',
            'Responsable Douane',
            'Agent de Transit',
            'Transitaire',
            'Agent de Dédouanement',
            'Agent Import Export',
            'Assistant Import Export',
            'Assistant Logistique',
            'Agent Logistique',
            'Responsable Logistique',
            'Magasinier',
            'Cariste',
            'Préparateur de Commande',
            'Technicien Informatique',
            'Technicien Support Informatique',
            'Développeur Web',
            'Développeur Full Stack',
            'Assistant Administratif',
            'Assistante Administrative',
            'Comptable',
            'Commercial',
            'Commercial Terrain',
        ];
    }

    private function fallbackTitleKeywords(): array
    {
        return [
            'technicien',
            'technicienne',
            'maintenance',
            'automate',
            'automatisme',
            'cablage',
            'câblage',
            'armoire',
            'armoires',
            'monteur',
            'cableur',
            'câbleur',
            'mecanique',
            'mécanique',
            'electricite',
            'électricité',
            'electricien',
            'électricien',
            'electromecanique',
            'électromécanique',
            'qualite',
            'qualité',
            'controle',
            'contrôle',
            'controleur',
            'contrôleur',
            'iso',
            'hse',
            'douane',
            'transit',
            'transitaire',
            'dedouanement',
            'dédouanement',
            'import',
            'export',
            'logistique',
            'stock',
            'magasinier',
            'cariste',
            'assistant',
            'assistante',
            'responsable',
            'agent',
            'chef',
            'comptable',
            'commercial',
            'informatique',
            'developpeur',
            'développeur',
            'support',
        ];
    }

    private function fallbackTitleBlockers(): array
    {
        return [
            'nom',
            'prenom',
            'prénom',
            'adresse',
            'telephone',
            'téléphone',
            'email',
            'formation',
            'experience',
            'expérience',
            'competences',
            'compétences',
            'langues',
            'loisirs',
            'profil',
            'contact',
            'lycee',
            'lycée',
            'ecole',
            'école',
        ];
    }

    private function fallbackNameBlockers(): array
    {
        return [
            'cv',
            'curriculum',
            'profil',
            'profile',
            'contact',
            'adresse',
            'telephone',
            'téléphone',
            'email',
            'formation',
            'experience',
            'expérience',
            'competences',
            'compétences',
            'langues',
            'loisirs',
            'stage',
            'garage',
            'mecanique',
            'monteur',
            'cableur',
            'automates',
            'cablage',
            'armoires',
            'validation',
            'produits',
            'qualite',
            'qualité',
            'technicien',
            'technicienne',
            'responsable',
            'assistant',
            'assistante',
            'agent',
            'chef',
            'maintenance',
            'installation',
            'installateur',
        ];
    }

    private function fallbackNameSectionBlockers(): array
    {
        return [
            'experience professionnelle',
            'formation academique',
            'formation académique',
            'competences techniques',
            'missions',
            'taches',
            'tâches',
            'centres d interet',
            'langues',
            'profil professionnel',
            'objectif professionnel',
        ];
    }

    private function fallbackCompanyWords(): array
    {
        return [
            'societe',
            'société',
            'sarl',
            'sa',
            'sas',
            'groupe',
            'group',
            'company',
            'entreprise',
            'maroc',
            'morocco',
            'lycee',
            'lycée',
            'ecole',
            'école',
            'institut',
            'universite',
            'université',
            'ofppt',
            'ista',
            'industrie',
            'industries',
            'services',
            'freelance',
        ];
    }

    private function fallbackFilenameNoise(): array
    {
        return [
            'cv',
            'resume',
            'profil',
            'profile',
            'final',
            'version',
            'copie',
            'copy',
            'scan',
            'pdf',
            'doc',
            'docx',
            'pro',
        ];
    }

    private function fallbackFirstNames(): array
    {
        return [
            'mohamed',
            'mohammed',
            'mohammad',
            'ahmed',
            'hamza',
            'youssef',
            'yassine',
            'yassin',
            'ayoub',
            'anas',
            'amine',
            'omar',
            'ali',
            'hassan',
            'houssam',
            'houssain',
            'mehdi',
            'zakaria',
            'mustapha',
            'khalid',
            'rachid',
            'said',
            'saad',
            'soufiane',
            'reda',
            'brahim',
            'ibrahim',
            'ismail',
            'othmane',
            'oussama',
            'ilyas',
            'adnane',
            'bilal',
            'badr',
            'ayman',
            'imad',
            'nabil',
            'jalal',
            'jamal',
            'kamal',
            'karim',
            'mounir',
            'salah',
            'taha',
            'tarik',
            'walid',
            'driss',
            'idriss',
            'issam',
            'samir',
            'hicham',
            'younes',
            'hatim',
            'adil',
            'mourad',
            'marouane',
            'achraf',
            'abdelkebir',
            'abdelkabir',
            'abdelhak',
            'abdelilah',
            'abdelaziz',
            'abdelali',
            'fatima',
            'zahra',
            'khadija',
            'aicha',
            'maryam',
            'meryem',
            'salma',
            'asmaa',
            'asma',
            'hajar',
            'ikram',
            'imane',
            'jihane',
            'hanane',
            'ibtissam',
            'sanaa',
            'sara',
            'souad',
            'zineb',
            'nada',
            'nadia',
            'nawal',
            'amal',
            'lamia',
            'oumaima',
            'aya',
            'doha',
            'douaa',
            'wiam',
            'wissal',
            'ghizlane',
            'ghita',
            'hafsa',
            'siham',
            'samira',
            'soukaina',
            'chaimae',
            'salima',
            'halima',
            'karima',
            'latifa',
            'loubna',
            'hind',
            'houda',
            'mouna',
            'ilham',
            'yasmine',
            'rim',
            'rania',
            'rajae',
            'manal',
            'marwa',
            'malak',
            'sabrina',
            'safae',
            'kaoutar',
            'khaoula',
            'hasna',
            'bouchra',
            'basma',
            'aissa',
        ];
    }

    private function fallbackLastNames(): array
    {
        return [
            'alami',
            'benjelloun',
            'bennani',
            'tazi',
            'fassi',
            'idrissi',
            'alaoui',
            'cherkaoui',
            'bennis',
            'berrada',
            'lahlou',
            'lamrani',
            'amrani',
            'ait',
            'taleb',
            'khattabi',
            'bouziane',
            'bensouda',
            'benslimane',
            'bensalem',
            'bensaid',
            'benali',
            'bennasser',
            'benmoussa',
            'belhaj',
            'belkadi',
            'belkacem',
            'belarbi',
            'harrak',
            'harrani',
            'khalfi',
            'bouzidi',
            'ouardi',
            'mansouri',
            'hamdaoui',
            'kadiri',
            'azzouzi',
            'hachimi',
            'housni',
            'malki',
            'mokhtari',
            'omari',
            'qadi',
            'yousfi',
            'filali',
            'moumni',
            'ouazzani',
            'ouafi',
            'fakir',
            'haddadi',
            'haddad',
            'hassani',
            'hilali',
            'khayari',
            'madani',
            'mrabet',
            'yamani',
            'saoud',
            'saidi',
            'saadi',
            'sadiqi',
            'seddiki',
            'sabri',
            'sahraoui',
            'sanhaji',
            'sbai',
            'slimani',
            'tahiri',
            'talbi',
            'wahbi',
            'zaki',
            'zerouali',
            'ziani',
            'rahmani',
            'rami',
            'azizi',
            'allali',
            'amari',
            'badri',
            'bahri',
            'bakkali',
            'baraka',
            'bassir',
            'chafik',
            'chafiki',
            'chakir',
            'cherif',
            'daoudi',
            'dahmani',
            'dahbi',
            'drissi',
            'fahmi',
            'fathi',
            'ghazali',
            'hajji',
            'hamdani',
            'jaafari',
            'jabri',
            'jellal',
            'kabbaj',
            'karimi',
            'khalil',
            'lazrak',
            'mabrouk',
            'maliki',
            'marzouki',
            'masmoudi',
            'messaoudi',
            'naji',
            'nassiri',
            'ouahbi',
            'qadiri',
            'rahal',
            'salhi',
            'samadi',
            'chafik',
            'mouhim',
            'banja',
            'raghib',
            'simo',
            'simoraghib',
            'aissa',
            'basma',
            'mohammedia',
        ];
    }
}