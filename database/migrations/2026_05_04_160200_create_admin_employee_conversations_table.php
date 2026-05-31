<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_employee_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('open');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('admin_seen_at')->nullable();
            $table->timestamp('employee_seen_at')->nullable();
            $table->timestamps();

            $table->index(['employee_user_id', 'employee_seen_at'], 'aec_employee_seen_idx');
            $table->index(['admin_user_id', 'admin_seen_at'], 'aec_admin_seen_idx');
            $table->index(['status', 'priority'], 'aec_status_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_employee_conversations');
    }
};
