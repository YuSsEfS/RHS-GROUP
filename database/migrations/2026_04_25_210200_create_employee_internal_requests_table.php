<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_internal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 50);
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('admin_seen_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'admin_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_internal_requests');
    }
};
