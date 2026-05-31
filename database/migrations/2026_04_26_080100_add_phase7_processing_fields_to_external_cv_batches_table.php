<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('external_cv_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('external_cv_batches', 'processing_status')) {
                $table->string('processing_status', 40)
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('external_cv_batches', 'processing_started_at')) {
                $table->timestamp('processing_started_at')
                    ->nullable()
                    ->after('processing_status');
            }

            if (!Schema::hasColumn('external_cv_batches', 'processing_completed_at')) {
                $table->timestamp('processing_completed_at')
                    ->nullable()
                    ->after('processing_started_at');
            }

            if (!Schema::hasColumn('external_cv_batches', 'processing_error_message')) {
                $table->text('processing_error_message')
                    ->nullable()
                    ->after('processing_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_cv_batches', function (Blueprint $table) {
            foreach ([
                'processing_error_message',
                'processing_completed_at',
                'processing_started_at',
                'processing_status',
            ] as $column) {
                if (Schema::hasColumn('external_cv_batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
