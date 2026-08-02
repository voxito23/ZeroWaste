<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->foreignId('parent_comment_id')->nullable()->after('autor_id')->constrained('respuestas')->cascadeOnDelete();
            $table->index(['post_id', 'parent_comment_id'], 'idx_respuestas_post_parent');
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->string('type', 50)->default('system_notice')->index();
            $table->string('entity_id', 100)->nullable();
            $table->foreignId('post_id')->nullable()->constrained('posts')->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('respuestas')->cascadeOnDelete();
            $table->string('route')->nullable();
            $table->json('payload')->default('{}');
            $table->index(['user_id', 'leida', 'created_at'], 'idx_notificaciones_user_read_date');
        });

        Schema::create('device_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('expo_push_token')->unique();
            $table->string('device_id');
            $table->string('platform', 20);
            $table->boolean('active')->default(true)->index();
            $table->timestampTz('last_seen_at');
            $table->timestampTz('disabled_at')->nullable();
            $table->string('last_error', 100)->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'device_id'], 'uq_push_token_user_device');
            $table->index(['user_id', 'active'], 'idx_push_token_user_active');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('usuarios')->cascadeOnDelete();
            $table->boolean('push_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            foreach (['comments', 'replies', 'likes', 'news', 'articles', 'campaigns', 'collections', 'points', 'rewards', 'system'] as $name) {
                $table->boolean($name)->default(true);
            }
            $table->timestampTz('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('device_push_tokens');

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_notificaciones_user_read_date');
            $table->dropConstrainedForeignId('comment_id');
            $table->dropConstrainedForeignId('post_id');
            $table->dropColumn(['type', 'entity_id', 'route', 'payload']);
        });

        Schema::table('respuestas', function (Blueprint $table) {
            $table->dropIndex('idx_respuestas_post_parent');
            $table->dropConstrainedForeignId('parent_comment_id');
        });
    }
};
