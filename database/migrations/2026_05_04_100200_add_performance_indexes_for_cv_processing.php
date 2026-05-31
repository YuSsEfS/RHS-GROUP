<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recruitment_requests')) {
            Schema::table('recruitment_requests', function (Blueprint $table) {
                if (Schema::hasColumn('recruitment_requests', 'matching_status') && !$this->hasIndex('recruitment_requests', 'recruitment_requests_matching_status_index')) {
                    $table->index('matching_status', 'recruitment_requests_matching_status_index');
                }

                if (Schema::hasColumn('recruitment_requests', 'matching_finished_at') && !$this->hasIndex('recruitment_requests', 'recruitment_requests_matching_finished_at_index')) {
                    $table->index('matching_finished_at', 'recruitment_requests_matching_finished_at_index');
                }
            });
        }

        if (Schema::hasTable('cvs')) {
            Schema::table('cvs', function (Blueprint $table) {
                if (Schema::hasColumn('cvs', 'file_hash') && !$this->hasIndex('cvs', 'cvs_file_hash_index')) {
                    $table->index('file_hash', 'cvs_file_hash_index');
                }
            });
        }

        if (Schema::hasTable('external_cvs')) {
            Schema::table('external_cvs', function (Blueprint $table) {
                if (
                    Schema::hasColumn('external_cvs', 'batch_id')
                    && Schema::hasColumn('external_cvs', 'status')
                    && !$this->hasIndex('external_cvs', 'external_cvs_batch_status_index')
                ) {
                    $table->index(['batch_id', 'status'], 'external_cvs_batch_status_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('external_cvs') && $this->hasIndex('external_cvs', 'external_cvs_batch_status_index')) {
            Schema::table('external_cvs', function (Blueprint $table) {
                $table->dropIndex('external_cvs_batch_status_index');
            });
        }

        if (Schema::hasTable('cvs') && $this->hasIndex('cvs', 'cvs_file_hash_index')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->dropIndex('cvs_file_hash_index');
            });
        }

        if (Schema::hasTable('recruitment_requests')) {
            if ($this->hasIndex('recruitment_requests', 'recruitment_requests_matching_finished_at_index')) {
                Schema::table('recruitment_requests', function (Blueprint $table) {
                    $table->dropIndex('recruitment_requests_matching_finished_at_index');
                });
            }

            if ($this->hasIndex('recruitment_requests', 'recruitment_requests_matching_status_index')) {
                Schema::table('recruitment_requests', function (Blueprint $table) {
                    $table->dropIndex('recruitment_requests_matching_status_index');
                });
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $databaseName = DB::connection()->getDatabaseName();

        if (!$databaseName) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$databaseName, $table, $indexName]
        );

        return $result !== null;
    }
};
