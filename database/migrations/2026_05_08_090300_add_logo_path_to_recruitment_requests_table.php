<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('client_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requests', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
