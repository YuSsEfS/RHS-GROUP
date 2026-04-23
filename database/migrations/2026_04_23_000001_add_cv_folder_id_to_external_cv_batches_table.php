<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_cv_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('external_cv_batches', 'cv_folder_id')) {
                $table->foreignId('cv_folder_id')
                    ->nullable()
                    ->after('notes')
                    ->constrained('cv_folders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_cv_batches', function (Blueprint $table) {
            if (Schema::hasColumn('external_cv_batches', 'cv_folder_id')) {
                $table->dropConstrainedForeignId('cv_folder_id');
            }
        });
    }
};