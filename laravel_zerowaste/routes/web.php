<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\PasswordResetRequestController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RecoleccionController;
use App\Http\Controllers\ReportesRecoleccionController;
use App\Http\Controllers\ImpactAdminController;
use App\Http\Controllers\PointQrController;
use App\Http\Controllers\CollectionScheduleController;

$adminPrefix = trim((string) env('ADMIN_PATH_PREFIX', 'zw-interno'), '/');
$adminBase = '/'.$adminPrefix;

Route::get($adminBase.'/login', function () {
    return view('welcome');
})->name('login');

Route::redirect($adminBase, $adminBase.'/login');
Route::redirect($adminBase.'/', $adminBase.'/login');
Route::redirect('/', $adminBase.'/login');

Route::post($adminBase.'/login', [AuthController::class, 'login'])->name('admin.login');

Route::prefix($adminPrefix)->middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Reportes
    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReportController::class, 'exportar'])->name('reportes.exportar');
    
    // Perfil Administrador
    Route::get('/perfil', [AdminProfileController::class, 'edit'])->name('admin.perfil.edit');
    Route::put('/perfil', [AdminProfileController::class, 'update'])->name('admin.perfil.update');

    // Usuarios
    Route::get('/usuarios/check-email', [UserController::class, 'checkEmail'])->name('usuarios.checkEmail');
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{user}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    // Campañas (CRUD completo)
    Route::get('/campanas', [CampaignController::class, 'index'])->name('campanas.index');
    Route::get('/campanas/create', [CampaignController::class, 'create'])->name('campanas.create');
    Route::post('/campanas', [CampaignController::class, 'store'])->name('campanas.store');
    Route::get('/campanas/{campaign}/edit', [CampaignController::class, 'edit'])->name('campanas.edit');
    Route::put('/campanas/{campaign}', [CampaignController::class, 'update'])->name('campanas.update');
    Route::delete('/campanas/{campaign}', [CampaignController::class, 'destroy'])->name('campanas.destroy');

    // Materiales
    Route::get('/materiales', [MaterialController::class, 'index'])->name('materiales.index');
    Route::post('/materiales', [MaterialController::class, 'store'])->name('materiales.store');

    // Mapa (CRUD completo)
    Route::get('/mapa', [MapController::class, 'index'])->name('mapa.index');
    Route::get('/mapa/create', [MapController::class, 'create'])->name('mapa.create');
    Route::post('/mapa', [MapController::class, 'store'])->name('mapa.store');
    Route::get('/mapa/{location}/edit', [MapController::class, 'edit'])->name('mapa.edit');
    Route::put('/mapa/{location}', [MapController::class, 'update'])->name('mapa.update');
    Route::delete('/mapa/{location}', [MapController::class, 'destroy'])->name('mapa.destroy');
    Route::post('/mapa/{location}/desactivar', [MapController::class, 'deactivate'])->name('mapa.deactivate');
    Route::post('/mapa/{id}/reactivar', [MapController::class, 'reactivate'])->name('mapa.reactivate');
    Route::post('/mapa/{location}/qr', [PointQrController::class, 'generate'])->name('mapa.qr.generate');
    Route::get('/mapa/{location}/qr', [PointQrController::class, 'show'])->name('mapa.qr.show');
    Route::get('/mapa/{location}/qr/descargar/{format}', [PointQrController::class, 'download'])->name('mapa.qr.download');
    Route::post('/mapa/{location}/qr/regenerar', [PointQrController::class, 'regenerate'])->name('mapa.qr.regenerate');
    Route::post('/mapa/{location}/qr/revocar', [PointQrController::class, 'revoke'])->name('mapa.qr.revoke');

    // Eventos
    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
    Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');

    // Mensajes de contacto
    Route::get('/mensajes', [ContactMessageController::class, 'index'])->name('mensajes.index');
    Route::put('/mensajes/{id}/estado', [ContactMessageController::class, 'updateEstado'])->name('mensajes.updateEstado');
    Route::put('/mensajes/{id}/responder', [ContactMessageController::class, 'responder'])->name('mensajes.responder');
    Route::get('/mensajes/{id}/thread', [ContactMessageController::class, 'getThread'])->name('mensajes.thread');

    // Foro / Posts
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/aprobar', [PostController::class, 'approve'])->name('posts.approve');
    Route::delete('/respuestas/{id}', [PostController::class, 'destroyRespuesta'])->name('respuestas.destroy');

    // Recolecciones a Domicilio
    Route::get('/recolecciones', [RecoleccionController::class, 'index'])->name('admin.recolecciones.index');
    Route::get('/recolecciones/horarios', [CollectionScheduleController::class, 'index'])->name('admin.recolecciones.horarios');
    Route::put('/recolecciones/horarios', [CollectionScheduleController::class, 'update'])->name('admin.recolecciones.horarios.update');
    Route::post('/recolecciones/horarios/excepciones', [CollectionScheduleController::class, 'storeException'])->name('admin.recolecciones.horarios.excepciones.store');
    Route::delete('/recolecciones/horarios/excepciones/{id}', [CollectionScheduleController::class, 'destroyException'])->name('admin.recolecciones.horarios.excepciones.destroy');
    Route::post('/recolecciones/horarios/restaurar', [CollectionScheduleController::class, 'restore'])->name('admin.recolecciones.horarios.restore');
    Route::post('/recolecciones/recolector', [RecoleccionController::class, 'storeRecolector'])->name('admin.recolecciones.recolector.store');
    Route::post('/recolecciones/{id}/completar', [RecoleccionController::class, 'completarSolicitud'])->name('admin.recolecciones.completar');
    Route::get('/recolecciones/reporte', [ReportesRecoleccionController::class, 'generarPDF'])->name('admin.recolecciones.reporte');

    Route::get('/ranking', [ImpactAdminController::class, 'ranking'])->name('impacto.ranking');
    Route::get('/recompensas', [ImpactAdminController::class, 'rewards'])->name('impacto.recompensas');
    Route::post('/recompensas', [ImpactAdminController::class, 'storeReward'])->name('impacto.recompensas.store');
    Route::put('/recompensas/{id}', [ImpactAdminController::class, 'updateReward'])->name('impacto.recompensas.update');
    Route::delete('/recompensas/{id}', [ImpactAdminController::class, 'destroyReward'])->name('impacto.recompensas.destroy');
    Route::get('/canjes', [ImpactAdminController::class, 'redemptions'])->name('impacto.canjes');
    Route::put('/canjes/{id}', [ImpactAdminController::class, 'updateRedemption'])->name('impacto.canjes.update');
    Route::get('/puntos/reglas', [ImpactAdminController::class, 'rules'])->name('impacto.reglas');
    Route::post('/puntos/reglas', [ImpactAdminController::class, 'storeRule'])->name('impacto.reglas.store');
    Route::put('/puntos/reglas/{id}', [ImpactAdminController::class, 'updateRule'])->name('impacto.reglas.update');
    Route::get('/puntos/movimientos', [ImpactAdminController::class, 'movements'])->name('impacto.movimientos');
    Route::get('/puntos/movimientos/exportar', [ImpactAdminController::class, 'exportMovements'])->name('impacto.movimientos.export');

    // Solicitudes de recuperación de contraseña
    Route::get('/recuperacion', [PasswordResetRequestController::class, 'index'])->name('recuperacion.index');
    Route::put('/recuperacion/{id}', [PasswordResetRequestController::class, 'update'])->name('recuperacion.update');
});
