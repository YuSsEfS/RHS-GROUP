<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'matching_status')) {
                $table->string('matching_status', 40)
                    ->nullable()
                    ->after('matching_job_status');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_finished_at')) {
                $table->timestamp('matching_finished_at')
                    ->nullable()
                    ->after('matching_completed_at');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_error')) {
                $table->text('matching_error')
                    ->nullable()
                    ->after('matching_error_message');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_viewed_at')) {
                $table->timestamp('matching_viewed_at')
                    ->nullable()
                    ->after('matching_finished_at');
            }
        });

        DB::table('recruitment_requests')
            ->select([
                'id',
                'matching_status',
                'matching_job_status',
                'matching_finished_at',
                'matching_completed_at',
                'matching_error',
                'matching_error_message',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $payload = [];

                    if (!$row->matching_status && $row->matching_job_status) {
                        $payload['matching_status'] = match ($row->matching_job_status) {
                            'en_attente' => 'pending',
                            'en_cours' => 'processing',
                            'termine' => 'completed',
                            'echoue' => 'failed',
                            default => null,
                        };
                    }

                    if (!$row->matching_finished_at && $row->matching_completed_at) {
                        $payload['matching_finished_at'] = $row->matching_completed_at;
                    }

                    if (!$row->matching_error && $row->matching_error_message) {
                        $payload['matching_error'] = $row->matching_error_message;
                    }

                    if (!empty($payload)) {
                        DB::table('recruitment_requests')
                            ->where('id', $row->id)
                            ->update($payload);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            foreach ([
                'matching_viewed_at',
                'matching_error',
                'matching_finished_at',
                'matching_status',
            ] as $column) {
                if (Schema::hasColumn('recruitment_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
