<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('formations', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->string('domain');
      $table->string('public');
      $table->string('format');
      $table->string('duration');
      $table->string('audience');
      $table->string('format_label');
      $table->text('description');
      $table->longText('program');
      $table->boolean('featured')->default(false);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('formations');
  }
};

