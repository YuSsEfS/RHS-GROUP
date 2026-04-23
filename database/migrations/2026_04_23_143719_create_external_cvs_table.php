<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_cvs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('external_cv_batches')
                ->cascadeOnDelete();

            $table->foreignId('cv_id')
                ->nullable()
                ->constrained('cvs')
                ->nullOnDelete();

            $table->string('candidate_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();

            $table->string('city')->nullable()->index();
            $table->string('current_title')->nullable()->index();

            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('stored_path');
            $table->string('file_hash')->nullable()->index();

            $table->longText('extracted_text')->nullable();
            $table->json('structured_profile')->nullable();

            $table->string('status')->default('pending'); // pending|indexed|failed
            $table->text('error_message')->nullable();

            $table->timestamp('indexed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_cvs');
    }
};