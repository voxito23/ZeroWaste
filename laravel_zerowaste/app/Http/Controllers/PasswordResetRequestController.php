<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PasswordResetRequest;

class PasswordResetRequestController extends Controller
{
    public function index()
    {
        $solicitudes = PasswordResetRequest::orderByDesc('created_at')->get();
        return view('admin.recuperacion', compact('solicitudes'));
    }

    public function update(Request $request, $id)
    {
        $sol = PasswordResetRequest::findOrFail($id);
        $sol->estado = $request->input('estado', 'atendido');
        $sol->notas = $request->input('notas', $sol->notas);
        $sol->save();
        return redirect()->route('recuperacion.index')->with('success', 'Solicitud actualizada.');
    }
}
