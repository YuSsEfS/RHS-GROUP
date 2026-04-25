<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 20);
            $table->date('report_date');
            $table->string('title')->nullable();
            $table->text('summary');
            $table->text('achievements')->nullable();
            $table->text('blockers')->nullable();
            $table->text('next_steps')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('admin_seen_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'report_date']);
            $table->index(['status', 'admin_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reports');
    }
};
