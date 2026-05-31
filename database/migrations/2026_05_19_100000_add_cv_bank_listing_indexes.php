<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cvs')) {
            return;
        }

        Schema::table('cvs', function (Blueprint $table) {
            if (
                Schema::hasColumn('cvs', 'archived_at')
                && Schema::hasColumn('cvs', 'is_active')
                && Schema::hasColumn('cvs', 'uploaded_at')
                && !$this->hasIndex('cvs', 'cvs_active_uploaded_listing_idx')
            ) {
                $table->index(['archived_at', 'is_active', 'uploaded_at', 'id'], 'cvs_active_uploaded_listing_idx');
            }

            if (
                Schema::hasColumn('cvs', 'source_type')
                && Schema::hasColumn('cvs', 'uploaded_at')
                && !$this->hasIndex('cvs', 'cvs_source_uploaded_listing_idx')
            ) {
                $table->index(['source_type', 'uploaded_at', 'id'], 'cvs_source_uploaded_listing_idx');
            }

            if (
                Schema::hasColumn('cvs', 'cv_folder_id')
                && Schema::hasColumn('cvs', 'uploaded_at')
                && !$this->hasIndex('cvs', 'cvs_folder_uploaded_listing_idx')
            ) {
                $table->index(['cv_folder_id', 'uploaded_at', 'id'], 'cvs_folder_uploaded_listing_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cvs')) {
            return;
        }

        foreach ([
            'cvs_folder_uploaded_listing_idx',
            'cvs_source_uploaded_listing_idx',
            'cvs_active_uploaded_listing_idx',
        ] as $indexName) {
            if ($this->hasIndex('cvs', $indexName)) {
                Schema::table('cvs', function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $databaseName = DB::connection()->getDatabaseName();

            if (!$databaseName) {
                return false;
            }

            return DB::selectOne(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$databaseName, $table, $indexName]
            ) !== null;
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => $row->name === $indexName);
        }

        return false;
    }
};
