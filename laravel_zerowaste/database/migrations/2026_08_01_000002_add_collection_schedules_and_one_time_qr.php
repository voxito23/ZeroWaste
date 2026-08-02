<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique();
            $table->boolean('active')->default(false);
            $table->time('starts_at')->default('10:00:00');
            $table->time('ends_at')->default('14:00:00');
            $table->unsignedSmallInteger('interval_minutes')->default(60);
            $table->unsignedSmallInteger('capacity_per_interval')->default(10);
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->date('exception_date')->index();
            $table->string('kind', 30)->default('closed');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedSmallInteger('capacity_per_interval')->nullable();
            $table->string('reason', 255);
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['exception_date', 'kind']);
        });

        Schema::table('solicitudes_recoleccion', function (Blueprint $table) {
            $table->string('cantidad_estimada', 100)->nullable();
            $table->text('notas')->nullable();
            $table->timestampTz('scheduled_at')->nullable()->index();
            $table->string('folio', 30)->nullable()->unique();
        });

        Schema::table('tokens_qr_recoleccion', function (Blueprint $table) {
            $table->text('token_ciphertext')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('active')->index();
            $table->timestampTz('invalidated_at')->nullable();
        });

        foreach (range(1, 7) as $weekday) {
            DB::table('collection_schedules')->insert([
                'weekday' => $weekday,
                'active' => DB::raw(in_array($weekday, [1, 3, 5], true) ? 'TRUE' : 'FALSE'),
                'starts_at' => '10:00:00',
                'ends_at' => '14:00:00',
                'interval_minutes' => 60,
                'capacity_per_interval' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tokens_qr_recoleccion', function (Blueprint $table) {
            $table->dropColumn(['token_ciphertext', 'version', 'status', 'invalidated_at']);
        });
        Schema::table('solicitudes_recoleccion', function (Blueprint $table) {
            $table->dropUnique(['folio']);
            $table->dropColumn(['cantidad_estimada', 'notas', 'scheduled_at', 'folio']);
        });
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('collection_schedules');
    }
};
