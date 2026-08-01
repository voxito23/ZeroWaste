<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->index();
            $table->string('horario', 255)->nullable();
            $table->string('responsable', 150)->nullable();
            $table->softDeletes();
        });

        Schema::create('point_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->text('token_ciphertext');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestampTz('generated_at');
            $table->timestampTz('regenerated_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['location_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_qr_codes');
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['activo', 'horario', 'responsable', 'deleted_at']);
        });
    }
};
