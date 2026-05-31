<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            if (!Schema::hasColumn('cvs', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('cvs', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('cvs', 'archive_reason')) {
                $table->string('archive_reason', 255)->nullable()->after('archived_by');
            }

            $table->index(['archived_at', 'cv_folder_id'], 'cvs_archived_at_cv_folder_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropIndex('cvs_archived_at_cv_folder_id_idx');

            if (Schema::hasColumn('cvs', 'archived_by')) {
                $table->dropConstrainedForeignId('archived_by');
            }

            if (Schema::hasColumn('cvs', 'archive_reason')) {
                $table->dropColumn('archive_reason');
            }

            if (Schema::hasColumn('cvs', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
