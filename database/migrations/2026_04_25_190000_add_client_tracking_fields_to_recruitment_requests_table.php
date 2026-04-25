<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->foreignId('client_user_id')
                ->nullable()
                ->after('cv_folder_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('request_status')
                ->default('pending')
                ->after('ai_normalized_requirements');

            $table->text('admin_notes')
                ->nullable()
                ->after('request_status');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
            $table->dropColumn(['request_status', 'admin_notes']);
        });
    }
};
