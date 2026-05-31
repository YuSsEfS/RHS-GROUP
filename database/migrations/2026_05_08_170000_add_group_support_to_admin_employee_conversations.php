<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_employee_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_employee_conversations', 'conversation_type')) {
                $table->string('conversation_type', 20)->default('direct')->after('employee_user_id')->index();
            }

            if (!Schema::hasColumn('admin_employee_conversations', 'group_name')) {
                $table->string('group_name')->nullable()->after('conversation_type');
            }
        });

        if (!Schema::hasTable('admin_employee_conversation_participants')) {
            Schema::create('admin_employee_conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('admin_employee_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('seen_at')->nullable();
                $table->timestamps();

                $table->unique(['conversation_id', 'user_id'], 'aecp_conversation_user_unique');
                $table->index(['user_id', 'seen_at'], 'aecp_user_seen_idx');
            });
        }

        DB::table('admin_employee_conversations')
            ->select('id', 'admin_user_id', 'employee_user_id', 'admin_seen_at', 'employee_seen_at', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunk(200, function ($conversations) {
                foreach ($conversations as $conversation) {
                    foreach ([
                        $conversation->admin_user_id => $conversation->admin_seen_at,
                        $conversation->employee_user_id => $conversation->employee_seen_at,
                    ] as $userId => $seenAt) {
                        if (!$userId) {
                            continue;
                        }

                        DB::table('admin_employee_conversation_participants')->updateOrInsert(
                            [
                                'conversation_id' => $conversation->id,
                                'user_id' => $userId,
                            ],
                            [
                                'seen_at' => $seenAt,
                                'created_at' => $conversation->created_at,
                                'updated_at' => $conversation->updated_at,
                            ]
                        );
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_employee_conversation_participants');

        Schema::table('admin_employee_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('admin_employee_conversations', 'group_name')) {
                $table->dropColumn('group_name');
            }

            if (Schema::hasColumn('admin_employee_conversations', 'conversation_type')) {
                $table->dropColumn('conversation_type');
            }
        });
    }
};
