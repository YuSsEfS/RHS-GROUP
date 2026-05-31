<?php

namespace App\Services;

class CvIndexingService
{
    protected array $cityAliases = [];
    protected array $cityLabels = [];
    protected array $firstNames = [];
    protected array $lastNames = [];
    protected array $exactTitles = [];
    protected array $titleKeywords = [];
    protected array $titleBlockers = [];
    protected array $nameBlockers = [];
    protected array $skillKeywords = [];
    protected array $languageKeywords = [];

    public function __construct()
    {
        $parserConfig = config('external_cv_parser', []);

        foreach (($parserConfig['city_aliases'] ?? []) as $alias => $city) {
            $aliasNorm = $this->normalizeText($alias);
            $cityNorm = $this->normalizeText($city);

            if ($aliasNorm !== '') {
                $this->cityAliases[$aliasNorm] = $city;
            }

            if ($cityNorm !== '') {
                $this->cityLabels[$cityNorm] = $city;
            }
        }

        foreach (($parserConfig['cities'] ?? $this->fallbackCities()) as $city) {
            $cityNorm = $this->normalizeText($city);

            if ($cityNorm !== '') {
                $this->cityLabels[$cityNorm] = $city;
            }
        }

        $this->firstNames = array_values(array_unique(array_filter(array_map(
            fn ($name) => $this->normalizeText($name),
            $parserConfig['first_names'] ?? $this->fallbackFirstNames()
        ))));

        $this->lastNames = array_values(array_unique(array_filter(array_map(
            fn ($name) => $this->normalizeText($name),
            $parserConfig['last_names'] ?? $this->fallbackLastNames()
        ))));

        $titles = array_merge(
            $this->fallbackTitles(),
            $parserConfig['titles'] ?? [],
            $parserConfig['title_keywords'] ?? []
        );

        foreach ($titles as $title) {
            $clean = $this->normalizeTitleCandidate((string) $title);
            $norm = $this->normalizeText($clean);

            if ($norm !== '' && str_word_count($norm) >= 2) {
                $this->exactTitles[$norm] = $this->beautify($clean);
            }
        }

        $this->exactTitles['electromecanique'] = 'Technicien Electromecanique';
        $this->exactTitles['electromecanicien'] = 'Technicien Electromecanique';

        $this->titleKeywords = array_values(array_unique(array_merge(
            [
                'responsable', 'charge', 'chargee', 'chef', 'manager', 'directeur', 'directrice',
                'assistant', 'assistante', 'agent', 'technicien', 'technicienne', 'ingenieur',
                'ingenieure', 'controleur', 'controleuse', 'declarant', 'administrateur',
                'consultant', 'consultante', 'commercial', 'commerciale', 'conseiller',
                'conseillere', 'coordonateur', 'coordinatrice', 'superviseur', 'superviseuse',
                'receptionniste', 'comptable', 'analyste', 'developpeur', 'developer',
                'magasinier', 'transitaire', 'gestionnaire', 'recruteur', 'recruteuse',
                'approvisionneur', 'approvisionneuse', 'qualite', 'quality', 'maintenance',
                'production', 'logistique', 'douane', 'transit', 'achat', 'achats', 'rh',
                'ressources humaines', 'service client', 'support', 'call center', 'qhse',
                'hse', 'laboratoire', 'controle', 'controller', 'reception', 'expedition',
                'supply chain', 'transport', 'administratif', 'administrative',
                'sales', 'vente', 'marketing', 'methodes', 'methode', 'expedition',
                'evenementiel', 'evenements', 'securite', 'moyens generaux',
                'aide', 'monteur', 'cableur', 'cablage', 'qualiticien', 'teleconseiller',
                'teleconseillere', 'vendeur', 'vendeuse', 'serveur', 'serveuse',
                'cuisinier', 'cuisiniere', 'chauffeur', 'conducteur',
            ],
            array_map(fn ($title) => strtok($this->normalizeText($title), ' '), array_keys($this->exactTitles))
        )));

        $this->titleKeywords = array_values(array_filter(
            $this->titleKeywords,
            fn ($keyword) => mb_strlen($this->normalizeText($keyword)) >= 2
        ));

        $this->titleBlockers = [
            'curriculum vitae', 'cv', 'resume', 'profil', 'profile', 'competences', 'competences techniques',
            'langues', 'langue', 'experience', 'experiences', 'formation', 'formations', 'education',
            'adresse', 'address', 'email', 'telephone', 'mobile', 'contact', 'reference',
            'references', 'objectif', 'summary', 'about me', 'career objective', 'naissance',
            'date de naissance', 'nationalite', 'linkedin', 'github', 'portfolio', 'loisirs',
            'interets', 'hobbies', 'stage', 'societe', 'entreprise', 'company', 'mission',
            'missions', 'responsabilites', 'taches', 'travaille', 'travaillee', 'cherche',
            'recherche', 'disponible', 'maroc', 'morocco', 'francais', 'anglais',
            'ecoute', 'precision', 'rigueur', 'serieux', 'ponctuel', 'rapide',
            'sens de la relation client', 'capacite d adaptation', 'travail en equipe',
        ];

        $this->nameBlockers = [
            'curriculum vitae', 'cv', 'profil', 'profile', 'poste', 'fonction', 'intitule',
            'responsable', 'charge', 'manager', 'assistant', 'agent', 'technicien',
            'ingenieur', 'developpeur', 'commercial', 'adresse', 'email', 'telephone',
            'contact', 'linkedin', 'github', 'nationalite', 'maroc', 'morocco', 'casablanca',
            'rabat', 'tanger', 'marrakech', 'fes', 'agadir', 'experience', 'formation',
            'competences', 'langues', 'objectif', 'recrutement',
            'a propos', 'a propos de moi', 'about me', 'dans ce domaine',
            'permis', 'permis de conduire', 'mobilite', 'mobilite nationale',
            'niveau', 'baccalaureat', 'ecole', 'ecole prive', 'lycee',
            'statique', 'tournante', 'jeune', 'homme', 'femme',
            'celibataire', 'marie', 'mariee', 'age', 'expertise', 'competence',
        ];

        $this->skillKeywords = [
            'excel', 'word', 'power bi', 'sap', 'sage', 'odoo', 'sql', 'mysql', 'postgresql', 'php',
            'laravel', 'javascript', 'typescript', 'react', 'vue', 'docker', 'git', 'github',
            'linux', 'python', 'java', 'c#', '.net', 'powerpoint', 'office 365', 'crm', 'erp',
            'service client', 'sourcing', 'recrutement', 'paie', 'facturation', 'comptabilite',
            'douane', 'transit', 'logistique', 'supply chain', 'stock', 'haccp', 'iso 9001',
            'maintenance', 'automatisme', 'autocad', 'solidworks', 'anglais', 'francais',
            'arabe', 'espagnol', 'canva', 'photoshop', 'illustrator', 'call center', 'quality',
            'controle qualite', 'lean', 'production', 'commerce', 'salesforce', 'hubspot',
            'sage paie', 'cnss', 'amo', 'ir', 'tva', 'import export', 'dedouanement',
            'incoterms', 'magasin', 'inventory', 'qms', 'smq', 'iso 14001', 'iso 45001',
            'metrologie', '5s', 'kaizen', 'catia', 'siemens', 'plc', 'cnc', 'fanuc',
            'gmao', 'tms', 'wms', 'hotellerie', 'reception', 'caisse', 'sav',
            'service apres vente',
        ];

        $this->languageKeywords = [
            'francais' => 'Francais',
            'french' => 'Francais',
            'anglais' => 'Anglais',
            'english' => 'Anglais',
            'arabe' => 'Arabe',
            'arabic' => 'Arabe',
            'darija' => 'Arabe',
            'espagnol' => 'Espagnol',
            'spanish' => 'Espagnol',
            'allemand' => 'Allemand',
            'german' => 'Allemand',
            'italien' => 'Italien',
            'italian' => 'Italien',
        ];
    }

