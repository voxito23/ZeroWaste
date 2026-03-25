<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;

class EventoController extends Controller
{
    public function index()
    {
        $eventos = Evento::all();
        return view('admin.eventos.index', compact('eventos'));
    }

    public function create()
    {
        return view('admin.eventos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:150',
            'fecha_inicio' => 'required|date',
            'ubicacion' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'categoria' => 'nullable|string|max:100',
            'link_unirse' => 'nullable|url|max:255',
        ]);

        Evento::create($request->all());

        return redirect()->route('eventos.index')->with('success', 'Evento creado exitosamente.');
    }

    public function edit(Evento $evento)
    {
        return view('admin.eventos.edit', compact('evento'));
    }

    public function update(Request $request, Evento $evento)
    {
        $request->validate([
            'titulo' => 'required|string|max:150',
            'fecha_inicio' => 'required|date',
            'ubicacion' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'categoria' => 'nullable|string|max:100',
            'link_unirse' => 'nullable|url|max:255',
        ]);

        $evento->update($request->all());

        return redirect()->route('eventos.index')->with('success', 'Evento actualizado exitosamente.');
    }

    public function destroy(Evento $evento)
    {
        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento eliminado exitosamente.');
    }
}
