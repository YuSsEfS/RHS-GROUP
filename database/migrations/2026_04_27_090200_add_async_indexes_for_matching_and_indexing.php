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
            if (!$this->hasIndex('recruitment_requests', ['matching_status'])) {
                $table->index('matching_status', 'recruitment_requests_matching_status_index');
            }

            if (!$this->hasIndex('recruitment_requests', ['matching_finished_at'])) {
                $table->index('matching_finished_at', 'recruitment_requests_matching_finished_at_index');
            }
        });

        Schema::table('cv_matches', function (Blueprint $table) {
            if (!$this->hasIndex('cv_matches', ['recruitment_request_id'])) {
                $table->index('recruitment_request_id', 'cv_matches_recruitment_request_id_index');
            }
        });

        Schema::table('external_cvs', function (Blueprint $table) {
            if (!$this->hasIndex('external_cvs', ['batch_id', 'status'])) {
                $table->index(['batch_id', 'status'], 'external_cvs_batch_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            foreach ([
                'recruitment_requests_matching_status_index',
                'recruitment_requests_matching_finished_at_index',
            ] as $indexName) {
                if ($this->hasIndexByName('recruitment_requests', $indexName)) {
                    $table->dropIndex($indexName);
                }
            }
        });

        Schema::table('cv_matches', function (Blueprint $table) {
            if ($this->hasIndexByName('cv_matches', 'cv_matches_recruitment_request_id_index')) {
                $table->dropIndex('cv_matches_recruitment_request_id_index');
            }
        });

        Schema::table('external_cvs', function (Blueprint $table) {
            if ($this->hasIndexByName('external_cvs', 'external_cvs_batch_status_index')) {
                $table->dropIndex('external_cvs_batch_status_index');
            }
        });
    }

    private function hasIndex(string $table, array $columns): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->groupBy('Key_name');

            foreach ($indexes as $rows) {
                $indexedColumns = array_values(array_map(
                    fn ($row) => $row->Column_name,
                    $rows->all()
                ));

                if ($indexedColumns === $columns) {
                    return true;
                }

                if (count($columns) === 1 && in_array($columns[0], $indexedColumns, true)) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                $details = DB::select("PRAGMA index_info('{$index->name}')");
                $indexedColumns = array_values(array_map(
                    fn ($row) => $row->name,
                    $details
                ));

                if ($indexedColumns === $columns) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasIndexByName(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'"));
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => $row->name === $indexName);
        }

        return false;
    }
};
