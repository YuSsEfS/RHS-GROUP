<?php

namespace App\Services;

class CvIndexingService
{
    protected array $cities = [
        'casablanca', 'rabat', 'sale', 'temara', 'tanger', 'marrakech', 'fes',
        'meknes', 'agadir', 'kenitra', 'mohammedia', 'el jadida', 'oujda',
        'safi', 'tetouan', 'laayoune', 'beni mellal', 'berrechid', 'nouaceur',
    ];

    protected array $titlePatterns = [
        'responsable douane', 'declarant en douane', 'agent de transit',
        'assistant rh', 'responsable rh', 'charge de recrutement',
        'comptable', 'assistant comptable', 'controleur de gestion',
        'responsable achats', 'acheteur', 'assistant achats',
        'responsable logistique', 'agent logistique', 'gestionnaire de stock',
        'magasinier', 'commercial', 'technico commercial', 'charge de clientele',
        'teleconseiller', 'developpeur full stack', 'developpeur web',
        'technicien support', 'administrateur systemes', 'technicien maintenance',
        'electromecanicien', 'technicien qualite', 'controleur qualite',
        'ingenieur qualite', 'ingenieur production', 'chef de projet',
        'assistant administratif', 'receptionniste',
    ];

    protected array $skillKeywords = [
        'excel', 'word', 'power bi', 'sap', 'sage', 'odoo', 'sql', 'php', 'laravel',
        'javascript', 'react', 'vue', 'docker', 'git', 'recrutement', 'sourcing',
        'paie', 'comptabilite', 'facturation', 'recouvrement', 'import export',
        'douane', 'transit', 'logistique', 'supply chain', 'stock', 'crm',
        'service client', 'haccp', 'iso 9001', 'maintenance', 'automatisme',
        'autocad', 'solidworks', 'canva', 'photoshop', 'anglais', 'francais',
        'arabe', 'espagnol',
    ];

    protected array $languageKeywords = [
        'francais' => 'Francais',
        'anglais' => 'Anglais',
        'english' => 'Anglais',
        'arabe' => 'Arabe',
        'arabic' => 'Arabe',
        'espagnol' => 'Espagnol',
        'spanish' => 'Espagnol',
        'allemand' => 'Allemand',
        'german' => 'Allemand',
        'italien' => 'Italien',
        'italian' => 'Italien',
    ];

