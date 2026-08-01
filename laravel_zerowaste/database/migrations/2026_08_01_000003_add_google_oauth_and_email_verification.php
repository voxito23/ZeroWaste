<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->timestampTz('email_verified_at')->nullable()->index();
        });
        DB::table('usuarios')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);

        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_subject', 255);
            $table->string('provider_email', 255)->nullable();
            $table->timestampTz('linked_at');
            $table->timestampTz('last_login_at')->nullable();
            $table->unique(['provider', 'provider_subject']);
            $table->unique(['usuario_id', 'provider']);
        });

        Schema::create('oauth_login_states', function (Blueprint $table) {
            $table->id();
            $table->char('state_hash', 64)->unique();
            $table->text('verifier_ciphertext');
            $table->char('nonce_hash', 64);
            $table->char('handoff_hash', 64)->nullable()->unique();
            $table->text('claims_ciphertext')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->cascadeOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_tokens');
        Schema::dropIfExists('oauth_login_states');
        Schema::dropIfExists('oauth_accounts');
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn('email_verified_at'));
    }
};
