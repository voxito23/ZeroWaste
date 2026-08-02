<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recompensas', function (Blueprint $table) {
            $table->timestampTz('available_at')->nullable();
            $table->softDeletes();
        });
        Schema::create('point_rule_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('reglas_puntos')->cascadeOnDelete();
            $table->json('before_values');
            $table->json('after_values');
            $table->foreignId('administrator_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('created_at');
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrator_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('subject_type', 80)->index();
            $table->string('subject_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestampTz('created_at')->index();
        });
        DB::statement('CREATE UNIQUE INDEX uq_point_qr_one_active ON point_qr_codes (location_id) WHERE active = true');
        DB::table('reglas_puntos')->updateOrInsert(['codigo' => 'VISITA_PUNTO_QR'], [
            'descripcion' => 'Visita verificada en punto mediante QR', 'puntos' => 0,
            'limite_diario' => 1, 'activa' => DB::raw('FALSE'), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_point_qr_one_active');
        DB::table('reglas_puntos')->where('codigo', 'VISITA_PUNTO_QR')->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('movimientos_puntos')->whereColumn('movimientos_puntos.regla_id', 'reglas_puntos.id');
        })->delete();
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('point_rule_history');
        Schema::table('recompensas', fn (Blueprint $table) => $table->dropColumn(['available_at', 'deleted_at']));
    }
};