    public function buildStructuredProfile(string $text, array $seed = [], ?string $filename = null): array
    {
        $lines = $this->lines($text);
        $normalizedText = $this->normalizeText($text);

        $email = $seed['email'] ?? $this->extractEmail($text);
        $phone = $seed['phone'] ?? $this->extractPhone($text);
        $city = $seed['city'] ?? $this->extractCity($normalizedText, $lines);
        $title = $seed['title'] ?? $this->extractTitle($normalizedText, $lines, $filename);
        $name = $seed['full_name'] ?? $this->extractName($text, $lines, $email, $filename, $title, $city);

        return array_filter([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'title' => $title,
            'headline' => $title,
            'desired_position' => $title,
            'years_experience' => $this->extractExperienceYears($normalizedText),
            'languages' => $this->extractLanguages($normalizedText),
            'technical_skills' => $this->extractSkills($normalizedText),
            'city' => $city,
            'location' => $city,
            'summary' => mb_substr(trim($text), 0, 1500),
        ], fn ($value) => !is_null($value) && $value !== '' && $value !== []);
    }

    public function extractEmail(string $text): ?string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            return strtolower(trim($m[0]));
        }

        return null;
    }

    public function extractPhone(string $text): ?string
    {
        foreach ([
            '/(?:\+212|00212)\s*[5-7](?:[\s.\-]?[0-9]{2}){4}/',
            '/\b0\s*[5-7](?:[\s.\-]?[0-9]{2}){4}\b/',
            '/\b[5-7](?:[\s.\-]?[0-9]{2}){4}\b/',
        ] as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return preg_replace('/\s+/', '', trim($m[0]));
            }
        }

        return null;
    }

    public function extractCity(string $normalizedText, array $lines = []): ?string
    {
        $candidates = [];

        foreach (array_slice($lines, 0, 45) as $index => $line) {
            $lineNorm = $this->normalizeText($line);

            foreach ($this->cityAliases as $aliasNorm => $cityLabel) {
                if (!$this->containsWholeTerm($lineNorm, $aliasNorm)) {
                    continue;
                }

                $candidates[] = [
                    'city' => $cityLabel,
                    'score' => $this->scoreCityLine($line, $lineNorm, $index, $aliasNorm),
                ];
            }

            foreach ($this->cityLabels as $cityNorm => $cityLabel) {
                if (!$this->containsWholeTerm($lineNorm, $cityNorm)) {
                    continue;
                }

                $candidates[] = [
                    'city' => $cityLabel,
                    'score' => $this->scoreCityLine($line, $lineNorm, $index, $cityNorm),
                ];
            }
        }

        if (empty($candidates)) {
            foreach ($this->cityAliases as $aliasNorm => $cityLabel) {
                if ($this->containsWholeTerm(mb_substr($normalizedText, 0, 1800), $aliasNorm)) {
                    $candidates[] = ['city' => $cityLabel, 'score' => 20 + mb_strlen($aliasNorm)];
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $candidates[0]['score'] >= 18 ? $this->beautify($candidates[0]['city']) : null;
    }

    public function extractTitle(string $normalizedText, array $lines = [], ?string $filename = null): ?string
    {
        $candidates = [];

        foreach (array_slice($lines, 0, 30) as $index => $line) {
            $cleanLine = $this->normalizeTitleCandidate($line);
            $lineNorm = $this->normalizeText($cleanLine);

            if ($lineNorm === '' || $this->isBlockedTitleLine($lineNorm)) {
                continue;
            }

            $labelScore = $this->isLabelledTitleLine($lineNorm) ? 45 : 0;
            $topScore = max(0, 38 - ($index * 3));

            foreach ($this->exactTitles as $titleNorm => $displayTitle) {
                if ($this->containsWholeTerm($lineNorm, $titleNorm)) {
                    $candidates[] = [
                        'title' => $displayTitle,
                        'score' => 70 + $labelScore + $topScore + mb_strlen($titleNorm),
                    ];
                }
            }

            if ($this->looksLikeGenericTitleLine($cleanLine, $lineNorm)) {
                $candidates[] = [
                    'title' => $this->beautify($cleanLine),
                    'score' => 42 + $labelScore + $topScore,
                ];
            }
        }

        $fileTitle = $filename ? $this->extractTitleFromFilename($filename) : null;

        if ($fileTitle) {
            $candidates[] = ['title' => $fileTitle, 'score' => 36];
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        if ($candidates[0]['score'] < 40) {
            return null;
        }

        $selectedTitle = $candidates[0]['title'];
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

    public function extractName(
        string $text,
        array $lines = [],
        ?string $email = null,
        ?string $filename = null,
        ?string $title = null,
        ?string $city = null
    ): ?string {
        $emailParts = $this->emailNameParts($email);
        $candidates = [];

        foreach (array_slice($lines, 0, 18) as $index => $line) {
            $candidate = $this->cleanupNameLine($line);
            $candidateNorm = $this->normalizeText($candidate);

            if (!$this->looksLikeNameCandidate($candidate, $candidateNorm, $title, $city)) {
                continue;
            }

            $score = 24;

            if ($index < 3) {
                $score += 38;
            } elseif ($index < 7) {
                $score += 18;
            }

            if ($this->isLabelledNameLine($this->normalizeText($line))) {
                $score += 46;
            }

            $tokens = $this->tokenizeName($candidate);

            if (count($tokens) >= 2 && count($tokens) <= 4) {
                $score += 16;
            }

            if ($this->tokensLookLikeProperName($tokens)) {
                $score += 18;
            }

            if (!empty($tokens) && in_array($this->normalizeText($tokens[0]), $this->firstNames, true)) {
                $score += 24;
            }

            if (count($tokens) >= 2 && in_array($this->normalizeText(end($tokens)), $this->lastNames, true)) {
                $score += 16;
            }

            if ($this->matchesEmailParts($tokens, $emailParts)) {
                $score += 28;
            }

            if (preg_match('/[0-9@]/', $line)) {
                $score -= 35;
            }

            if (mb_strlen($candidate) > 48) {
                $score -= 20;
            }

            $candidates[] = [
                'name' => $this->beautifyName($candidate),
                'score' => $score,
            ];
        }

        $emailGuess = $email ? $this->extractNameFromEmail($email) : null;

        if ($emailGuess) {
            $candidates[] = ['name' => $emailGuess, 'score' => 72];
        }

        $filenameGuess = $filename ? $this->extractNameFromFilename($filename, $emailParts) : null;

        if ($filenameGuess) {
            $candidates[] = ['name' => $filenameGuess, 'score' => 64];
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $candidates[0]['score'] >= 42 ? $candidates[0]['name'] : null;
    }

    public function extractExperienceYears(string $normalizedText): ?float
    {
        $patterns = [
            '/(?:experience|experiences|exp|parcours)[a-z0-9\s.\-\/]{0,45}?(\d+(?:[.,]\d+)?)\s*(?:ans|annees|an|years?|yrs?)/u',
            '/(\d+(?:[.,]\d+)?)\s*(?:ans|annees|an|years?|yrs?)\s*(?:d|de|en)?\s*(?:experience|experiences|exp|parcours)/u',
            '/(?:plus de|minimum|min\.?|au moins)\s*(\d+(?:[.,]\d+)?)\s*(?:ans|annees|an|years?|yrs?)/u',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $normalizedText, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $values = [];

            foreach ($matches[1] ?? [] as $match) {
                $raw = $match[0] ?? null;
                $offset = (int) ($match[1] ?? 0);

                if ($raw === null) {
                    continue;
                }

                $context = mb_substr($normalizedText, max(0, $offset - 45), 110);

                if (preg_match('/\b(age|ne le|nee le|naissance|date de naissance|celibataire|marie|mariee|permis)\b/u', $context)) {
                    continue;
                }

                $value = (float) str_replace(',', '.', $raw);

                if ($value >= 0 && $value <= 40) {
                    $values[] = $value;
                }
            }

            if (!empty($values)) {
                return max($values);
            }
        }

        if (preg_match('/debutant|junior|entry level/u', $normalizedText)) {
            return 0.0;
        }

        return null;
    }

    public function extractLanguages(string $normalizedText): array
    {
        $results = [];

        foreach ($this->languageKeywords as $needle => $label) {
            if ($this->containsWholeTerm($normalizedText, $needle)) {
                $results[] = $label;
            }
        }

        return array_values(array_unique($results));
    }

    public function extractSkills(string $normalizedText): array
    {
        $skills = [];

        foreach ($this->skillKeywords as $skill) {
            if ($this->containsWholeTerm($normalizedText, $this->normalizeText($skill))) {
                $skills[] = $this->beautify($skill);
            }
        }

        return array_values(array_unique($skills));
    }

    private function lines(string $text): array
    {
        $lines = array_values(array_filter(array_map(
            fn ($line) => trim((string) preg_replace('/\s+/', ' ', str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $line))),
            preg_split('/\r\n|\r|\n/', $text) ?: []
        ), fn ($line) => $line !== ''));

        $maxLength = empty($lines) ? 0 : max(array_map('mb_strlen', $lines));

        if (count($lines) <= 3 || $maxLength > 220) {
            $semanticLines = $this->semanticLinesFromFlatText($text);

            if (count($semanticLines) > count($lines)) {
                return $semanticLines;
            }
        }

        return $lines;
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

    private function normalizeText(?string $text): string
    {
        $text = mb_strtolower((string) $text, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($ascii !== false) {
            $text = $ascii;
        }

        $text = preg_replace('/[^a-z0-9@+.\-\/\s]+/u', ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function beautify(string $text): string
    {
        return trim(mb_convert_case((string) preg_replace('/\s+/', ' ', $text), MB_CASE_TITLE, 'UTF-8'));
    }

    private function beautifyName(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', str_replace(['_', '.'], ' ', $text));

        return trim(mb_convert_case((string) $text, MB_CASE_TITLE, 'UTF-8'));
    }

    private function containsWholeTerm(string $haystack, string $needle): bool
    {
        return $needle !== '' && preg_match('/(^|\s)' . preg_quote($needle, '/') . '(\s|$)/u', $haystack) === 1;
    }

    private function scoreCityLine(string $line, string $lineNorm, int $index, string $matchedCity): int
    {
        $score = 12 + max(0, 46 - ($index * 2));

        if (preg_match('/\b(ville|city|adresse|address|localisation|localite|residence|residant|reside|habite|domicile|domicilie|based|coordonnees|contact)\b/u', $lineNorm)) {
            $score += 78;
        }

        if (preg_match('/\b(adresse actuelle|adresse personnelle|residence actuelle|domicilie a|domiciliee a|habite a|ville actuelle)\b/u', $lineNorm)) {
            $score += 44;
        }

        if (preg_match('/\b(maroc|morocco|ma)\b/u', $lineNorm)) {
            $score += 12;
        }

        if (preg_match('/@|\+212|00212|\b0[5-7]/u', $line)) {
            $score += 34;
        }

        if (preg_match('/\b(formation|education|etude|etudes|diplome|diplomes|baccalaureat|licence|master|universite|ecole|faculte|institut|ista|ofppt|lycee)\b/u', $lineNorm)) {
            $score -= 88;
        }

        if (preg_match('/\b(stage|experience|experiences|societe|company|entreprise|mission|missions|poste|emploi|travaille|projet|projets)\b/u', $lineNorm)) {
            $score -= 54;
        }

        if (preg_match('/\b(ne a|nee a|lieu de naissance|naissance)\b/u', $lineNorm)) {
            $score -= 24;
        }

        if (mb_strlen($lineNorm) > 110) {
            $score -= 20;
        }

        $score += intdiv(mb_strlen($matchedCity), 2);

        return $score;
    }

    private function normalizeTitleCandidate(string $line): string
    {
        $line = trim($line);
        $line = preg_replace('/\b(poste recherche|poste|fonction|intitule du poste|intitule|titre|profil|profile|headline|objective)\b\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/^(?:present|pr[ée]sent|actuellement|aujourd hui|aujourd\'hui|a ce jour|depuis|\d{4})\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/\s*\|\s*.+$/u', '', $line);
        $line = preg_replace('/\s{2,}/', ' ', $line);
        $line = $this->stripLeadingNameBeforeTitle($line);

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

        foreach ($this->titleKeywords as $keyword) {
            if ($this->containsWholeTerm($prefixNorm, $this->normalizeText($keyword))) {
                return $line;
            }
        }

        return trim(substr($line, $offset));
    }

    private function isBlockedTitleLine(string $lineNorm): bool
    {
        if ($lineNorm === '' || preg_match('/@|\+212|00212|\b0[5-7]/', $lineNorm)) {
            return true;
        }

        $words = preg_split('/\s+/', $lineNorm) ?: [];
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
            return true;
        }

        if (preg_match('/\b(inter\s*ts?|int\s*r\s*ts?|loisirs?|langues?|formations?|experiences?|competences?|connaissances?)\b/u', $lineNorm)) {
            return true;
        }

        foreach ($this->titleBlockers as $blocker) {
            if (str_contains($lineNorm, $this->normalizeText($blocker))) {
                return true;
            }
        }

        return false;
    }

    private function isLabelledTitleLine(string $lineNorm): bool
    {
        return preg_match('/\b(poste|fonction|intitule|titre|profile|headline|objective)\b/u', $lineNorm) === 1;
    }

    private function looksLikeGenericTitleLine(string $cleanLine, string $lineNorm): bool
    {
        $words = preg_split('/\s+/', trim($cleanLine)) ?: [];

        if (count($words) < 2 || count($words) > 8) {
            return false;
        }

        if (preg_match('/[0-9@]/', $cleanLine)) {
            return false;
        }

        if (mb_strlen($cleanLine) > 75) {
            return false;
        }

        foreach ($this->titleKeywords as $keyword) {
            if ($this->containsWholeTerm($lineNorm, $this->normalizeText($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function cleanupNameLine(string $line): string
    {
        $line = trim($line);
        $line = preg_replace('/\.(pdf|docx?|txt)$/i', '', $line);
        $line = preg_replace('/\b(monsieur|madame|mademoiselle|mr|mme|mlle|melle)\b\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/\b(name|nom)\b\s*[:\-]?\s*/iu', '', $line);
        $line = preg_replace('/[|\/].*$/u', '', $line);
        $line = preg_replace('/\s{2,}/', ' ', $line);

        return trim((string) $line);
    }

    private function looksLikeNameCandidate(?string $candidate, string $candidateNorm, ?string $title, ?string $city): bool
    {
        if ($candidateNorm === '' || preg_match('/@|\+212|00212|\b0[5-7]/', $candidateNorm)) {
            return false;
        }

        $words = $this->tokenizeName($candidate);

        if (count($words) < 2 || count($words) > 4) {
            return false;
        }

        if ($title && str_contains($candidateNorm, $this->normalizeText($title))) {
            return false;
        }

        if ($city && str_contains($candidateNorm, $this->normalizeText($city))) {
            return false;
        }

        foreach ($this->nameBlockers as $blocker) {
            if (str_contains($candidateNorm, $this->normalizeText($blocker))) {
                return false;
            }
        }

        $firstToken = $this->normalizeText($words[0] ?? '');

        if (in_array($firstToken, ['a', 'au', 'aux', 'de', 'du', 'des', 'en', 'et', 'pour', 'dans'], true)) {
            return false;
        }

        if (preg_match('/\b(a propos|about me|dans ce domaine|permis|mobilite|niveau|baccalaureat|ecole|lycee|statique|tournante|celibataire|marie|age|expertise|competence)\b/u', $candidateNorm)) {
            return false;
        }

        if (preg_match('/\b(de|du|des|a|au|aux|et|en)\b.*\b(de|du|des|a|au|aux|et|en)\b/u', $candidateNorm)) {
            return false;
        }

        return true;
    }

    private function isLabelledNameLine(string $lineNorm): bool
    {
        return preg_match('/\b(name|nom)\b/u', $lineNorm) === 1;
    }

    private function tokenizeName(?string $text): array
    {
        preg_match_all('/[A-Za-zÀ-ÿ\'\-]+/u', (string) $text, $matches);

        return $matches[0] ?? [];
    }

    private function tokensLookLikeProperName(array $tokens): bool
    {
        if (empty($tokens)) {
            return false;
        }

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                return false;
            }

            if (!preg_match('/^[A-Za-zÀ-ÿ\'\-]+$/u', $token)) {
                return false;
            }
        }

        return true;
    }

    private function emailNameParts(?string $email): array
    {
        if (!$email || !preg_match('/^([A-Z0-9._%+\-]+)/i', $email, $match)) {
            return [];
        }

        $local = preg_replace('/[0-9]+/', ' ', $match[1]);
        $local = str_replace(['.', '_', '-'], ' ', $local);
        $parts = preg_split('/\s+/', trim((string) $local)) ?: [];

        $parts = array_values(array_filter(array_map(fn ($part) => $this->normalizeText($part), $parts), fn ($part) => $part !== ''));

        return count($parts) === 1 ? $this->splitJoinedEmailName($parts[0]) : $parts;
    }

    private function splitJoinedEmailName(string $joined): array
    {
        if ($joined === '') {
            return [];
        }

        $firsts = $this->firstNames;
        $lasts = $this->lastNames;

        usort($firsts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        usort($lasts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($firsts as $first) {
            if ($first !== '' && str_starts_with($joined, $first)) {
                $rest = mb_substr($joined, mb_strlen($first));

                if ($rest !== '' && mb_strlen($rest) >= 3) {
                    return [$first, $rest];
                }
            }

            if ($first !== '' && str_ends_with($joined, $first)) {
                $before = mb_substr($joined, 0, mb_strlen($joined) - mb_strlen($first));

                if ($before !== '' && mb_strlen($before) >= 3) {
                    return [$before, $first];
                }
            }
        }

        foreach ($lasts as $last) {
            if ($last !== '' && str_starts_with($joined, $last)) {
                $rest = mb_substr($joined, mb_strlen($last));

                if ($rest !== '' && mb_strlen($rest) >= 3) {
                    return [$last, $rest];
                }
            }

            if ($last !== '' && str_ends_with($joined, $last)) {
                $before = mb_substr($joined, 0, mb_strlen($joined) - mb_strlen($last));

                if ($before !== '' && mb_strlen($before) >= 3) {
                    return [$before, $last];
                }
            }
        }

        return [$joined];
    }

    private function matchesEmailParts(array $tokens, array $emailParts): bool
    {
        if (empty($tokens) || empty($emailParts)) {
            return false;
        }

        $normalizedTokens = array_map(fn ($token) => $this->normalizeText($token), $tokens);

        return count(array_intersect($normalizedTokens, $emailParts)) >= min(2, count($normalizedTokens));
    }

    private function extractNameFromEmail(string $email): ?string
    {
        $parts = $this->emailNameParts($email);

        if (count($parts) < 2) {
            return null;
        }

        return implode(' ', array_map(fn ($part) => $this->beautifyName($part), array_slice($parts, 0, 4)));
    }

    private function extractNameFromFilename(string $filename, array $emailParts = []): ?string
    {
        $base = str_replace(['_', '.', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));

        if (preg_match('/^[a-f0-9]{14,}$/i', str_replace(' ', '', $base))) {
            return null;
        }

        $base = preg_replace('/\b(cv|resume|profil|profile|final|version|copie|copy|scan|pdf|docx|doc)\b/i', ' ', $base);
        $base = preg_replace('/\b\d{1,8}\b/', ' ', $base);
        $base = trim((string) preg_replace('/\s+/', ' ', $base));
        $norm = $this->normalizeText($base);

        if (!$this->looksLikeNameCandidate($base, $norm, null, null)) {
            return null;
        }

        $tokens = $this->tokenizeName($base);

        if (!$this->matchesEmailParts($tokens, $emailParts) && !$this->tokensLookLikeProperName($tokens)) {
            return null;
        }

        return $this->beautifyName($base);
    }

    private function extractTitleFromFilename(string $filename): ?string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = preg_replace('/[_\-.]+/', ' ', $base);
        $base = preg_replace('/\b(cv|resume|profil|profile|final|version|copie|copy|scan|pdf|docx|doc)\b/i', ' ', (string) $base);
        $base = trim((string) preg_replace('/\s+/', ' ', (string) $base));
        $norm = $this->normalizeText($base);

        if ($norm === '' || preg_match('/^[a-f0-9]{15,}$/i', str_replace(' ', '', $base))) {
            return null;
        }

        foreach ($this->exactTitles as $titleNorm => $displayTitle) {
            if ($this->containsWholeTerm($norm, $titleNorm)) {
                return $displayTitle;
            }
        }

        return $this->looksLikeGenericTitleLine($base, $norm) ? $this->beautify($base) : null;
    }

    private function fallbackCities(): array
    {
        return [
            'Casablanca', 'Rabat', 'Sale', 'Temara', 'Kenitra', 'Mohammedia', 'Bouskoura',
            'Nouaceur', 'Tanger', 'Tetouan', 'Marrakech', 'Agadir', 'Fes', 'Meknes',
            'El Jadida', 'Safi', 'Oujda', 'Nador', 'Laayoune', 'Dakhla', 'Beni Mellal',
            'Khouribga', 'Essaouira', 'Taroudant', 'Taza', 'Errachidia', 'Ouarzazate',
        ];
    }

    private function fallbackFirstNames(): array
    {
        return [
            'mohamed', 'mohammed', 'ahmed', 'youssef', 'hamza', 'amine', 'salma',
            'fatima', 'zineb', 'meryem', 'hajar', 'yassine', 'mehdi', 'abdessamad',
            'abdesamad', 'abdelmounaim', 'marouane', 'marwane', 'aymane', 'mohcine',
            'mouhcine', 'mohssine', 'mouhsine',
            'atika', 'hicham', 'abdeslam', 'abdelkrim', 'abdelkarim',
            'kaoutar', 'ouiam', 'nidal',
        ];
    }

    private function fallbackLastNames(): array
    {
        return ['el alami', 'bennani', 'idrissi', 'amrani', 'tazi', 'fassi', 'benali', 'bensouda', 'kadiri', 'hassani', 'wadi', 'souiba', 'labriki', 'el bouanani', 'amdou', 'yabouri', 'el hari', 'elhari', 'bani'];
    }

    private function fallbackTitles(): array
    {
        return [
            'Responsable Douane', 'Declarant en Douane', 'Agent de Transit', 'Responsable Logistique',
            'Coordinateur Logistique', 'Gestionnaire de Stock', 'Magasinier', 'Responsable Achats',
            'Acheteur', 'Assistant Achats', 'Assistant RH', 'Responsable RH', 'Charge de Recrutement',
            'Comptable', 'Assistant Comptable', 'Controleur de Gestion', 'Commercial',
            'Technico Commercial', 'Charge de Clientele', 'Teleconseiller', 'Developpeur Full Stack',
            'Developpeur Web', 'Technicien Support', 'Administrateur Systemes', 'Technicien Maintenance',
            'Electromecanicien', 'Technicien Qualite', 'Controleur Qualite', 'Controleuse Qualite',
            'Agent Qualite', 'Inspecteur Qualite', 'Technicien Controle Qualite', 'Ingenieur Qualite',
            'Ingenieur Production', 'Chef de Projet', 'Assistant Administratif', 'Receptionniste',
            'Responsable Production', 'Superviseur Production', 'Responsable Service Client',
            'Ingenieur HSE', 'Assistant QHSE', 'Responsable QHSE', 'Technicien Laboratoire',
            'Technicienne Laboratoire', 'Juriste', 'Office Manager',
            'Senior Sales Manager', 'Directeur Marketing & Commercial',
            'Manager Qualite Operationnelle', 'Chef d Equipe Logistique',
            'Responsable Moyens Generaux', 'Technicien Methodes Fabrication Mecanique',
            'Agent d Expedition', 'Coordinatrice Interne Ventes Evenements',
            'Technicienne Specialisee en Commerce', 'Agent de Securite',
        ];
    }
}
