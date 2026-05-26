<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RecoleccionController extends Controller
{
    public function index()
    {
        // Query directly using DB since we don't have a specific Laravel model yet
        $solicitudes = DB::table('solicitudes_recoleccion')
            ->join('usuarios as ciudadano', 'solicitudes_recoleccion.usuario_id', '=', 'ciudadano.id')
            ->leftJoin('usuarios as recolector', 'solicitudes_recoleccion.recolector_id', '=', 'recolector.id')
            ->select(
                'solicitudes_recoleccion.*', 
                'ciudadano.nombre as ciudadano_nombre', 
                'ciudadano.email as ciudadano_email',
                'recolector.nombre as recolector_nombre'
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.recolecciones.index', compact('solicitudes'));
    }

    public function storeRecolector(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|min:5|max:50',
            'email' => 'required|email|max:100|unique:usuarios',
            'password' => 'required|string|min:6',
            'edad' => 'required|integer|min:18|max:80',
            'licencia_conducir' => 'required|string|max:50',
        ];

        $request->validate($rules);

        User::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => 'recolector',
            'edad' => $request->edad,
            'licencia_conducir' => $request->licencia_conducir,
            'is_admin' => false,
            'auth_provider' => 'local',
            'profile_completed' => true,
            'ubicacion' => 'Centro de Recolección',
            'titulo_perfil' => 'Chofer Recolector'
        ]);

        return redirect()->route('admin.recolecciones.index')->with('success', 'Recolector registrado exitosamente.');
    }

    public function completarSolicitud(Request $request, $id)
    {
        $recolector_id = auth()->id();

        DB::table('solicitudes_recoleccion')
            ->where('id', $id)
            ->update([
                'estado' => 'completada',
                'recolector_id' => $recolector_id,
                'updated_at' => now()
            ]);

        return redirect()->route('admin.recolecciones.index')->with('success', 'Solicitud marcada como completada.');
    }
}
