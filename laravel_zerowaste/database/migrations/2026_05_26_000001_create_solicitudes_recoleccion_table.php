<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_recoleccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->text('direccion');
            $table->text('materiales')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente, completada, cancelada
            $table->foreignId('recolector_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->integer('calificacion_recolector')->nullable();
            $table->text('comentario_recolector')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_recoleccion');
    }
};
