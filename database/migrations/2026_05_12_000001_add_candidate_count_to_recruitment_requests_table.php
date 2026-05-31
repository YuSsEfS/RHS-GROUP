<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'candidate_count')) {
                $table->unsignedInteger('candidate_count')->nullable()->after('age');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requests', 'candidate_count')) {
                $table->dropColumn('candidate_count');
            }
        });
    }
};
