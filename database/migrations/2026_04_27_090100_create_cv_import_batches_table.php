<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('cv_folder_id')->nullable()->constrained('cv_folders')->nullOnDelete();
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('queued_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->string('status', 40)->default('en_attente')->index();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_import_batches');
    }
};
