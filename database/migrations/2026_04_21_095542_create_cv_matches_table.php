<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cv_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();

            $table->decimal('score', 5, 2)->default(0);
            $table->json('score_breakdown')->nullable();
            $table->text('summary')->nullable();
            $table->boolean('selected')->default(false);

            $table->timestamps();

            $table->unique(['recruitment_request_id', 'cv_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_matches');
    }
};