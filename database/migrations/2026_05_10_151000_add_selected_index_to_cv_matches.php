<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'cv_matches_request_selected_index';

    public function up(): void
    {
        if (!Schema::hasTable('cv_matches') || $this->hasIndex()) {
            return;
        }

        Schema::table('cv_matches', function (Blueprint $table) {
            if (
                Schema::hasColumn('cv_matches', 'recruitment_request_id')
                && Schema::hasColumn('cv_matches', 'selected')
            ) {
                $table->index(['recruitment_request_id', 'selected'], self::INDEX_NAME);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cv_matches') || !$this->hasIndex()) {
            return;
        }

        Schema::table('cv_matches', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    private function hasIndex(): bool
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $databaseName = DB::connection()->getDatabaseName();

            return $databaseName
                && DB::selectOne(
                    'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                    [$databaseName, 'cv_matches', self::INDEX_NAME]
                ) !== null;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('cv_matches')"))
                ->contains(fn ($row) => $row->name === self::INDEX_NAME);
        }

        return false;
    }
};
