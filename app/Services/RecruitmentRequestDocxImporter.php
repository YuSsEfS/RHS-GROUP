<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use ZipArchive;

class RecruitmentRequestDocxImporter
{
    private array $labelMap = [
        'reference' => [
            'Affaire N°',
            'Affaire No',
            'Référence',
            'Reference',
        ],
        'client_name' => [
            'Client / Demandeur',
            'Client/Demandeur',
            'Client',
            'Demandeur',
            'Société',
            'Entreprise',
            'Nom du client',
        ],
        'request_date' => [
            'Date Demande',
            'Date de demande',
            'Date',
        ],
        'position_title' => [
            'Poste à pourvoir',
            'Définition du poste à pourvoir',
            'Intitulé du poste',
            'Position',
            'Fonction',
        ],
        'work_location' => [
            'Lieu de travail',
            'Lieu',
            'Localisation',
        ],
        'recruitment_reason' => [
            'Motif de recrutement',
            'Motif',
        ],
        'age' => [
            'Âge',
            'Age',
        ],
        'gender' => [
            'Sexe',
            'Genre',
        ],
        'education' => [
            'Formation',
            'Niveau d’étude',
            "Niveau d'étude",
            "Niveau d'etude",
            'Diplôme',
            'Diplome',
            'Education',
        ],
        'experience_years' => [
            'Expérience professionnelle',
            'Experience professionnelle',
            'Expérience',
            'Experience',
            'Nombre d’années d’expérience',
            "Nombre d'annees d'experience",
            'Années d’expérience',
            "Annees d'experience",
        ],
        'availability' => [
            'Disponibilité',
            'Disponibilite',
        ],
        'languages' => [
            'Langues (Lues, Ecrites, Parlées)',
            'Langues (Lues, Ecrites, Parlées )',
            'Langues (Lues, Ecrites, Parlées):',
            'Langues',
        ],
        'budget_type' => [
            'Budget du poste',
            'Type de budget',
            'Budget',
        ],
        'monthly_salary' => [
            'Rémunération mensuelle prévue/proposée',
            'Remuneration mensuelle prevue/proposee',
            'Salaire mensuel',
            'Salaire',
            'Rémunération',
            'Remuneration',
        ],
        'contract_type' => [
            'Type de contrat',
            'Contrat',
        ],
        'planned_start_date' => [
            'Date prévue de démarrage',
            'Date prevue de demarrage',
            'Date de démarrage',
            'Date de demarrage',
            'Démarrage',
            'Demarrage',
        ],
        'missions' => [
            'Missions et tâches globales',
            'Missions et taches globales',
            'Missions et tâches',
            'Missions et taches',
            'Missions',
        ],
        'personal_qualities' => [
            'Qualités personnelles',
            'Qualites personnelles',
            'Qualités',
            'Qualites',
            'Compétences comportementales',
            'Competences comportementales',
        ],
        'specific_knowledge' => [
            'Connaissances spécifiques requises pour le poste',
            'Connaissances specifiques requises pour le poste',
            'Connaissances spécifiques',
            'Connaissances specifiques',
            'Connaissances',
            'Compétences spécifiques',
            'Competences specifiques',
        ],
        'other_benefits' => [
            'Autres avantages',
            'Avantages',
        ],
    ];

    private array $noisePatterns = [
        '/^DEMANDE DE RECRUTEMENT$/iu',
        '/^Identification\s*:?\s*$/iu',
        '/^FOR[-\sA-Z0-9\/]+$/iu',
        '/^Datte Effective\s*:?\s*$/iu',
        '/^Date Effective\s*:?\s*$/iu',
        '/^\d{2}\/\d{2}\/\d{4}$/u',
        '/^Page\s+\d+\s*\/\s*\d+$/iu',
        '/^DEMANDE DE RECRUTEMENT\s*\|.*$/iu',
        '/^P$/u',
        '/^age\s+1\/2$/iu',
        '/^Visa Demandeur \/ Client.*$/iu',
        '/^Visa Chargé de recrutement.*$/iu',
        '/^Visa Charge de recrutement.*$/iu',
    ];

