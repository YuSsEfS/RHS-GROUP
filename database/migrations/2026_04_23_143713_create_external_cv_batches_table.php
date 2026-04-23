<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_cv_batches', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('notes')->nullable();

            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('indexed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);

            $table->string('status')->default('draft'); // draft|processing|completed|failed

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_cv_batches');
    }
};