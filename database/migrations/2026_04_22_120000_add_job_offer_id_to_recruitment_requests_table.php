<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'job_offer_id')) {
                $table->foreignId('job_offer_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('job_offers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requests', 'job_offer_id')) {
                $table->dropConstrainedForeignId('job_offer_id');
            }
        });
    }
};