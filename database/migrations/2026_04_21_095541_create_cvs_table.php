<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);

            $table->string('encrypted_path');
            $table->longText('encrypted_extracted_text')->nullable();
            $table->json('structured_profile')->nullable();

            $table->string('file_hash')->unique();
            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cvs');
    }
};