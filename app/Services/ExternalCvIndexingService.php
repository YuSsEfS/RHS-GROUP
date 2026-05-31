<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\ExternalCv;
use App\Models\ExternalCvBatch;
use App\Services\CandidateMatchingSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

    public function __construct(
        protected CvExtractionService $extraction,
        protected CvIndexingService $indexing,
        protected CvDuplicateDetectionService $duplicates,
    )
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

        $this->titleKeywords = array_values(array_filter(
            $this->titleKeywords,
            fn ($keyword) => mb_strlen($this->normalizeText($keyword)) >= 2
        ));

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
            'ecoute',
            'precision',
            'rigueur',
            'serieux',
            'ponctuel',
            'rapide',
            'sens de la relation client',
            'capacite d adaptation',
            'travail en equipe',
        ];
    }

    public function indexBatch(ExternalCvBatch $batch, bool $force = false): void
    {
        $this->prepareBatchForIndexing($batch, $force);

        while ($this->indexBatchSlice($batch->fresh() ?? $batch, false, 50)) {
            // Kept for direct service callers; queued jobs use slices to avoid blocking the queue.
        }
    }

    public function indexBatchSlice(ExternalCvBatch $batch, bool $force = false, int $limit = 25): bool
    {
        $this->prepareBatchForIndexing($batch, $force);

        $statuses = [ExternalCv::STATUS_PENDING];

        if ($force) {
            $statuses[] = ExternalCv::STATUS_FAILED;
        }

        $files = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->whereIn('status', $statuses)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        foreach ($files as $externalCv) {
            try {
                $this->indexOne($externalCv);
            } catch (\Throwable $e) {
                Log::warning('External CV indexing failed', [
                    'external_cv_id' => $externalCv->id,
                    'batch_id' => $batch->id,
                    'filename' => $externalCv->original_filename,
                    'message' => $e->getMessage(),
                ]);

                $externalCv->update([
                    'status' => ExternalCv::STATUS_FAILED,
                    'error_message' => $this->safeDbText($e->getMessage(), 1000),
                    'indexed_at' => now(),
                ]);
            }
        }

        $this->refreshBatchStats($batch->fresh() ?? $batch);

        return ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', ExternalCv::STATUS_PENDING)
            ->exists();
    }

    private function prepareBatchForIndexing(ExternalCvBatch $batch, bool $force = false): void
    {
        if ($force) {
            ExternalCv::query()
                ->where('batch_id', $batch->id)
                ->update([
                    'status' => ExternalCv::STATUS_PENDING,
                    'cv_id' => null,
                    'duplicate_of_cv_id' => null,
                    'duplicate_score' => null,
                    'duplicate_reason' => null,
                    'error_message' => null,
                    'indexed_at' => null,
                ]);
        }

        $updates = [
            'status' => 'processing',
            'processing_status' => 'en_cours',
            'processing_started_at' => $batch->processing_started_at ?: now(),
            'processing_completed_at' => null,
            'processing_error_message' => null,
        ];

        if ($force) {
            $updates['indexed_files'] = 0;
            $updates['failed_files'] = 0;
            $updates['duplicate_files'] = 0;
        }

        $batch->update($updates);
    }

    public function reindexBatch(ExternalCvBatch $batch): void
    {
        $this->indexBatch($batch, true);
    }

    public function refreshBatchStats(ExternalCvBatch $batch): void
    {
        $indexed = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', ExternalCv::STATUS_INDEXED)
            ->count();

        $failed = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', ExternalCv::STATUS_FAILED)
            ->count();

        $duplicates = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', ExternalCv::STATUS_DUPLICATE)
            ->count();

        $pending = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->where('status', ExternalCv::STATUS_PENDING)
            ->count();

        $status = 'completed';
        $processingStatus = 'termine';

        if ($indexed === 0 && $duplicates === 0 && $failed > 0 && $pending === 0) {
            $status = 'failed';
            $processingStatus = 'echoue';
        } elseif ($pending > 0) {
            $status = 'processing';
            $processingStatus = 'en_cours';
        }

        $batch->update([
            'status' => $status,
            'processing_status' => $processingStatus,
            'indexed_files' => $indexed,
            'failed_files' => $failed,
            'duplicate_files' => $duplicates,
            'processing_completed_at' => $pending > 0 ? null : now(),
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

        $indexedProfile = $this->indexing->buildStructuredProfile($text, [], $externalCv->original_filename);

        $resolvedEmail = $indexedProfile['email'] ?? $this->extractEmail($text);
        $resolvedPhone = $indexedProfile['phone'] ?? $this->extractPhone($text);
        $resolvedCity = $this->resolveCity($text, $normalizedText, $indexedProfile['city'] ?? null);
        $resolvedTitle = $this->resolveTitle($text, $normalizedText, $externalCv->original_filename, $indexedProfile['title'] ?? null);
        $resolvedName = $this->resolveName(
            $text,
            $resolvedEmail,
            $resolvedPhone,
            $resolvedTitle,
            $resolvedCity,
            $externalCv->original_filename,
            $indexedProfile['full_name'] ?? null
        );

        $profile = $this->safeProfile(array_merge($indexedProfile, [
            'full_name' => $resolvedName,
            'email' => $resolvedEmail,
            'phone' => $resolvedPhone,
            'title' => $resolvedTitle,
            'city' => $resolvedCity,
            'summary' => $this->safeDbText(mb_substr($text, 0, 2500), 2500),
        ]));

        $syncedCvId = null;

        DB::transaction(function () use (
            $externalCv,
            $hash,
            $text,
            $profile,
            $resolvedName,
            $resolvedEmail,
            $resolvedPhone,
            $resolvedCity,
            $resolvedTitle,
            &$syncedCvId
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

            $linkedCv = $externalCv->cv_id ? Cv::find($externalCv->cv_id) : null;

            if (
                !$linkedCv
                && Schema::hasColumn('cvs', 'source_type')
                && Schema::hasColumn('cvs', 'source_id')
            ) {
                $linkedCv = Cv::query()
                    ->where('source_type', 'external_db')
                    ->where('source_id', $externalCv->id)
                    ->first();
            }

            $linkedCvBelongsToThisImport = $linkedCv
                && (
                    !Schema::hasColumn('cvs', 'source_type')
                    || !Schema::hasColumn('cvs', 'source_id')
                    || (
                        $linkedCv->source_type === 'external_db'
                        && (int) $linkedCv->source_id === (int) $externalCv->id
                    )
                );
            $existingCv = !$externalCv->duplicate_of_cv_id && $linkedCvBelongsToThisImport
                ? $linkedCv
                : null;

            $duplicate = $this->duplicates->findLikelyDuplicate([
                'file_hash' => $hash,
                'email' => $resolvedEmail,
                'phone' => $resolvedPhone,
                'candidate_name' => $resolvedName,
                'original_filename' => $externalCv->original_filename,
                'file_size' => (int) $externalCv->file_size,
                'text' => $text,
                'current_title' => $resolvedTitle,
                'city' => $resolvedCity,
            ], $existingCv?->id);

            if ($duplicate) {
                if ($existingCv && $existingCv->id !== $duplicate['cv_id']) {
                    $this->cleanupOwnedCvIfDuplicateSwitch($existingCv, $externalCv);
                }

                $externalCv->update([
                    'cv_id' => $duplicate['cv_id'],
                    'duplicate_of_cv_id' => $duplicate['cv_id'],
                    'duplicate_score' => $duplicate['score'],
                    'duplicate_reason' => $duplicate['reason'],
                    'status' => ExternalCv::STATUS_DUPLICATE,
                    'error_message' => null,
                    'indexed_at' => now(),
                ]);

                $syncedCvId = (int) $duplicate['cv_id'];

                return;
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

            if (Schema::hasColumn('cvs', 'original_file_size')) {
                $cvData['original_file_size'] = (int) ($externalCv->file_size ?: 0);
            }

            if (Schema::hasColumn('cvs', 'compression_status')) {
                $cvData['compression_status'] = Cv::COMPRESSION_STATUS_PENDING;
            }

            if (Schema::hasColumn('cvs', 'duplicate_of_cv_id')) {
                $cvData['duplicate_of_cv_id'] = null;
            }

            if (Schema::hasColumn('cvs', 'duplicate_score')) {
                $cvData['duplicate_score'] = null;
            }

            if (Schema::hasColumn('cvs', 'duplicate_reason')) {
                $cvData['duplicate_reason'] = null;
            }

            $cv = $existingCv
                ? tap($existingCv)->update($cvData)
                : Cv::create($cvData);

            $externalCv->update([
                'cv_id' => $cv->id,
                'duplicate_of_cv_id' => null,
                'duplicate_score' => null,
                'duplicate_reason' => null,
                'status' => ExternalCv::STATUS_INDEXED,
                'error_message' => null,
                'indexed_at' => now(),
            ]);

            $syncedCvId = (int) $cv->id;
        });

        if ($syncedCvId) {
            app(CandidateMatchingSyncService::class)->dispatchForCvId($syncedCvId, 'external_cv_indexing');
        }
    }

    private function cleanupOwnedCvIfDuplicateSwitch(Cv $cv, ExternalCv $externalCv): void
    {
        if (
            !Schema::hasColumn('cvs', 'source_type')
            || !Schema::hasColumn('cvs', 'source_id')
            || $cv->source_type !== 'external_db'
            || (int) $cv->source_id !== (int) $externalCv->id
        ) {
            return;
        }

        $hasOtherLinks = ExternalCv::query()
            ->where('cv_id', $cv->id)
            ->whereKeyNot($externalCv->id)
            ->exists();

        if ($hasOtherLinks) {
            return;
        }

        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            Storage::disk('local')->delete($cv->encrypted_path);
        }

        $compressionDisk = (string) config('cv_storage.compression_disk', 'local');

        if (!empty($cv->compressed_path) && Storage::disk($compressionDisk)->exists($cv->compressed_path)) {
            Storage::disk($compressionDisk)->delete($cv->compressed_path);
        }

        $cv->delete();
    }

    private function extractTextFromFile(string $filePath, string $extension): string
    {
        return $this->safeDbText($this->extraction->extractTextFromFile($filePath, $extension), 60000) ?? '';
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

    private function resolveCity(string $text, string $normalizedText, ?string $seedCity = null): ?string
    {
        return $this->extractCity($text, $normalizedText, $seedCity);
    }

    private function resolveTitle(string $text, string $normalizedText, string $filename, ?string $seedTitle = null): ?string
    {
        $localTitle = $this->extractTitle($text, $normalizedText, $filename);

        if (!$seedTitle) {
            return $localTitle;
        }

        if (!$localTitle) {
            return $this->beautifyText($seedTitle);
        }

        $seedNorm = $this->normalizeText($seedTitle);
        $localNorm = $this->normalizeText($localTitle);

        if ($seedNorm === $localNorm) {
            return $this->beautifyText($seedTitle);
        }

        if ($this->isSpecificTitle($localNorm) || !$this->isSpecificTitle($seedNorm)) {
            return $localTitle;
        }

        return $this->beautifyText($seedTitle);
    }

    private function resolveName(
        string $text,
        ?string $email,
        ?string $phone,
        ?string $title,
        ?string $city,
        string $filename,
        ?string $seedName = null
    ): ?string {
        $localName = $this->extractName($text, $email, $phone, $title, $city, $filename);

        if ($localName) {
            return $localName;
        }

        return $seedName ? $this->beautifyName($seedName) : null;
    }

    private function extractCity(string $text, string $normalizedText, ?string $seedCity = null): ?string
    {
        $terms = [];
        $candidates = [];

        foreach ($this->cityAliases as $alias => $city) {
            $aliasNorm = $this->normalizeText($alias);

            if ($aliasNorm !== '') {
                $terms[$aliasNorm] = $city;
            }
        }

        foreach ($this->cities as $city) {
            $cityNorm = $this->normalizeText($city);

            if ($cityNorm !== '') {
                $terms[$cityNorm] = $city;
            }
        }

        if ($seedCity) {
            $candidates[] = [
                'city' => $seedCity,
                'score' => 26,
                'line' => 999,
            ];
        }

        foreach (array_slice($this->importantLines($text), 0, 90) as $index => $line) {
            $lineNorm = $this->normalizeText($line);

            foreach ($terms as $termNorm => $city) {
                if ($termNorm === '' || !$this->containsWholeTerm($lineNorm, $termNorm)) {
                    continue;
                }

                $candidates[] = [
                    'city' => $city,
                    'score' => $this->scoreCityCandidateLine($line, $lineNorm, $index, $termNorm),
                    'line' => $index,
                ];
            }
        }

        if (empty($candidates)) {
            $earlyText = mb_substr($normalizedText, 0, 1800);

            foreach ($terms as $termNorm => $city) {
                if ($termNorm !== '' && $this->containsWholeTerm($earlyText, $termNorm)) {
                    $candidates[] = [
                        'city' => $city,
                        'score' => 18 + intdiv(mb_strlen($termNorm), 2),
                        'line' => 1000,
                    ];
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $a['line'] <=> $b['line'];
            }

            return $b['score'] <=> $a['score'];
        });

        return $candidates[0]['score'] >= 24 ? $this->beautifyText($candidates[0]['city']) : null;
    }

    private function scoreCityCandidateLine(string $line, string $lineNorm, int $index, string $matchedCity): int
    {
        $score = 12 + max(0, 48 - ($index * 2));

        if (preg_match('/\b(ville|city|adresse|address|localisation|localite|residence|residant|reside|habite|domicile|domicilie|based|coordonnees|contact)\b/u', $lineNorm)) {
            $score += 82;
        }

        if (preg_match('/\b(adresse actuelle|adresse personnelle|residence actuelle|domicilie a|domiciliee a|habite a|ville actuelle)\b/u', $lineNorm)) {
            $score += 46;
        }

        if (preg_match('/\b(maroc|morocco|ma)\b/u', $lineNorm)) {
            $score += 12;
        }

        if (preg_match('/@|\+212|00212|\b0[5-7]/u', $line)) {
            $score += 38;
        }

        if (preg_match('/\b(formation|education|etude|etudes|diplome|diplomes|baccalaureat|licence|master|universite|ecole|faculte|institut|ista|ofppt|lycee)\b/u', $lineNorm)) {
            $score -= 92;
        }

        if (preg_match('/\b(stage|experience|experiences|societe|company|entreprise|mission|missions|poste|emploi|travaille|projet|projets)\b/u', $lineNorm)) {
            $score -= 58;
        }

        if (preg_match('/\b(ne a|nee a|lieu de naissance|naissance)\b/u', $lineNorm)) {
            $score -= 28;
        }

        if (mb_strlen($lineNorm) > 110) {
            $score -= 22;
        }

        $score += intdiv(mb_strlen($matchedCity), 2);

        return $score;
    }

    private function isSpecificTitle(string $normalizedTitle): bool
    {
        return preg_match('/\b(qualite|quality|qhse|hse|douane|transit|logistique|maintenance|electricite|electromecanique|comptabilite|finance|informatique|developpeur|recrutement|production|achat|commercial|clientele|laboratoire)\b/u', $normalizedTitle) === 1;
    }

    private function extractTitle(string $text, string $normalizedText, string $filename): ?string
    {
        $candidates = [];
        $lines = array_slice($this->importantLines($text), 0, 180);

        foreach ($lines as $i => $line) {
            $clean = $this->cleanTitleLine($line);
            $clean = $this->stripTitleDatePrefix($clean);
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

        if (empty($candidates)) {
            foreach ($this->titleRuleMap() as $needles => $title) {
                foreach ((array) $needles as $needle) {
                    $n = $this->normalizeText($needle);

                    if ($n !== '' && preg_match('/(^|\s)' . preg_quote($n, '/') . '(\s|$)/u', $normalizedText)) {
                        $candidates[] = [
                            'title' => $title,
                            'score' => 34 + min(18, mb_strlen($n)),
                            'line' => 999,
                        ];
                    }
                }
            }
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $a['line'] <=> $b['line'];
            }

            return $b['score'] <=> $a['score'];
        });

        $selectedTitle = $candidates[0]['title'] ?? null;
        $selectedNorm = $this->normalizeText($selectedTitle);
        $selectedCompact = str_replace(' ', '', $selectedNorm);

        if (in_array($selectedCompact, ['qualite', 'qualit', 'clientele', 'entrepot', 'entrept', 'production', 'maintenance', 'logistique'], true)) {
            return null;
        }

        if (in_array($selectedCompact, ['electromecanique', 'electromecanicien', 'lectromcanique', 'lectromcanicien'], true)) {
            return 'Technicien Electromecanique';
        }

        return $selectedTitle;
    }

    private function cleanTitleLine(string $line): string
    {
        $line = preg_replace('/\b(poste|fonction|titre|emploi|métier|metier|profession|objective|objectif|profil recherché|profil recherche)\b\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/[|•●■◆►▶]+/u', ' ', $line);
        $line = preg_replace('/\s+/', ' ', $line);
        $line = $this->stripLeadingNameBeforeTitle($line);

        return trim($line, " \t\n\r\0\x0B:-");
    }

    private function stripTitleDatePrefix(string $line): string
    {
        $line = preg_replace('/^(?:present|pr[ée]sent|actuellement|aujourd hui|aujourd\'hui|a ce jour|depuis|\d{4})\s*[:\-]?\s*/iu', '', $line);

        return trim((string) $line);
    }

    private function stripLeadingNameBeforeTitle(string $line): string
    {
        if (!preg_match('/\b(technicien(?:ne)?|ingenieur|ing[eé]nieur|op[eé]rateur|op[eé]ratrice|assistant(?:e)?|agent|responsable|controleur|contr[oô]leur|controleuse|contr[oô]leuse|commercial|comptable|d[eé]clarant|magasinier|cariste|m[eé]canicien|[eé]lectricien)\b/iu', $line, $match, PREG_OFFSET_CAPTURE)) {
            return $line;
        }

        $offset = (int) $match[0][1];

        if ($offset <= 0) {
            return $line;
        }

        $prefix = trim(substr($line, 0, $offset));
        $prefixNorm = $this->normalizeText($prefix);
        $prefixWords = preg_split('/\s+/', $prefixNorm) ?: [];

        if (count($prefixWords) < 2 || count($prefixWords) > 4) {
            return $line;
        }

        if ($this->containsAny($prefixNorm, $this->titleKeywords) || $this->containsAny($prefixNorm, $this->titleBlockers)) {
            return $line;
        }

        return trim(substr($line, $offset));
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

        if (preg_match('/\b(inter\s*ts?|int\s*r\s*ts?|loisirs?|langues?|formations?|experiences?|competences?|connaissances?)\b/u', $norm)) {
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

        $words = preg_split('/\s+/', $norm) ?: [];
        $oneWordNoise = [
            'qualite',
            'clientele',
            'entrepot',
            'production',
            'maintenance',
            'logistique',
            'administratif',
            'administrative',
        ];

        if (count($words) === 1 && in_array($words[0], $oneWordNoise, true)) {
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
            'technicien specialise methode en fabrication mecanique' => 'Technicien Méthodes Fabrication Mécanique',
            'technicien spécialisé méthode en fabrication mécanique' => 'Technicien Méthodes Fabrication Mécanique',
            'technicien specialise methode' => 'Technicien Méthodes',
            'technicien spécialisé méthode' => 'Technicien Méthodes',
            'technicien methodes' => 'Technicien Méthodes',
            'technicien méthodes' => 'Technicien Méthodes',
            'methode en fabrication mecanique' => 'Technicien Méthodes Fabrication Mécanique',
            'méthode en fabrication mécanique' => 'Technicien Méthodes Fabrication Mécanique',
            'operateur de machine' => 'Opérateur Machine',
            'opérateur de machine' => 'Opérateur Machine',
            'operateur machine' => 'Opérateur Machine',
            'opérateur machine' => 'Opérateur Machine',
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

            'controleur qualite' => 'ContrÃ´leur QualitÃ©',
            'contrÃ´leur qualitÃ©' => 'ContrÃ´leur QualitÃ©',
            'controleuse qualite' => 'ContrÃ´leuse QualitÃ©',
            'contrÃ´leuse qualitÃ©' => 'ContrÃ´leuse QualitÃ©',
            'quality control' => 'ContrÃ´leur QualitÃ©',
            'quality controller' => 'ContrÃ´leur QualitÃ©',
            'agent qualite' => 'Agent QualitÃ©',
            'agent qualitÃ©' => 'Agent QualitÃ©',
            'inspecteur qualite' => 'Inspecteur QualitÃ©',
            'inspecteur qualitÃ©' => 'Inspecteur QualitÃ©',
            'technicien controle qualite' => 'Technicien ContrÃ´le QualitÃ©',
            'technicien contrÃ´le qualitÃ©' => 'Technicien ContrÃ´le QualitÃ©',
            'technicien laboratoire' => 'Technicien Laboratoire',
            'technicienne laboratoire' => 'Technicienne Laboratoire',
            'laboratory technician' => 'Technicien Laboratoire',
            'assistant qhse' => 'Assistant QHSE',
            'responsable qhse' => 'Responsable QHSE',

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
            'senior sales manager' => 'Senior Sales Manager',
            'sales manager' => 'Sales Manager',
            'directeur marketing commercial' => 'Directeur Marketing & Commercial',
            'directeur marketing et commercial' => 'Directeur Marketing & Commercial',
            'manager qualite operationnelle' => 'Manager Qualite Operationnelle',
            'chef equipe logistique' => 'Chef d Equipe Logistique',
            'chef d equipe logistique' => 'Chef d Equipe Logistique',
            'responsable moyens generaux' => 'Responsable Moyens Generaux',
            'responsable des moyens generaux' => 'Responsable Moyens Generaux',
            'agent expedition' => 'Agent d Expedition',
            'agent d expedition' => 'Agent d Expedition',
            'coordinatrice interne des ventes' => 'Coordinatrice Interne Ventes Evenements',
            'coordinatrice interne ventes evenements' => 'Coordinatrice Interne Ventes Evenements',
            'technicienne specialisee en commerce' => 'Technicienne Specialisee en Commerce',
            'agent de securite' => 'Agent de Securite',
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

        if (preg_match('/\b(a propos|about me|dans ce domaine|permis|mobilite|niveau|baccalaureat|ecole|lycee|statique|tournante|celibataire|marie|age)\b/u', $normalized)) {
            return false;
        }

        if (preg_match('/\b(de|du|des|a|au|aux|et|en)\b.*\b(de|du|des|a|au|aux|et|en)\b/u', $normalized)) {
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
        $base = preg_replace('/\b\d{1,8}\b/', ' ', $base);
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
        $lines = array_values(array_filter(array_map(
            fn ($l) => trim(preg_replace('/\s+/', ' ', $l)),
            $lines
        ), fn ($l) => $l !== ''));

        $maxLength = empty($lines) ? 0 : max(array_map('mb_strlen', $lines));

        if (count($lines) <= 3 || $maxLength > 220) {
            $semanticLines = $this->semanticLinesFromFlatText($text);

            if (count($semanticLines) > count($lines)) {
                return $semanticLines;
            }
        }

        return $lines;
    }

    private function normalizeLinesForParsing(string $text): string
    {
        $text = $this->safeDbText($text, 60000) ?? '';
        $text = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $semanticLines = $this->semanticLinesFromFlatText($text);

        if (count($semanticLines) > 1) {
            return implode(PHP_EOL, $semanticLines);
        }

        return trim($text);
    }

    private function semanticLinesFromFlatText(string $text): array
    {
        $text = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $text);
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return [];
        }

        $sectionPatterns = [
            '/\s+(CONNAISSANCES?\s+INFORMATIQUES?\b)/iu',
            '/\s+(COMP[ÉE]TENCES?(?:\s+TECHNIQUES?)?\b)/iu',
            '/\s+(EXP[ÉE]RIENCES?\s+PROFESSIONNELLES?\b)/iu',
            '/\s+(PARCOURS\s+PROFESSIONNEL\b)/iu',
            '/\s+(FORMATIONS?\b)/iu',
            '/\s+(DIPL[ÔO]MES?\b)/iu',
            '/\s+(LANGUES?\b)/iu',
            '/\s+(CENTRES?\s+D[’\']?INT[ÉE]R[ÊE]TS?\b)/iu',
            '/\s+(INT[ÉE]R[ÊE]TS?\b)/iu',
        ];

        foreach ($sectionPatterns as $pattern) {
            $text = preg_replace($pattern, "\n$1", $text);
        }

        $titleOpeners = 'TECHNICIEN(?:NE)?|ING[ÉE]NIEUR|OPERATEUR|OP[ÉE]RATEUR|OPERATRICE|OP[ÉE]RATRICE|ASSISTANT(?:E)?|AGENT|RESPONSABLE|CONTROLEUR|CONTR[ÔO]LEUR|CONTROLEUSE|CONTR[ÔO]LEUSE|COMMERCIAL|COMPTABLE|D[ÉE]CLARANT|MAGASINIER|CARISTE|M[ÉE]CANICIEN|[ÉE]LECTRICIEN';
        $text = preg_replace('/\s+([A-ZÀ-Ÿ][A-ZÀ-Ÿ\' -]{3,70}\s+(?:' . $titleOpeners . ')\b)/u', "\n$1", $text);

        $months = 'JANVIER|FEVRIER|F[ÉE]VRIER|MARS|AVRIL|MAI|JUIN|JUILLET|AOUT|AO[ÛU]T|SEPTEMBRE|OCTOBRE|NOVEMBRE|DECEMBRE|D[ÉE]CEMBRE';
        $text = preg_replace('/\s+((?:Op[eé]rateur|Operateur|Contr[oô]leur|Controleur|Stage|Technicien|Assistant|Agent|Responsable|Commercial|Comptable)[^.]{0,90}\s+(?:' . $months . '|PRESENT|PR[ÉE]SENT)\b)/iu', "\n$1", $text);

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return array_values(array_filter(array_map(
            fn ($line) => trim((string) preg_replace('/\s+/', ' ', $line)),
            $lines
        ), fn ($line) => $line !== ''));
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

    private function containsWholeTerm(string $text, string $term): bool
    {
        return $term !== '' && preg_match('/(^|\s)' . preg_quote($term, '/') . '(\s|$)/u', $text) === 1;
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
            'Contrôleuse Qualité',
            'Agent Qualité',
            'Inspecteur Qualité',
            'Technicien Contrôle Qualité',
            'Technicien Laboratoire',
            'Technicienne Laboratoire',
            'Assistant QHSE',
            'Responsable QHSE',
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
            'Senior Sales Manager',
            'Sales Manager',
            'Directeur Marketing & Commercial',
            'Manager Qualite Operationnelle',
            'Chef d Equipe Logistique',
            'Responsable Moyens Generaux',
            'Technicien Methodes Fabrication Mecanique',
            'Agent d Expedition',
            'Coordinatrice Interne Ventes Evenements',
            'Technicienne Specialisee en Commerce',
            'Agent de Securite',
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
            'controleuse',
            'contrôleuse',
            'quality',
            'controller',
            'inspecteur',
            'iso',
            'qhse',
            'hse',
            'laboratoire',
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
            'sales',
            'vente',
            'marketing',
            'manager',
            'directeur',
            'directrice',
            'methodes',
            'methode',
            'expedition',
            'evenementiel',
            'evenements',
            'securite',
            'moyens generaux',
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
            'abdessamad',
            'abdesamad',
            'abdelmounaim',
            'abdelmonaim',
            'abdelmoumen',
            'mouhcine',
            'mohcine',
            'mohssine',
            'mouhsine',
            'houssame',
            'aymane',
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
            'atika',
            'hicham',
            'abdeslam',
            'abdelkrim',
            'abdelkarim',
            'kaoutar',
            'ouiam',
            'nidal',
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
            'wadi',
            'souiba',
            'labriki',
            'el bouanani',
            'amdou',
            'yabouri',
            'el hari',
            'elhari',
            'bani',
        ];
    }
}
