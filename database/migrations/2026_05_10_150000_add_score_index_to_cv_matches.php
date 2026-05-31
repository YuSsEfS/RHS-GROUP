<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'cv_matches_request_score_index';
    private const SELECTED_INDEX_NAME = 'cv_matches_request_selected_index';

    public function up(): void
    {
        if (!Schema::hasTable('cv_matches')) {
            return;
        }

        Schema::table('cv_matches', function (Blueprint $table) {
            if (
                Schema::hasColumn('cv_matches', 'recruitment_request_id')
                && Schema::hasColumn('cv_matches', 'score')
                && !$this->hasIndex(self::INDEX_NAME)
            ) {
                $table->index(['recruitment_request_id', 'score'], self::INDEX_NAME);
            }

            if (
                Schema::hasColumn('cv_matches', 'recruitment_request_id')
                && Schema::hasColumn('cv_matches', 'selected')
                && !$this->hasIndex(self::SELECTED_INDEX_NAME)
            ) {
                $table->index(['recruitment_request_id', 'selected'], self::SELECTED_INDEX_NAME);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cv_matches')) {
            return;
        }

        Schema::table('cv_matches', function (Blueprint $table) {
            if ($this->hasIndex(self::SELECTED_INDEX_NAME)) {
                $table->dropIndex(self::SELECTED_INDEX_NAME);
            }

            if ($this->hasIndex(self::INDEX_NAME)) {
                $table->dropIndex(self::INDEX_NAME);
            }
        });
    }

    private function hasIndex(string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $databaseName = DB::connection()->getDatabaseName();

            if (!$databaseName) {
                return false;
            }

            return DB::selectOne(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$databaseName, 'cv_matches', $indexName]
            ) !== null;
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('cv_matches')"))
                ->contains(fn ($row) => $row->name === $indexName);
        }

        return false;
    }
};
