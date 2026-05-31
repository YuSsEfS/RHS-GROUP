<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cvs')) {
            return;
        }

        Schema::table('cvs', function (Blueprint $table) {
            if (!Schema::hasColumn('cvs', 'duplicate_of_cv_id')) {
                $table->foreignId('duplicate_of_cv_id')
                    ->nullable()
                    ->after('notes')
                    ->constrained('cvs')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('cvs', 'duplicate_score')) {
                $table->decimal('duplicate_score', 5, 2)->nullable()->after('duplicate_of_cv_id');
            }

            if (!Schema::hasColumn('cvs', 'duplicate_reason')) {
                $table->text('duplicate_reason')->nullable()->after('duplicate_score');
            }

            if (!Schema::hasColumn('cvs', 'original_file_size')) {
                $table->unsignedBigInteger('original_file_size')->nullable()->after('file_size');
            }

            if (!Schema::hasColumn('cvs', 'compressed_file_size')) {
                $table->unsignedBigInteger('compressed_file_size')->nullable()->after('original_file_size');
            }

            if (!Schema::hasColumn('cvs', 'compressed_path')) {
                $table->string('compressed_path')->nullable()->after('encrypted_path');
            }

            if (!Schema::hasColumn('cvs', 'compression_status')) {
                $table->string('compression_status', 40)->nullable()->default('pending')->after('compressed_path');
            }

            if (!Schema::hasColumn('cvs', 'compression_error')) {
                $table->text('compression_error')->nullable()->after('compression_status');
            }

            if (!Schema::hasColumn('cvs', 'compression_verified_at')) {
                $table->timestamp('compression_verified_at')->nullable()->after('compression_error');
            }
        });

        Schema::table('cvs', function (Blueprint $table) {
            $table->index('duplicate_of_cv_id', 'cvs_duplicate_of_cv_id_index');
            $table->index('compression_status', 'cvs_compression_status_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cvs')) {
            return;
        }

        Schema::table('cvs', function (Blueprint $table) {
            if (Schema::hasColumn('cvs', 'duplicate_of_cv_id')) {
                $table->dropConstrainedForeignId('duplicate_of_cv_id');
            }

            foreach ([
                'duplicate_score',
                'duplicate_reason',
                'original_file_size',
                'compressed_file_size',
                'compressed_path',
                'compression_status',
                'compression_error',
                'compression_verified_at',
            ] as $column) {
                if (Schema::hasColumn('cvs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
