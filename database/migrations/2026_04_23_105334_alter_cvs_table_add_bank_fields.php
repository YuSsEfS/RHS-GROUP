<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('uploaded_at')->index();
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();

            $table->foreignId('cv_folder_id')
                ->nullable()
                ->after('source_id')
                ->constrained('cv_folders')
                ->nullOnDelete();

            $table->string('city')->nullable()->after('cv_folder_id')->index();
            $table->string('current_title')->nullable()->after('city')->index();
            $table->boolean('is_active')->default(true)->after('current_title')->index();
            $table->text('notes')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cv_folder_id');
            $table->dropColumn([
                'source_type',
                'source_id',
                'city',
                'current_title',
                'is_active',
                'notes',
            ]);
        });
    }
};