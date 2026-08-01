<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('aprobado')->default(true)->index();
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('aprobado_at')->nullable();
        });
        DB::statement('ALTER TABLE posts ALTER COLUMN aprobado SET DEFAULT false');
        Schema::create('reglas_puntos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('descripcion', 255);
            $table->unsignedInteger('puntos');
            $table->unsignedInteger('limite_diario')->nullable();
            $table->boolean('activa')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('saldos_puntos', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedBigInteger('puntos_disponibles')->default(0);
            $table->unsignedBigInteger('impacto_historico')->default(0)->index();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('recompensas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion');
            $table->unsignedInteger('costo_puntos');
            $table->unsignedInteger('stock')->default(0);
            $table->string('imagen', 255)->nullable();
            $table->boolean('activa')->default(true)->index();
            $table->unsignedInteger('limite_por_usuario')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestampsTz();
        });

        Schema::create('canjes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('recompensa_id')->constrained('recompensas')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->unsignedBigInteger('puntos_utilizados');
            $table->string('estado', 30)->default('SOLICITADA')->index();
            $table->foreignId('administrador_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['usuario_id', 'created_at']);
        });

        Schema::create('movimientos_puntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->bigInteger('cantidad');
            $table->unsignedBigInteger('saldo_anterior');
            $table->unsignedBigInteger('saldo_nuevo');
            $table->unsignedBigInteger('impacto_anterior');
            $table->unsignedBigInteger('impacto_nuevo');
            $table->string('referencia_tipo', 50)->nullable();
            $table->string('referencia_id', 100)->nullable();
            $table->foreignId('regla_id')->nullable()->constrained('reglas_puntos')->restrictOnDelete();
            $table->string('descripcion', 255);
            $table->foreignId('administrador_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['usuario_id', 'created_at']);
            $table->unique(['usuario_id', 'referencia_tipo', 'referencia_id', 'regla_id'], 'uq_movimiento_recompensa');
        });

        Schema::create('historial_canjes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canje_id')->constrained('canjes')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('administrador_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('motivo', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('tokens_qr_recoleccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->unique()->constrained('solicitudes_recoleccion')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('used_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        $activa = DB::raw('TRUE');

        DB::table('reglas_puntos')->insert([
            ['codigo'=>'RECOLECCION_QR','descripcion'=>'Recolección completada y verificada mediante QR','puntos'=>100,'limite_diario'=>null,'activa'=>$activa,'created_at'=>now(),'updated_at'=>now()],
            ['codigo'=>'EVENTO_CONFIRMADO','descripcion'=>'Participación confirmada en campaña o evento','puntos'=>50,'limite_diario'=>null,'activa'=>$activa,'created_at'=>now(),'updated_at'=>now()],
            ['codigo'=>'POST_APROBADO','descripcion'=>'Publicación válida y aprobada en el foro','puntos'=>20,'limite_diario'=>null,'activa'=>$activa,'created_at'=>now(),'updated_at'=>now()],
            ['codigo'=>'RESPUESTA_VALIDA','descripcion'=>'Respuesta válida en el foro','puntos'=>5,'limite_diario'=>5,'activa'=>$activa,'created_at'=>now(),'updated_at'=>now()],
            ['codigo'=>'RESENA_PUNTO','descripcion'=>'Reseña válida de un punto de acopio','puntos'=>10,'limite_diario'=>null,'activa'=>$activa,'created_at'=>now(),'updated_at'=>now()],
        ]);

        DB::table('recompensas')->insert([
            ['nombre'=>'Termo reutilizable','descripcion'=>'Termo reutilizable ZeroWaste.','costo_puntos'=>500,'stock'=>0,'imagen'=>'termo_reutilizable.png','activa'=>$activa,'orden'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Bolsa reutilizable','descripcion'=>'Bolsa reutilizable para compras.','costo_puntos'=>250,'stock'=>0,'imagen'=>'bolsa_reutilizable.png','activa'=>$activa,'orden'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Kit de botes para separación','descripcion'=>'Kit para separar residuos en casa.','costo_puntos'=>1200,'stock'=>0,'imagen'=>'kit_botes_separacion.png','activa'=>$activa,'orden'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Kit de cubiertos reutilizables','descripcion'=>'Cubiertos reutilizables portátiles.','costo_puntos'=>350,'stock'=>0,'imagen'=>'kit_cubiertos_reutilizables.png','activa'=>$activa,'orden'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Compostera doméstica','descripcion'=>'Compostera para residuos orgánicos.','costo_puntos'=>1500,'stock'=>0,'imagen'=>'compostera_domestica.png','activa'=>$activa,'orden'=>5,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_qr_recoleccion');
        Schema::dropIfExists('historial_canjes');
        Schema::dropIfExists('movimientos_puntos');
        Schema::dropIfExists('canjes');
        Schema::dropIfExists('recompensas');
        Schema::dropIfExists('saldos_puntos');
        Schema::dropIfExists('reglas_puntos');
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn(['aprobado', 'aprobado_at']);
        });
    }
};
