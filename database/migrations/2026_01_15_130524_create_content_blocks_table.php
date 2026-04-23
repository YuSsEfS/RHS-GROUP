<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('page');     // home, about, services...
            $table->string('section');  // hero, stats...
            $table->string('field');    // title, subtitle, image...
            $table->longText('value')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->index(['page','section']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('content_blocks');
    }
};
