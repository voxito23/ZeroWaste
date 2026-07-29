<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Post;
use App\Models\Location;
use App\Models\Actividad;
use App\Models\ContactMessage;
use App\Models\PasswordResetRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

/**
 * @property \Illuminate\Http\Request $request
 */

class DashboardController extends Controller
{
    public function index()
    {
        $campaignCount = Campaign::query()->count();
        $totalUsuarios = User::query()->count();
        $totalPosts = Post::query()->count();
        $totalPuntos = Location::query()->count();
        $totalActividades = Actividad::query()->count();
        $totalMensajes = ContactMessage::query()->count();
        $totalSolicitudes = PasswordResetRequest::query()->count();

        // Desglose por roles y estado
        $totalAdmins = User::query()->where('is_admin', 'true')->count();
        $totalNormales = User::query()->where('is_admin', 'false')->count();
        $totalBloqueados = User::query()->where('bloqueado', 'true')->count();

        // Tendencias (últimos 7 días vs 7 días anteriores)
        $now = Carbon::now();
        $hace7 = $now->copy()->subDays(7);
        $hace14 = $now->copy()->subDays(14);

        $usersLast7 = User::where('created_at', '>=', $hace7)->count();
        $usersPrev7 = User::whereBetween('created_at', [$hace14, $hace7])->count();
        $trendUsuarios = $usersPrev7 > 0 ? round((($usersLast7 - $usersPrev7) / $usersPrev7) * 100) : ($usersLast7 > 0 ? 100 : 0);

        $postsLast7 = Post::where('created_at', '>=', $hace7)->count();
        $postsPrev7 = Post::whereBetween('created_at', [$hace14, $hace7])->count();
        $trendPosts = $postsPrev7 > 0 ? round((($postsLast7 - $postsPrev7) / $postsPrev7) * 100) : ($postsLast7 > 0 ? 100 : 0);

        $msgsLast7 = ContactMessage::where('created_at', '>=', $hace7)->count();
        $msgsPrev7 = ContactMessage::whereBetween('created_at', [$hace14, $hace7])->count();
        $trendMensajes = $msgsPrev7 > 0 ? round((($msgsLast7 - $msgsPrev7) / $msgsPrev7) * 100) : ($msgsLast7 > 0 ? 100 : 0);

        $campsLast7 = Campaign::where('created_at', '>=', $hace7)->count();
        $campsPrev7 = Campaign::whereBetween('created_at', [$hace14, $hace7])->count();
        $trendCampanas = $campsPrev7 > 0 ? round((($campsLast7 - $campsPrev7) / $campsPrev7) * 100) : ($campsLast7 > 0 ? 100 : 0);

        // Último registro
        $ultimoRegistro = User::query()->orderByDesc('created_at')->first();

        // Usuarios recientes
        $usuariosRecientes = User::query()->orderByDesc('created_at')->limit(5)->get();


        // Datos para gráfica: usuarios por día (últimos 7 días)
        $usuariosPorMes = DB::table('usuarios')
            ->select(DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as mes_raw"), DB::raw("TO_CHAR(created_at, 'DD Mon') as mes"), DB::raw('COUNT(*) as total'))
            ->whereNotNull('created_at')
            ->groupBy('mes_raw', 'mes')
            ->orderBy('mes_raw', 'desc')
            ->limit(7)
            ->get()
            ->reverse()
            ->values();

        // Sentimiento NLP
        $sentimiento = ['POS' => 0, 'NEU' => 0, 'NEG' => 0];
        try {
            $response = Http::timeout(60)->get('http://fastapi_app:6000/analisis/sentimiento');
            if ($response->successful()) {
                $sentimiento = $response->json()['data'];
            } else {
                $sentimiento = ['POS' => 65, 'NEU' => 20, 'NEG' => 15];
            }
        } catch (\Exception $e) {
            $sentimiento = ['POS' => 65, 'NEU' => 20, 'NEG' => 15];
        }

        return view('admin.dashboard', compact(
            'campaignCount', 'totalUsuarios', 'totalPosts', 'totalPuntos',
            'totalActividades', 'sentimiento', 'totalMensajes', 'totalSolicitudes',
            'usuariosRecientes', 'usuariosPorMes',
            'totalAdmins', 'totalNormales', 'totalBloqueados',
            'trendUsuarios', 'trendPosts', 'trendMensajes', 'trendCampanas',
            'ultimoRegistro'
        ));
    }

    public function exportarPDF(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
        ], [
            'fecha_fin.after_or_equal' => 'La fecha final debe ser posterior o igual a la de inicio.',
        ]);

        $fechaInicio = request('fecha_inicio') . ' 00:00:00';
        $fechaFin = request('fecha_fin') . ' 23:59:59';
        $data = [
            'fecha' => Carbon::now()->format('d/m/Y - H:i'),
            'fecha_inicio' => request('fecha_inicio'),
            'fecha_fin' => request('fecha_fin'),
            'totalUsuarios' => User::query()->whereBetween('created_at', [$fechaInicio, $fechaFin])->count(),
            'totalPuntos' => Location::query()->count(),
            'totalCampanas' => Campaign::query()->whereBetween('created_at', [$fechaInicio, $fechaFin])->count(),
            'totalMensajes' => ContactMessage::query()->whereBetween('created_at', [$fechaInicio, $fechaFin])->count(),
            'totalSolicitudes' => PasswordResetRequest::query()->whereBetween('created_at', [$fechaInicio, $fechaFin])->count(),
            'campanas' => Campaign::query()->where('activa', 'true')->whereBetween('created_at', [$fechaInicio, $fechaFin])->get(),
            'usuariosRecientes' => User::query()->whereBetween('created_at', [$fechaInicio, $fechaFin])->orderByDesc('created_at')->limit(10)->get(),
            'sentimiento' => ['POS' => 65, 'NEU' => 20, 'NEG' => 15]
        ];

        try {
            $response = Http::timeout(60)->get('http://fastapi_app:6000/analisis/sentimiento');
            if ($response->successful()) {
                $data['sentimiento'] = $response->json()['data'];
            }
        } catch (\Exception $e) {}

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('reporte_pdf', $data);

        // Guardado Automático en el servidor
        $filename = 'Reporte_ZeroWaste_' . Carbon::now()->format('Ymd_His') . '.pdf';
        $reportFolder = storage_path('app/public/reportes');
        if (!file_exists($reportFolder)) {
            mkdir($reportFolder, 0755, true);
        }
        $pdf->save($reportFolder . '/' . $filename);

        return $pdf->download('Reporte_ZeroWaste_' . Carbon::now()->format('Ymd') . '.pdf');
    }
}
