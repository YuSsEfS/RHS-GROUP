<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CvIngestionService
{
    public function __construct(
        protected CvExtractionService $extraction,
        protected CvIndexingService $indexing,
        protected OpenAiRecruitmentService $ai,
    ) {
    }

    public function syncApplicationCvToBank(JobApplication $application): array
    {
        $relativePath = ltrim((string) $application->cv_path, '/');

        if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
            return ['status' => 'missing'];
        }

        $binary = Storage::disk('public')->get($relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $hash = $this->extraction->hashBinary($binary);
        $text = $this->extraction->extractTextFromBinary($binary, $extension);

        $profile = $this->buildProfile([
            'full_name' => $application->full_name,
            'email' => $application->email,
            'phone' => $application->phone,
            'title' => $application->position,
            'city' => $application->city,
        ], $text);

        $existingBySource = null;

        if (Schema::hasColumn('cvs', 'source_type') && Schema::hasColumn('cvs', 'source_id')) {
            $existingBySource = Cv::query()
                ->where('source_type', 'application')
                ->where('source_id', $application->id)
                ->first();
        }

        $existingByHash = Cv::query()
            ->where('file_hash', $hash)
            ->first();

        if ($existingBySource) {
            if (!empty($existingBySource->encrypted_path) && Storage::disk('local')->exists($existingBySource->encrypted_path)) {
                Storage::disk('local')->delete($existingBySource->encrypted_path);
            }

            $storedPath = $this->storePrivateBinary($binary, $extension, 'cv_app_');

            $existingBySource->update($this->buildCvPayload(
                profile: $profile,
                originalFilename: basename($relativePath),
                mimeType: $this->extraction->guessMimeTypeFromExtension($extension),
                fileSize: strlen($binary),
                storedPath: $storedPath,
                text: $text,
                hash: $hash,
                sourceType: 'application',
                sourceId: $application->id,
                city: $application->city,
                currentTitle: $application->position,
            ));

            return ['status' => 'updated', 'cv_id' => $existingBySource->id];
        }

        if ($existingByHash) {
            $updateData = [
                'candidate_name' => $existingByHash->candidate_name ?: ($profile['full_name'] ?? $application->full_name),
                'email' => $existingByHash->email ?: ($profile['email'] ?? $application->email),
                'phone' => $existingByHash->phone ?: ($profile['phone'] ?? $application->phone),
            ];

            if (Schema::hasColumn('cvs', 'source_type')) {
                $updateData['source_type'] = 'application';
            }

            if (Schema::hasColumn('cvs', 'source_id')) {
                $updateData['source_id'] = $application->id;
            }

            if (Schema::hasColumn('cvs', 'city')) {
                $updateData['city'] = $existingByHash->city ?: $application->city;
            }

            if (Schema::hasColumn('cvs', 'current_title')) {
                $updateData['current_title'] = $existingByHash->current_title ?: $application->position;
            }

            if (Schema::hasColumn('cvs', 'is_active')) {
                $updateData['is_active'] = true;
            }

            $existingByHash->update($updateData);

            return ['status' => 'relinked', 'cv_id' => $existingByHash->id];
        }

        $storedPath = $this->storePrivateBinary($binary, $extension, 'cv_app_');

        $cv = Cv::create($this->buildCvPayload(
            profile: $profile,
            originalFilename: basename($relativePath),
            mimeType: $this->extraction->guessMimeTypeFromExtension($extension),
            fileSize: strlen($binary),
            storedPath: $storedPath,
            text: $text,
            hash: $hash,
            sourceType: 'application',
            sourceId: $application->id,
            city: $application->city,
            currentTitle: $application->position,
        ));

        return ['status' => 'created', 'cv_id' => $cv->id];
    }

    public function importManualCv(
        string $binary,
        string $originalFilename,
        ?string $mimeType,
        int $fileSize,
        array $context = []
    ): array {
        $hash = $this->extraction->hashBinary($binary);

        if (Schema::hasColumn('cvs', 'file_hash') && Cv::where('file_hash', $hash)->exists()) {
            return ['status' => 'skipped'];
        }

        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $text = $this->extraction->extractTextFromBinary($binary, $extension);

        $profile = $this->buildProfile([
            'city' => $context['city'] ?? null,
            'title' => $context['current_title'] ?? null,
        ], $text);

        $resolvedCity = $context['city']
            ?? $profile['city']
            ?? data_get($profile, 'location.city')
            ?? data_get($profile, 'address.city');

        $resolvedTitle = $context['current_title']
            ?? $profile['title']
            ?? data_get($profile, 'current_title')
            ?? data_get($profile, 'headline')
            ?? data_get($profile, 'desired_position');

        $storedPath = $this->storePrivateBinary($binary, $extension, 'cv_');

        $cv = Cv::create($this->buildCvPayload(
            profile: $profile,
            originalFilename: $originalFilename,
            mimeType: $mimeType ?: $this->extraction->guessMimeTypeFromExtension($extension),
            fileSize: $fileSize,
            storedPath: $storedPath,
            text: $text,
            hash: $hash,
            sourceType: 'manual',
            sourceId: null,
            city: $resolvedCity,
            currentTitle: $resolvedTitle,
            folderId: $context['cv_folder_id'] ?? null,
            isActive: true,
            notes: $context['notes'] ?? null,
        ));

        return ['status' => 'created', 'cv_id' => $cv->id];
    }

    public function buildProfile(array $seed, string $text): array
    {
        $profile = array_merge([
            'full_name' => null,
            'email' => null,
            'phone' => null,
            'title' => null,
            'years_experience' => null,
            'education' => null,
            'languages' => [],
            'technical_skills' => [],
            'soft_skills' => [],
            'industries' => [],
            'certifications' => [],
            'summary' => $text ? mb_substr($text, 0, 1500) : null,
            'city' => null,
        ], $this->indexing->buildStructuredProfile($text, $seed), $seed);

        if ($text !== '') {
            $structured = $this->ai->structureCv($text);

            if (is_array($structured) && !empty($structured)) {
                $profile = array_merge($profile, array_filter($structured, fn ($value) => $value !== null && $value !== '' && $value !== []));
            }
        }

        return $profile;
    }

    private function storePrivateBinary(string $binary, string $extension, string $prefix): string
    {
        $storedPath = 'private/cvs/' . uniqid($prefix, true) . '.' . strtolower($extension);
        Storage::disk('local')->put($storedPath, $binary);

        return $storedPath;
    }

    private function buildCvPayload(
        array $profile,
        string $originalFilename,
        ?string $mimeType,
        int $fileSize,
        string $storedPath,
        string $text,
        string $hash,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $city = null,
        ?string $currentTitle = null,
        ?int $folderId = null,
        bool $isActive = true,
        ?string $notes = null,
    ): array {
        $data = [
            'candidate_name' => $profile['full_name'] ?? null,
            'email' => $profile['email'] ?? null,
            'phone' => $profile['phone'] ?? null,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'encrypted_path' => $storedPath,
            'encrypted_extracted_text' => $text,
            'structured_profile' => $profile,
            'uploaded_at' => now(),
        ];

        if (Schema::hasColumn('cvs', 'file_hash')) {
            $data['file_hash'] = $hash;
        }

        if (Schema::hasColumn('cvs', 'source_type')) {
            $data['source_type'] = $sourceType;
        }

        if (Schema::hasColumn('cvs', 'source_id')) {
            $data['source_id'] = $sourceId;
        }

        if (Schema::hasColumn('cvs', 'cv_folder_id')) {
            $data['cv_folder_id'] = $folderId;
        }

        if (Schema::hasColumn('cvs', 'city')) {
            $data['city'] = $city;
        }

        if (Schema::hasColumn('cvs', 'current_title')) {
            $data['current_title'] = $currentTitle;
        }

        if (Schema::hasColumn('cvs', 'is_active')) {
            $data['is_active'] = $isActive;
        }

        if (Schema::hasColumn('cvs', 'notes')) {
            $data['notes'] = $notes;
        }

        return $data;
    }
}
