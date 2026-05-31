<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cv_import_batches')) {
            Schema::table('cv_import_batches', function (Blueprint $table) {
                if (!Schema::hasColumn('cv_import_batches', 'duplicate_files')) {
                    $table->unsignedInteger('duplicate_files')->default(0)->after('failed_files');
                }
            });
        }

        if (Schema::hasTable('external_cv_batches')) {
            Schema::table('external_cv_batches', function (Blueprint $table) {
                if (!Schema::hasColumn('external_cv_batches', 'duplicate_files')) {
                    $table->unsignedInteger('duplicate_files')->default(0)->after('failed_files');
                }
            });
        }

        if (Schema::hasTable('external_cvs')) {
            Schema::table('external_cvs', function (Blueprint $table) {
                if (!Schema::hasColumn('external_cvs', 'duplicate_of_cv_id')) {
                    $table->foreignId('duplicate_of_cv_id')
                        ->nullable()
                        ->after('cv_id')
                        ->constrained('cvs')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('external_cvs', 'duplicate_score')) {
                    $table->decimal('duplicate_score', 5, 2)->nullable()->after('duplicate_of_cv_id');
                }

                if (!Schema::hasColumn('external_cvs', 'duplicate_reason')) {
                    $table->text('duplicate_reason')->nullable()->after('duplicate_score');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('external_cvs')) {
            Schema::table('external_cvs', function (Blueprint $table) {
                if (Schema::hasColumn('external_cvs', 'duplicate_of_cv_id')) {
                    $table->dropConstrainedForeignId('duplicate_of_cv_id');
                }

                foreach (['duplicate_score', 'duplicate_reason'] as $column) {
                    if (Schema::hasColumn('external_cvs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('external_cv_batches') && Schema::hasColumn('external_cv_batches', 'duplicate_files')) {
            Schema::table('external_cv_batches', function (Blueprint $table) {
                $table->dropColumn('duplicate_files');
            });
        }

        if (Schema::hasTable('cv_import_batches') && Schema::hasColumn('cv_import_batches', 'duplicate_files')) {
            Schema::table('cv_import_batches', function (Blueprint $table) {
                $table->dropColumn('duplicate_files');
            });
        }
    }
};
