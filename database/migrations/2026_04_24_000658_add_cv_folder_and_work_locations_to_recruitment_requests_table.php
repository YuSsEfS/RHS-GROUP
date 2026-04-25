<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'cv_folder_id')) {
                $table->foreignId('cv_folder_id')
                    ->nullable()
                    ->after('job_offer_id')
                    ->constrained('cv_folders')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('recruitment_requests', 'work_locations')) {
                $table->text('work_locations')
                    ->nullable()
                    ->after('work_location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requests', 'cv_folder_id')) {
                $table->dropConstrainedForeignId('cv_folder_id');
            }

            if (Schema::hasColumn('recruitment_requests', 'work_locations')) {
                $table->dropColumn('work_locations');
            }
        });
    }
};