<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooService
{
    protected string $url;

    protected string $db;

    protected string $username;

    protected string $password;

    protected ?int $uid = null;

    public function __construct()
    {
        $this->url = rtrim((string) config('odoo.url'), '/');
        $this->db = (string) config('odoo.db');
        $this->username = (string) config('odoo.username');
        $this->password = (string) config('odoo.api_key');
    }

    public function authenticate(): ?int
    {
        if ($this->uid) {
            return $this->uid;
        }

        try {
            $response = $this->httpClient()->post($this->url . '/jsonrpc', [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => 'common',
                    'method' => 'authenticate',
                    'args' => [
                        $this->db,
                        $this->username,
                        $this->password,
                        [],
                    ],
                ],
                'id' => random_int(1, 999999),
            ]);

            $json = $response->json();

            $this->uid = !empty($json['result']) ? (int) $json['result'] : null;

            return $this->uid;
        } catch (\Throwable $e) {
            Log::error('Odoo auth failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function call(string $model, string $method, array $args = [], array $kwargs = [])
    {
        $uid = $this->authenticate();

        if (!$uid) {
            throw new \RuntimeException('Odoo authentication failed');
        }

        $response = $this->httpClient()->post($this->url . '/jsonrpc', [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'service' => 'object',
                'method' => 'execute_kw',
                'args' => [
                    $this->db,
                    $uid,
                    $this->password,
                    $model,
                    $method,
                    $args,
                    $kwargs,
                ],
            ],
            'id' => random_int(1, 999999),
        ]);

        $json = $response->json();

        if (isset($json['error'])) {
            Log::error('Odoo API error', [
                'model' => $model,
                'method' => $method,
                'error' => $json['error'],
            ]);

            throw new \RuntimeException(json_encode($json['error']));
        }

        return $json['result'] ?? null;
    }

    public function silentContext(): array
    {
        return [
            'context' => [
                'tracking_disable' => true,
                'mail_create_nolog' => true,
                'mail_create_nosubscribe' => true,
                'mail_notrack' => true,
                'mail_notify_force_send' => false,
                'mail_auto_subscribe_no_notify' => true,
            ],
        ];
    }

    public function searchClientByName(string $name, bool $exact = false): array
    {
        return $this->searchByName(config('odoo.client_model', 'res.partner'), $name, ['id', 'name'], $exact);
    }

    public function createClient(array $data)
    {
        return $this->call(
            config('odoo.client_model', 'res.partner'),
            'create',
            [$data],
            $this->silentContext()
        );
    }

    public function findOrCreateClient(string $name): array
    {
        $name = trim($name) !== '' ? trim($name) : 'Client Laravel RHS';
        $existing = $this->searchClientByName($name, exact: true);

        if (empty($existing[0]['id'])) {
            $existing = $this->searchClientByName($name);
        }

        if (!empty($existing[0]['id'])) {
            return [
                'id' => (int) $existing[0]['id'],
                'created' => false,
                'record' => $existing[0],
            ];
        }

        $id = (int) $this->createClient([
            'name' => $name,
            'company_type' => 'company',
        ]);

        return [
            'id' => $id,
            'created' => true,
            'record' => ['id' => $id, 'name' => $name],
        ];
    }

    public function createCandidate(array $data)
    {
        return $this->call(
            config('odoo.candidate_model', 'hr.candidate'),
            'create',
            [$data],
            $this->silentContext()
        );
    }

    public function createDemande(array $data)
    {
        return $this->call(
            config('odoo.demande_model', 'hr.job'),
            'create',
            [$this->filterWritableFields(config('odoo.demande_model', 'hr.job'), $data)],
            $this->silentContext()
        );
    }

    public function updateDemande(int $id, array $data): bool
    {
        $payload = $this->filterWritableFields(config('odoo.demande_model', 'hr.job'), $data);

        if (empty($payload)) {
            return false;
        }

        return (bool) $this->call(
            config('odoo.demande_model', 'hr.job'),
            'write',
            [[$id], $payload],
            $this->silentContext()
        );
    }

    public function findOrCreateSmartOffer(string $name, array $data): array
    {
        $model = 'smart.offre';
        $reference = trim((string) ($data['reference_externe'] ?? ''));
        $domain = $reference !== ''
            ? [['reference_externe', '=', $reference]]
            : [['name', '=', $name]];

        $existing = $this->call($model, 'search_read', [$domain], [
            'fields' => ['id', 'name', 'reference_externe'],
            'limit' => 1,
        ]) ?: [];

        if (!empty($existing[0]['id'])) {
            $id = (int) $existing[0]['id'];
            $payload = $this->filterWritableFields($model, $data);

            if (!empty($payload)) {
                $this->call($model, 'write', [[$id], $payload], $this->silentContext());
            }

            return [
                'id' => $id,
                'created' => false,
                'record' => $existing[0],
            ];
        }

        $payload = array_merge($data, ['name' => $name]);
        $id = (int) $this->call($model, 'create', [$this->filterWritableFields($model, $payload)], $this->silentContext());

        return [
            'id' => $id,
            'created' => true,
            'record' => ['id' => $id, 'name' => $name],
        ];
    }

    public function updateSmartOffer(int $id, array $data): bool
    {
        $payload = $this->filterWritableFields('smart.offre', $data);

        if (empty($payload)) {
            return false;
        }

        return (bool) $this->call('smart.offre', 'write', [[$id], $payload], $this->silentContext());
    }

    public function pushSmartCandidateFromRtp(array $data): array
    {
        try {
            return (array) ($this->call(
                'smart.candidat',
                'recevoir_depuis_rtp',
                [[], $data],
                $this->silentContext()
            ) ?: []);
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (!str_contains($message, 'recevoir_depuis_rtp') && !str_contains($message, 'positional argument')) {
                throw $e;
            }

            return (array) ($this->call(
                'smart.candidat',
                'recevoir_depuis_rtp',
                [$data],
                $this->silentContext()
            ) ?: []);
        }
    }

    public function updateSmartCandidate(int $id, array $data): bool
    {
        $payload = $this->filterWritableFields('smart.candidat', $data);

        if (empty($payload)) {
            return false;
        }

        return (bool) $this->call('smart.candidat', 'write', [[$id], $payload], $this->silentContext());
    }

    public function mapDemandeFieldsByLabels(array $labelValues): array
    {
        if (empty($labelValues)) {
            return [];
        }

        $fields = $this->call(config('odoo.demande_model', 'hr.job'), 'fields_get', [], [
            'attributes' => ['string', 'type', 'readonly'],
        ]) ?: [];

        $labels = [];

        foreach ($fields as $name => $meta) {
            if (($meta['readonly'] ?? false) === true) {
                continue;
            }

            $label = $this->normalizeFieldLabel((string) ($meta['string'] ?? ''));

            if ($label !== '') {
                $labels[$label] = [
                    'name' => $name,
                    'type' => $meta['type'] ?? null,
                ];
            }
        }

        $payload = [];

        foreach ($labelValues as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $field = $labels[$this->normalizeFieldLabel((string) $label)] ?? null;

            if (! $field || ! $this->supportsScalarFieldType($field['type'])) {
                continue;
            }

            $payload[$field['name']] = $value;
        }

        return $payload;
    }

    public function searchDemande(string $name, int $departmentId, bool $exact = true): array
    {
        $operator = $exact ? '=' : 'ilike';

        return $this->call(
            config('odoo.demande_model', 'hr.job'),
            'search_read',
            [[
                ['name', $operator, $name],
                ['department_id', '=', $departmentId],
            ]],
            [
                'fields' => ['id', 'name', 'department_id'],
                'limit' => 1,
            ]
        ) ?: [];
    }

    public function findOrCreateDemande(string $name, int $departmentId, array $data): array
    {
        $name = trim($name) !== '' ? trim($name) : 'Demande Laravel RHS';
        $existing = $this->searchDemande($name, $departmentId, exact: true);

        if (empty($existing[0]['id'])) {
            $existing = $this->searchDemande($name, $departmentId, exact: false);
        }

        if (!empty($existing[0]['id'])) {
            $id = (int) $existing[0]['id'];
            $this->updateDemande($id, $data);

            return [
                'id' => $id,
                'created' => false,
                'record' => $existing[0],
            ];
        }

        $payload = array_merge($data, [
            'name' => $name,
            'department_id' => $departmentId,
        ]);
        $id = (int) $this->createDemande($payload);

        return [
            'id' => $id,
            'created' => true,
            'record' => ['id' => $id, 'name' => $name, 'department_id' => $departmentId],
        ];
    }

    public function searchStageByName(string $name): array
    {
        return $this->call(
            config('odoo.stage_model', 'hr.recruitment.stage'),
            'search_read',
            [[
                ['name', '=', $name],
            ]],
            [
                'fields' => ['id', 'name'],
                'limit' => 1,
            ]
        ) ?: [];
    }

    public function resolvePreselectionStageId(): int
    {
        $stageName = (string) config('odoo.preselection_stage_name', '');

        if ($stageName !== '') {
            $stage = $this->searchStageByName($stageName);

            if (!empty($stage[0]['id'])) {
                return (int) $stage[0]['id'];
            }
        }

        return (int) config('odoo.preselection_stage_id', 1);
    }

    public function createApplicant(array $data)
    {
        return $this->call(
            config('odoo.applicant_model', 'hr.applicant'),
            'create',
            [$data],
            $this->silentContext()
        );
    }

    public function uploadAttachment(array $data)
    {
        return $this->call(
            config('odoo.attachment_model', 'ir.attachment'),
            'create',
            [$data],
            $this->silentContext()
        );
    }

    public function searchDepartmentByName(string $name, bool $exact = false): array
    {
        return $this->searchByName(config('odoo.department_model', 'hr.department'), $name, ['id', 'name'], $exact);
    }

    public function createDepartment(array $data)
    {
        return $this->call(
            config('odoo.department_model', 'hr.department'),
            'create',
            [$data],
            $this->silentContext()
        );
    }

    public function findOrCreateDepartment(string $name): array
    {
        $name = trim($name) !== '' ? trim($name) : 'Client Laravel RHS';
        $existing = $this->searchDepartmentByName($name, exact: true);

        if (empty($existing[0]['id'])) {
            $existing = $this->searchDepartmentByName($name);
        }

        if (!empty($existing[0]['id'])) {
            return [
                'id' => (int) $existing[0]['id'],
                'created' => false,
                'record' => $existing[0],
            ];
        }

        $id = (int) $this->createDepartment(['name' => $name]);

        return [
            'id' => $id,
            'created' => true,
            'record' => ['id' => $id, 'name' => $name],
        ];
    }

    public function recruitmentUrl(?int $jobId = null): string
    {
        if ($jobId && config('odoo.recruitment_job_url_template')) {
            return str_replace('{job_id}', (string) $jobId, (string) config('odoo.recruitment_job_url_template'));
        }

        if ($jobId) {
            return $this->url . '/odoo/recruitment/' . $jobId . '/action-214';
        }

        return (string) (config('odoo.recruitment_url') ?: $this->url . '/odoo/recruitment');
    }

    private function searchByName(string $model, string $name, array $fields, bool $exact = false): array
    {
        $name = trim($name);

        if ($name === '') {
            return [];
        }

        $operator = $exact ? '=' : 'ilike';

        return $this->call(
            $model,
            'search_read',
            [[
                ['name', $operator, $name],
            ]],
            [
                'fields' => $fields,
                'limit' => 1,
            ]
        ) ?: [];
    }

    private function filterWritableFields(string $model, array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $fields = $this->call($model, 'fields_get', [], [
            'attributes' => ['readonly'],
        ]) ?: [];

        return collect($data)
            ->filter(function ($value, string $field) use ($fields) {
                if (!array_key_exists($field, $fields)) {
                    return false;
                }

                if (($fields[$field]['readonly'] ?? false) === true) {
                    return false;
                }

                return $value !== null && $value !== '';
            })
            ->all();
    }

    private function normalizeFieldLabel(string $label): string
    {
        $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label;
        $label = strtolower($label);
        $label = preg_replace('/[^a-z0-9]+/', ' ', $label) ?? $label;

        return trim($label);
    }

    private function supportsScalarFieldType(?string $type): bool
    {
        return in_array($type, [
            'char',
            'text',
            'html',
            'date',
            'datetime',
            'integer',
            'float',
            'monetary',
            'selection',
        ], true);
    }

    private function httpClient()
    {
        return Http::timeout((int) config('odoo.timeout', 30))
            ->withOptions([
                'verify' => (bool) config('odoo.verify_ssl', false),
            ]);
    }
}
