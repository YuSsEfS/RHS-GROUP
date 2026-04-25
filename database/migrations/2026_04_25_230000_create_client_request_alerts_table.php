<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_request_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('admin_response')->nullable();
            $table->timestamp('admin_seen_at')->nullable();
            $table->timestamp('employee_seen_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'admin_seen_at']);
            $table->index(['status', 'employee_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_request_alerts');
    }
};
