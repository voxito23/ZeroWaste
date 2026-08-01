<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('canjes', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable();
            $table->unique(['usuario_id', 'idempotency_key'], 'uq_canje_usuario_idempotency');
        });
    }

    public function down(): void
    {
        Schema::table('canjes', function (Blueprint $table) {
            $table->dropUnique('uq_canje_usuario_idempotency');
            $table->dropColumn('idempotency_key');
        });
    }
};
