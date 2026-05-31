<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_employee_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('admin_employee_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->longText('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at'], 'aem_conversation_created_idx');
            $table->index(['conversation_id', 'seen_at'], 'aem_conversation_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_employee_messages');
    }
};