    public function buildStructuredProfile(string $text, array $seed = [], ?string $filename = null): array
    {
        $normalized = $this->normalizeText($text);
        $lines = $this->lines($text);

        $name = $seed['full_name'] ?? $this->extractName($text, $lines, $seed['email'] ?? null, $filename);
        $email = $seed['email'] ?? $this->extractEmail($text);
        $phone = $seed['phone'] ?? $this->extractPhone($text);
        $city = $seed['city'] ?? $this->extractCity($normalized);
        $title = $seed['title'] ?? $this->extractTitle($normalized, $lines, $filename);

        return array_filter([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'title' => $title,
            'headline' => $title,
            'desired_position' => $title,
            'years_experience' => $this->extractExperienceYears($normalized),
            'languages' => $this->extractLanguages($normalized),
            'technical_skills' => $this->extractSkills($normalized),
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

    public function extractCity(string $normalizedText): ?string
    {
        foreach ($this->cities as $city) {
            if (preg_match('/(^|\s)' . preg_quote($city, '/') . '(\s|$)/u', $normalizedText)) {
                return $this->beautify($city);
            }
        }

        return null;
    }

    public function extractTitle(string $normalizedText, array $lines = [], ?string $filename = null): ?string
    {
        $candidates = [];

        foreach (array_slice($lines, 0, 18) as $index => $line) {
            $lineNorm = $this->normalizeText($line);

            foreach ($this->titlePatterns as $pattern) {
                if (preg_match('/(^|\s)' . preg_quote($pattern, '/') . '(\s|$)/u', $lineNorm)) {
                    $candidates[] = ['title' => $this->beautify($pattern), 'score' => 100 - $index];
                }
            }
        }

        foreach ($this->titlePatterns as $pattern) {
            if (preg_match('/(^|\s)' . preg_quote($pattern, '/') . '(\s|$)/u', $normalizedText)) {
                $candidates[] = ['title' => $this->beautify($pattern), 'score' => 70];
            }
        }

        if ($filename) {
            $fileNorm = $this->normalizeText(pathinfo($filename, PATHINFO_FILENAME));

            foreach ($this->titlePatterns as $pattern) {
                if (str_contains($fileNorm, $pattern)) {
                    $candidates[] = ['title' => $this->beautify($pattern), 'score' => 55];
                }
            }
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $candidates[0]['title'] ?? null;
    }

    public function extractName(string $text, array $lines = [], ?string $email = null, ?string $filename = null): ?string
    {
        foreach (array_slice($lines, 0, 10) as $line) {
            if ($line === '' || mb_strlen($line) < 5 || mb_strlen($line) > 70) {
                continue;
            }

            if (preg_match('/@|\+212|00212|curriculum|vitae|experience|formation|profil|competences/i', $line)) {
                continue;
            }

            preg_match_all('/[A-Za-zÀ-ÿ\'\-]+/u', $line, $tokens);
            $tokens = $tokens[0] ?? [];

            if (count($tokens) >= 2 && count($tokens) <= 4) {
                $candidate = implode(' ', array_slice($tokens, 0, 4));

                if (!$this->looksLikeTitle($this->normalizeText($candidate))) {
                    return $this->beautify($candidate);
                }
            }
        }

        if ($email && preg_match('/^([A-Z0-9._%+\-]+)/i', $email, $m)) {
            return $this->beautify(str_replace(['.', '_', '-'], ' ', $m[1]));
        }

        if ($filename) {
            $base = str_replace(['_', '.', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
            $base = preg_replace('/\b(cv|resume|profil|profile|final|version|pdf|docx|doc)\b/i', ' ', $base);
            $base = trim(preg_replace('/\s+/', ' ', $base));

            if ($base !== '' && !$this->looksLikeTitle($this->normalizeText($base))) {
                return $this->beautify($base);
            }
        }

        return null;
    }

    public function extractExperienceYears(string $normalizedText): ?float
    {
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:ans|annees|years?)/u', $normalizedText, $matches)) {
            return (float) max(array_map(fn ($value) => (float) str_replace(',', '.', $value), $matches[1]));
        }

        if (preg_match('/debutant|junior/u', $normalizedText)) {
            return 0.0;
        }

        return null;
    }

    public function extractLanguages(string $normalizedText): array
    {
        $results = [];

        foreach ($this->languageKeywords as $needle => $label) {
            if (preg_match('/(^|\s)' . preg_quote($needle, '/') . '(\s|$)/u', $normalizedText)) {
                $results[] = $label;
            }
        }

        return array_values(array_unique($results));
    }

    public function extractSkills(string $normalizedText): array
    {
        $skills = [];

        foreach ($this->skillKeywords as $skill) {
            if (preg_match('/(^|\s)' . preg_quote($skill, '/') . '(\s|$)/u', $normalizedText)) {
                $skills[] = $this->beautify($skill);
            }
        }

        return array_values(array_unique($skills));
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map(
            fn ($line) => trim((string) preg_replace('/\s+/', ' ', $line)),
            preg_split('/\r\n|\r|\n/', $text) ?: []
        )));
    }

    private function looksLikeTitle(string $normalizedText): bool
    {
        foreach ($this->titlePatterns as $pattern) {
            if (str_contains($normalizedText, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(?string $text): string
    {
        $text = mb_strtolower((string) $text, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($ascii !== false) {
            $text = $ascii;
        }

        $text = preg_replace('/[^a-z0-9@+.\-\s]+/u', ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function beautify(string $text): string
    {
        return trim(mb_convert_case((string) preg_replace('/\s+/', ' ', $text), MB_CASE_TITLE, 'UTF-8'));
    }
}
