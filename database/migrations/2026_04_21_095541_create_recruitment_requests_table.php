<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->nullable();
            $table->string('client_name')->nullable();
            $table->date('request_date')->nullable();

            $table->string('position_title');
            $table->string('work_location')->nullable();

            $table->longText('missions')->nullable();
            $table->string('recruitment_reason')->nullable();

            $table->string('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('education')->nullable();
            $table->string('experience_years')->nullable();
            $table->string('availability')->nullable();

            $table->boolean('lang_ar')->default(false);
            $table->boolean('lang_fr')->default(false);
            $table->boolean('lang_en')->default(false);
            $table->boolean('lang_es')->default(false);
            $table->string('other_language')->nullable();

            $table->text('personal_qualities')->nullable();
            $table->text('specific_knowledge')->nullable();

            $table->string('budget_type')->nullable();
            $table->string('monthly_salary')->nullable();
            $table->text('other_benefits')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('planned_start_date')->nullable();

            $table->json('ai_normalized_requirements')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_requests');
    }
};