    public function import(string $path): array
    {
        $rawText = $this->extractDocxText($path);
        $preparedText = $this->prepareTextForParsing($rawText);
        $mapped = $this->mapRecruitmentRequest($preparedText);

        return [
            'raw_text' => $preparedText,
            'mapped' => $mapped,
        ];
    }

    private function extractDocxText(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $parts = [
            'word/document.xml',
            'word/header1.xml',
            'word/header2.xml',
            'word/header3.xml',
            'word/footer1.xml',
            'word/footer2.xml',
            'word/footer3.xml',
        ];

        $chunks = [];

        foreach ($parts as $part) {
            $index = $zip->locateName($part);

            if ($index === false) {
                continue;
            }

            $xmlContent = $zip->getFromIndex($index);

            if (!is_string($xmlContent) || trim($xmlContent) === '') {
                continue;
            }

            $text = $this->extractTextFromWordXml($xmlContent);

            if ($text !== '') {
                $chunks[] = $text;
            }
        }

        $zip->close();

        return $this->normalizeText(implode("\n\n", $chunks));
    }

    private function extractTextFromWordXml(string $xmlContent): string
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xmlContent);
        libxml_clear_errors();

        if (!$loaded) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $lines = [];

        foreach ($xpath->query('//w:p') as $paragraph) {
            $texts = [];

            foreach ($xpath->query('.//w:t', $paragraph) as $textNode) {
                $texts[] = $textNode->textContent;
            }

            $line = $this->cleanExtractedLine(implode('', $texts));

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        foreach ($xpath->query('//w:tbl') as $table) {
            foreach ($xpath->query('.//w:tr', $table) as $row) {
                $cells = [];

                foreach ($xpath->query('./w:tc', $row) as $cell) {
                    $cellTexts = [];

                    foreach ($xpath->query('.//w:t', $cell) as $textNode) {
                        $cellTexts[] = $textNode->textContent;
                    }

                    $cellText = $this->cleanExtractedLine(implode(' ', $cellTexts));

                    if ($cellText !== '') {
                        $cells[] = $cellText;
                    }
                }

                if (!empty($cells)) {
                    $lines[] = implode(' | ', $cells);
                }
            }
        }

        $lines = array_map(fn ($line) => $this->normalizeInlineSpacing($line), $lines);
        $lines = $this->deduplicateLines($lines);

        return $this->normalizeText(implode("\n", $lines));
    }

    private function prepareTextForParsing(string $text): string
    {
        $text = $this->normalizeText($text);

        $replacements = [
            "/Client\s*\/\s*\n\s*Demandeur/iu" => "Client / Demandeur",
            "/Définition du\s*\n\s*poste à pourvoir/iu" => "Définition du poste à pourvoir",
            "/Definition du\s*\n\s*poste à pourvoir/iu" => "Définition du poste à pourvoir",
            "/Missions et\s*\n\s*tâches globales/iu" => "Missions et tâches globales",
            "/Missions et\s*\n\s*taches globales/iu" => "Missions et tâches globales",
            "/Connaissances spécifiques requises pour le\s*\n\s*poste/iu" => "Connaissances spécifiques requises pour le poste",
            "/Connaissances specifiques requises pour le\s*\n\s*poste/iu" => "Connaissances spécifiques requises pour le poste",
            "/Type de\s*\n\s*contrat/iu" => "Type de contrat",
            "/Date prévue de\s*\n\s*démarrage/iu" => "Date prévue de démarrage",
            "/Date prevue de\s*\n\s*demarrage/iu" => "Date prévue de démarrage",
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        $lines = preg_split('/\n+/u', $text) ?: [];
        $prepared = [];

        foreach ($lines as $line) {
            $line = $this->cleanExtractedLine($line);

            if ($line === '') {
                continue;
            }

            if ($this->isNoiseLine($line)) {
                continue;
            }

            $prepared[] = $line;
        }

        $prepared = $this->deduplicateLines($prepared);

        return $this->normalizeText(implode("\n", $prepared));
    }

    private function mapRecruitmentRequest(string $text): array
    {
        $languages = $this->extractLanguages($text);

        $mapped = [
            'reference' => $this->extractField($text, 'reference'),
            'client_name' => $this->extractField($text, 'client_name'),
            'request_date' => $this->extractField($text, 'request_date'),
            'position_title' => $this->extractField($text, 'position_title'),
            'work_location' => $this->extractField($text, 'work_location'),
            'recruitment_reason' => $this->extractRecruitmentReason($text),
            'age' => $this->extractField($text, 'age'),
            'gender' => $this->extractField($text, 'gender'),
            'education' => $this->extractField($text, 'education'),
            'experience_years' => $this->extractField($text, 'experience_years'),
            'availability' => $this->extractField($text, 'availability'),
            'other_language' => $languages['other_language'],
            'lang_ar' => $languages['lang_ar'],
            'lang_fr' => $languages['lang_fr'],
            'lang_en' => $languages['lang_en'],
            'lang_es' => $languages['lang_es'],
            'budget_type' => $this->extractBudgetType($text),
            'monthly_salary' => $this->extractField($text, 'monthly_salary'),
            'contract_type' => $this->extractField($text, 'contract_type'),
            'planned_start_date' => $this->extractField($text, 'planned_start_date'),
            'missions' => $this->extractBlock($text, 'missions'),
            'personal_qualities' => $this->extractBlock($text, 'personal_qualities'),
            'specific_knowledge' => $this->extractBlock($text, 'specific_knowledge'),
            'other_benefits' => $this->extractBlock($text, 'other_benefits'),
        ];

        return $this->postProcessMappedData($mapped);
    }

    private function extractField(string $text, string $fieldKey): string
    {
        $lines = preg_split('/\n+/u', $text) ?: [];
        $labels = $this->labelMap[$fieldKey] ?? [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            foreach ($labels as $label) {
                $value = $this->extractInlineFieldValue($line, $label, $fieldKey);

                if ($value !== '') {
                    return $this->normalizeFieldValue($fieldKey, $value);
                }

                if ($this->lineIsLabelOnly($line, $label)) {
                    for ($j = $index + 1; $j < count($lines); $j++) {
                        $next = trim($lines[$j]);

                        if ($next === '') {
                            continue;
                        }

                        if ($this->looksLikeAnyKnownLabel($next)) {
                            break;
                        }

                        $next = $this->sanitizeExtractedValue($next, $fieldKey);

                        if ($next !== '') {
                            return $this->normalizeFieldValue($fieldKey, $next);
                        }
                    }
                }
            }
        }

        return '';
    }

    private function extractBlock(string $text, string $fieldKey): string
    {
        $lines = preg_split('/\n+/u', $text) ?: [];
        $labels = $this->labelMap[$fieldKey] ?? [];
        $allLabels = $this->getAllLabels();

        foreach ($lines as $index => $line) {
            $line = trim($line);

            foreach ($labels as $label) {
                if (!$this->lineStartsWithLabel($line, $label)) {
                    continue;
                }

                $parts = [];
                $inline = $this->extractInlineFieldValue($line, $label, $fieldKey);

                if ($inline !== '') {
                    $parts[] = $inline;
                }

                for ($j = $index + 1; $j < count($lines); $j++) {
                    $candidate = trim($lines[$j]);

                    if ($candidate === '') {
                        continue;
                    }

                    if ($this->matchesAnyLabel($candidate, $allLabels)) {
                        break;
                    }

                    $candidate = $this->sanitizeExtractedValue($candidate, $fieldKey);

                    if ($candidate !== '') {
                        $parts[] = $candidate;
                    }
                }

                $value = trim(implode("\n", $parts));
                $value = $this->normalizeFieldValue($fieldKey, $value);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function extractLanguages(string $text): array
    {
        $labels = $this->labelMap['languages'] ?? [];
        $lines = preg_split('/\n+/u', $text) ?: [];
        $languageLine = '';

        foreach ($lines as $index => $line) {
            foreach ($labels as $label) {
                $value = $this->extractInlineFieldValue($line, $label, 'languages');

                if ($value !== '') {
                    $languageLine = $value;
                    break 2;
                }

                if ($this->lineIsLabelOnly($line, $label)) {
                    $buffer = [];

                    for ($j = $index + 1; $j < count($lines); $j++) {
                        $next = trim($lines[$j]);

                        if ($next === '') {
                            continue;
                        }

                        if ($this->looksLikeAnyKnownLabel($next)) {
                            break;
                        }

                        $next = $this->sanitizeExtractedValue($next, 'languages');

                        if ($next !== '') {
                            $buffer[] = $next;
                        }
                    }

                    if (!empty($buffer)) {
                        $languageLine = implode(' ', $buffer);
                        break 2;
                    }
                }
            }
        }

        $source = $languageLine !== '' ? $languageLine : $text;

        $langAr = (bool) preg_match('/(^|\W)(arabe|arabic)($|\W)/iu', $source);
        $langFr = (bool) preg_match('/(^|\W)(français|francais|french)($|\W)/iu', $source);
        $langEn = (bool) preg_match('/(^|\W)(anglais|english)($|\W)/iu', $source);
        $langEs = (bool) preg_match('/(^|\W)(espagnol|spanish)($|\W)/iu', $source);

        $other = '';
        if (preg_match('/Autre\s*[:\-]?\s*(.+)$/iu', $source, $m)) {
            $other = $this->sanitizeExtractedValue($m[1] ?? '', 'other_language');
        }

        return [
            'lang_ar' => $langAr,
            'lang_fr' => $langFr,
            'lang_en' => $langEn,
            'lang_es' => $langEs,
            'other_language' => $other,
        ];
    }

    private function extractRecruitmentReason(string $text): string
    {
        $value = $this->extractField($text, 'recruitment_reason');

        if ($value !== '') {
            return trim(preg_replace('/\s{2,}/u', ' ', $value));
        }

        if (preg_match('/Motif de recrutement\s*:?\s*(.+?)(?:\n|$)/iu', $text, $m)) {
            return trim(preg_replace('/\s{2,}/u', ' ', $m[1]));
        }

        return '';
    }

    private function extractBudgetType(string $text): string
    {
        $lines = preg_split('/\n+/u', $text) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if (!preg_match('/Budget du poste/iu', $line)) {
                continue;
            }

            $candidateLines = [$line];

            for ($j = $index + 1; $j < min($index + 4, count($lines)); $j++) {
                $next = trim($lines[$j]);

                if ($next === '') {
                    continue;
                }

                if ($this->looksLikeAnyKnownLabel($next) && !preg_match('/Poste\s+(?:non\s+)?budg/iu', $next)) {
                    break;
                }

                $candidateLines[] = $next;
            }

            $source = mb_strtolower(implode(' ', $candidateLines));

            $hasNon = str_contains($source, 'poste non budgété') || str_contains($source, 'poste non budgete');
            $hasYes = preg_match('/\bposte budgété\b/iu', implode(' ', $candidateLines)) === 1
                || preg_match('/\bposte budgete\b/iu', implode(' ', $candidateLines)) === 1;

            if ($hasNon && !$hasYes) {
                return 'Poste non budgété';
            }

            if ($hasYes && !$hasNon) {
                return 'Poste budgété';
            }

            if ($hasNon && $hasYes) {
                return '';
            }
        }

        return '';
    }

    private function postProcessMappedData(array $mapped): array
    {
        foreach ($mapped as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $mapped[$key] = $this->normalizeFieldValue($key, $value);
        }

        if (($mapped['position_title'] ?? '') !== '') {
            $mapped['position_title'] = preg_replace(
                '/^(?:poste\s+à\s+pourvoir|poste\s+a\s+pourvoir|définition\s+du\s+poste\s+à\s+pourvoir|definition\s+du\s+poste\s+a\s+pourvoir|à\s+pourvoir|a\s+pourvoir)\s*[:\-]?\s*/iu',
                '',
                $mapped['position_title']
            );
            $mapped['position_title'] = trim($mapped['position_title']);
        }

        if (($mapped['monthly_salary'] ?? '') !== '') {
            $mapped['monthly_salary'] = preg_replace(
                '/^(?:rémunération\s+mensuelle\s+prévue\/proposée|remuneration\s+mensuelle\s+prevue\/proposee|mensuelle\s+prévue\/proposée|mensuelle\s+prevue\/proposee)\s*[:\-]?\s*/iu',
                '',
                $mapped['monthly_salary']
            );
            $mapped['monthly_salary'] = trim($mapped['monthly_salary']);
        }

        if (($mapped['age'] ?? '') === 'ASAP') {
            $mapped['age'] = '';
        }

        return $mapped;
    }

    private function normalizeFieldValue(string $fieldKey, string $value): string
    {
        $value = $this->sanitizeExtractedValue($value, $fieldKey);

        if ($value === '') {
            return '';
        }

        if (in_array($fieldKey, ['missions', 'personal_qualities', 'specific_knowledge', 'other_benefits'], true)) {
            $lines = preg_split('/\n+/u', $value) ?: [];
            $lines = array_map(fn ($line) => trim(preg_replace('/\s+/u', ' ', $line)), $lines);
            $lines = array_filter($lines, fn ($line) => $line !== '');
            return trim(implode("\n", $lines));
        }

        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        return match ($fieldKey) {
            'age' => $this->normalizeAge($value),
            'gender' => $this->normalizeGender($value),
            'planned_start_date' => $this->normalizePlannedStartDate($value),
            'request_date' => $this->normalizeRequestDate($value),
            'budget_type' => $this->normalizeBudgetType($value),
            default => $value,
        };
    }

    private function normalizeAge(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        if (in_array($lower, ['n/a', 'na', 'néant', 'neant', 'aucun', 'aucune', 'non', ''], true)) {
            return '';
        }

        return $value;
    }

    private function normalizeGender(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        if (preg_match('/\b(h\/f|f\/h)\b/iu', $value)) {
            return 'H/F';
        }

        if (in_array($lower, ['homme', 'h'], true)) {
            return 'H';
        }

        if (in_array($lower, ['femme', 'f'], true)) {
            return 'F';
        }

        if (in_array($lower, ['n/a', 'na', 'néant', 'neant', 'aucun', 'aucune', 'non', ''], true)) {
            return '';
        }

        return $value;
    }

    private function normalizePlannedStartDate(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        if (in_array($lower, ['asap', 'immédiat', 'immediat', 'immédiate', 'immediate'], true)) {
            return 'ASAP';
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            [$d, $m, $y] = explode('/', $value);
            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $value;
    }

    private function normalizeRequestDate(string $value): string
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            [$d, $m, $y] = explode('/', $value);
            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $value;
    }

    private function normalizeBudgetType(string $value): string
    {
        $lower = mb_strtolower($value);

        if (str_contains($lower, 'non budg')) {
            return 'Poste non budgété';
        }

        if (preg_match('/\bposte budg[ée]té\b/iu', $value) || str_contains($lower, 'poste budgete')) {
            return 'Poste budgété';
        }

        return $value;
    }

    private function extractInlineFieldValue(string $line, string $label, ?string $fieldKey = null): string
    {
        $labelPattern = preg_quote($label, '/');

        $nextLabels = array_filter(
            $this->getAllLabelsSortedByLength(),
            fn ($known) => !$this->sameLooseLabel($known, $label)
        );

        $nextLabelRegex = implode(
            '|',
            array_map(fn ($l) => preg_quote($l, '/'), $nextLabels)
        );

        $patterns = [
            '/(?:^|\s)' . $labelPattern . '\s*[:|]\s*(.*?)(?=\s+(?:' . $nextLabelRegex . ')\s*[:|]|\s*$)/iu',
            '/(?:^|\s)' . $labelPattern . '\s*[-–—]\s*(.*?)(?=\s+(?:' . $nextLabelRegex . ')\s*[:|]|\s*$)/iu',
            '/(?:^|\s)' . $labelPattern . '\s*\.{2,}\s*(.*?)(?=\s+(?:' . $nextLabelRegex . ')\s*[:|]|\s*$)/iu',
            '/(?:^|\s)' . $labelPattern . '\s*…+\s*(.*?)(?=\s+(?:' . $nextLabelRegex . ')\s*[:|]|\s*$)/iu',
            '/(?:^|\s)' . $labelPattern . '\s+(.+?)(?=\s+(?:' . $nextLabelRegex . ')\s*[:|]|\s*$)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $value = $this->sanitizeExtractedValue($matches[1] ?? '', $fieldKey);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        if (str_contains($line, '|')) {
            $parts = preg_split('/\s*\|\s*/u', $line) ?: [];

            if (count($parts) >= 2) {
                $first = trim($parts[0]);

                if ($this->sameLooseLabel($first, $label)) {
                    $value = $this->sanitizeExtractedValue(implode(' | ', array_slice($parts, 1)), $fieldKey);

                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        return '';
    }

    private function extractValueFromLine(string $line, string $label, ?string $fieldKey = null): string
    {
        return $this->extractInlineFieldValue($line, $label, $fieldKey);
    }

    private function sanitizeExtractedValue(string $value, ?string $fieldKey): string
    {
        $value = $this->cleanValue($value);

        $value = preg_replace('/^\s*[:|\-–—_…\.]+\s*/u', '', $value);
        $value = preg_replace('/\s*[:|\-–—_…\.]+\s*$/u', '', $value);
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[\.\-–—_…\s]+$/u', $value)) {
            return '';
        }

        if (preg_match('/^\.+$/u', str_replace(' ', '', $value))) {
            return '';
        }

        if (preg_match('/^…+$/u', str_replace(' ', '', $value))) {
            return '';
        }

        $lower = mb_strtolower($value);

        $emptyTokens = [
            '',
            'a promouvoir',
            'à promouvoir',
            'a confirmer',
            'à confirmer',
            'n a',
            'n/a',
            'na',
            'néant',
            'neant',
            'ras',
            'aucun',
            'aucune',
            'non',
            'vide',
            'null',
            '---',
            '...',
            '........',
            '...........',
            '…………',
        ];

        if (in_array($lower, $emptyTokens, true)) {
            return '';
        }

        if (preg_match('/^(?:\.{2,}|…+)\s*\p{L}/u', $value)) {
            return '';
        }

        foreach ($this->getAllLabels() as $label) {
            if ($this->sameLooseLabel($value, $label)) {
                return '';
            }
        }

        foreach ($this->getAllLabelsSortedByLength() as $knownLabel) {
            $pattern = '/^(.*?)\s+(?=' . preg_quote($knownLabel, '/') . '\s*(?:[:|]|\.{2,}|…+|-|–|—)?)/iu';
            if (preg_match($pattern, $value, $m)) {
                $candidate = trim($m[1]);
                if ($candidate !== '') {
                    $value = $candidate;
                }
            }
        }

        if ($fieldKey === 'position_title') {
            $value = preg_replace('/^(?:poste\s+à\s+pourvoir|definition\s+du\s+poste\s+à\s+pourvoir|définition\s+du\s+poste\s+à\s+pourvoir|à\s+pourvoir|a\s+pourvoir)\s*[:\-]?\s*/iu', '', $value);
        }

        if ($fieldKey === 'monthly_salary') {
            $value = preg_replace('/^(?:rémunération\s+mensuelle\s+prévue\/proposée|remuneration\s+mensuelle\s+prevue\/proposee|mensuelle\s+prévue\/proposée|mensuelle\s+prevue\/proposee)\s*[:\-]?\s*/iu', '', $value);
        }

        if ($fieldKey === 'other_language') {
            $value = preg_replace('/^(?:autre)\s*[:\-]?\s*/iu', '', $value);
        }

        if ($fieldKey === 'client_name' && $value === '/') {
            return '';
        }

        return trim($value);
    }

    private function cleanExtractedLine(string $line): string
    {
        $line = str_replace("\xc2\xa0", ' ', $line);
        $line = html_entity_decode($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $line = preg_replace('/[ \t]+/u', ' ', $line);
        $line = preg_replace('/\.{3,}/u', ' ........ ', $line);
        $line = preg_replace('/…{2,}/u', ' ........ ', $line);
        $line = preg_replace('/\s+/u', ' ', $line);

        return trim($line);
    }

    private function cleanValue(string $value): string
    {
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xc2\xa0", ' ', $value);

        return trim($value);
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace("/\r\n|\r/u", "\n", $text);
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    private function normalizeInlineSpacing(string $text): string
    {
        $text = preg_replace('/\s*[:]\s*/u', ': ', $text);
        $text = preg_replace('/\s*[|]\s*/u', ' | ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function deduplicateLines(array $lines): array
    {
        $seen = [];
        $result = [];

        foreach ($lines as $line) {
            $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $line)));

            if ($normalized === '') {
                continue;
            }

            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $result[] = trim($line);
        }

        return $result;
    }

    private function isNoiseLine(string $line): bool
    {
        foreach ($this->noisePatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    private function lineIsLabelOnly(string $line, string $label): bool
    {
        $normalizedLine = $this->normalizeLooseLabel($line);
        $normalizedLabel = $this->normalizeLooseLabel($label);

        if ($normalizedLine === $normalizedLabel) {
            return true;
        }

        return preg_match('/^\s*' . preg_quote($label, '/') . '\s*(?:[:|]|\.{2,}|…+|-|–|—)?\s*$/iu', $line) === 1;
    }

    private function lineStartsWithLabel(string $line, string $label): bool
    {
        if ($this->lineIsLabelOnly($line, $label)) {
            return true;
        }

        return preg_match('/^\s*' . preg_quote($label, '/') . '\s*(?:[:|]|\.{2,}|…+|-|–|—|\s)/iu', $line) === 1;
    }

    private function looksLikeAnyKnownLabel(string $line): bool
    {
        foreach ($this->getAllLabels() as $label) {
            if ($this->lineStartsWithLabel($line, $label) || $this->sameLooseLabel($line, $label)) {
                return true;
            }
        }

        return false;
    }

    private function matchesAnyLabel(string $line, array $labels): bool
    {
        foreach ($labels as $label) {
            if ($this->lineStartsWithLabel($line, $label) || $this->sameLooseLabel($line, $label)) {
                return true;
            }
        }

        return false;
    }

    private function getAllLabels(): array
    {
        $all = [];

        foreach ($this->labelMap as $labels) {
            foreach ($labels as $label) {
                $all[] = $label;
            }
        }

        return array_values(array_unique($all));
    }

    private function getAllLabelsSortedByLength(): array
    {
        $labels = $this->getAllLabels();
        usort($labels, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        return $labels;
    }

    private function normalizeLooseLabel(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s\/]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function sameLooseLabel(string $a, string $b): bool
    {
        return $this->normalizeLooseLabel($a) === $this->normalizeLooseLabel($b);
    }
}