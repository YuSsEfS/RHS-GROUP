<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_offer_id')
                ->nullable()
                ->constrained('job_offers')
                ->nullOnDelete();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('city')->nullable();

            $table->string('cv_path')->nullable();
            $table->string('letter_path')->nullable();
            $table->longText('message')->nullable();

            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('job_applications');
    }
};
