<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();

            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('contract_type')->nullable(); // CDI, CDD, Intérim
            $table->string('sector')->nullable();

            $table->text('excerpt')->nullable();
            $table->longText('description')->nullable();
            $table->longText('missions')->nullable();
            $table->longText('requirements')->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('job_offers');
    }
};
