<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAiRecruitmentService
{
    protected $client = null;
    protected string $model;
    protected bool $configured = false;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        $this->model = (string) config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'));

        if (!$apiKey) {
            Log::info('OpenAI disabled for recruitment services: missing API key.', [
                'model' => $this->model,
            ]);

            return;
        }

        $this->client = \OpenAI::client($apiKey);
        $this->configured = true;
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function normalizeRequest(array $requestData): array
    {
        if (!$this->configured) {
            return [];
        }

        return $this->callWithRetry(function () use ($requestData) {
            $messages = [
                [
                    'role' => 'system',
                    'content' => <<<TXT
Tu es un assistant RH expert.

Transforme une demande de recrutement en JSON strict avec EXACTEMENT ces clés :
role, must_have_skills, nice_to_have_skills, education, min_experience_years, languages, location, availability, contract_type, soft_skills, mission_keywords.

Retourne uniquement du JSON valide.
TXT
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($requestData, JSON_UNESCAPED_UNICODE),
                ],
            ];

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'temperature' => 0.1,
                'messages' => $messages,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');
            $data = $this->extractJson($content);

            return is_array($data) ? $data : [];
        }, 'normalizeRequest');
    }

    public function structureCv(string $cvText): array
    {
        if (!$this->configured || trim($cvText) === '') {
            return [];
        }

        return $this->callWithRetry(function () use ($cvText) {
            $messages = [
                [
                    'role' => 'system',
                    'content' => <<<TXT
Tu es un expert en analyse de CV.

Retourne uniquement un JSON strict avec ces clés :
full_name, email, phone, title, headline, desired_position, years_experience, education, languages, technical_skills, soft_skills, industries, certifications, location, city, availability, summary.

Retourne uniquement du JSON valide.
TXT
                ],
                [
                    'role' => 'user',
                    'content' => mb_substr($cvText, 0, 12000),
                ],
            ];

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'temperature' => 0.1,
                'messages' => $messages,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');
            $data = $this->extractJson($content);

            return is_array($data) ? $data : [];
        }, 'structureCv');
    }

    private function callWithRetry(callable $callback, string $context): array
    {
        $attempts = 3;
        $lastException = null;

        for ($try = 1; $try <= $attempts; $try++) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;
                $isRateLimit = str_contains(mb_strtolower($e->getMessage()), 'rate limit');

                Log::warning($context . ' retry', [
                    'try' => $try,
                    'message' => $e->getMessage(),
                    'model' => $this->model,
                ]);

                if ($isRateLimit && $try < $attempts) {
                    sleep($try * 3);
                    continue;
                }

                break;
            }
        }

        Log::error($context . ' failed', [
            'message' => $lastException?->getMessage(),
            'model' => $this->model,
        ]);

        return [];
    }

    private function extractJson(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
