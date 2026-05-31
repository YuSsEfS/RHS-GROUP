<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cv_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('cv_matches', 'ai_analysis_status')) {
                $table->string('ai_analysis_status', 40)
                    ->nullable()
                    ->after('selected');
            }

            if (!Schema::hasColumn('cv_matches', 'ai_analysis_started_at')) {
                $table->timestamp('ai_analysis_started_at')
                    ->nullable()
                    ->after('ai_analysis_status');
            }

            if (!Schema::hasColumn('cv_matches', 'ai_analysis_completed_at')) {
                $table->timestamp('ai_analysis_completed_at')
                    ->nullable()
                    ->after('ai_analysis_started_at');
            }

            if (!Schema::hasColumn('cv_matches', 'ai_analysis_error_message')) {
                $table->text('ai_analysis_error_message')
                    ->nullable()
                    ->after('ai_analysis_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cv_matches', function (Blueprint $table) {
            foreach ([
                'ai_analysis_error_message',
                'ai_analysis_completed_at',
                'ai_analysis_started_at',
                'ai_analysis_status',
            ] as $column) {
                if (Schema::hasColumn('cv_matches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
