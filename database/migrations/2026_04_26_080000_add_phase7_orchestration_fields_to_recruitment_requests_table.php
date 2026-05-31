<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'assigned_employee_id')) {
                $table->foreignId('assigned_employee_id')
                    ->nullable()
                    ->after('client_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('recruitment_requests', 'assignment_status')) {
                $table->string('assignment_status', 40)
                    ->nullable()
                    ->after('assigned_employee_id');
            }

            if (!Schema::hasColumn('recruitment_requests', 'assignment_seen_at')) {
                $table->timestamp('assignment_seen_at')
                    ->nullable()
                    ->after('assignment_status');
            }

            if (!Schema::hasColumn('recruitment_requests', 'pipeline_stage')) {
                $table->string('pipeline_stage', 60)
                    ->nullable()
                    ->after('request_status');
            }

            if (!Schema::hasColumn('recruitment_requests', 'employee_notes')) {
                $table->text('employee_notes')
                    ->nullable()
                    ->after('admin_notes');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_job_status')) {
                $table->string('matching_job_status', 40)
                    ->nullable()
                    ->after('pipeline_stage');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_started_at')) {
                $table->timestamp('matching_started_at')
                    ->nullable()
                    ->after('matching_job_status');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_completed_at')) {
                $table->timestamp('matching_completed_at')
                    ->nullable()
                    ->after('matching_started_at');
            }

            if (!Schema::hasColumn('recruitment_requests', 'matching_error_message')) {
                $table->text('matching_error_message')
                    ->nullable()
                    ->after('matching_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            foreach ([
                'matching_error_message',
                'matching_completed_at',
                'matching_started_at',
                'matching_job_status',
                'employee_notes',
                'pipeline_stage',
                'assignment_seen_at',
                'assignment_status',
            ] as $column) {
                if (Schema::hasColumn('recruitment_requests', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('recruitment_requests', 'assigned_employee_id')) {
                $table->dropConstrainedForeignId('assigned_employee_id');
            }
        });
    }
};
