<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;

class EventoController extends Controller
{
    public function index()
    {
        $eventos = Evento::orderByDesc('fecha_inicio')->get();
        return view('admin.eventos.index', compact('eventos'));
    }

    public function create()
    {
        return view('admin.eventos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:256000',
        ]);

        if ($request->hasFile('imagen_archivo')) {
            try {
                $img = $request->file('imagen_archivo');
                $nombreImg = uniqid('evt_') . '.' . $img->getClientOriginalExtension();
                $destino = app()->basePath('../flask_zerowaste/static/img/eventos/');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $img->move($destino, $nombreImg);
                $data['imagen_url'] = $nombreImg;
            } catch (\Exception $e) {
                \Log::error("Error subiendo imagen de evento: " . $e->getMessage());
            }
        }
        unset($data['imagen_archivo']);
        $data['activa'] = $request->has('activa');

        Evento::create($data);
        
        return redirect()->route('eventos.index')->with('success', 'Evento o Jornada creada exitosamente.');
    }

    public function edit(Evento $evento)
    {
        return view('admin.eventos.edit', compact('evento'));
    }

    public function update(Request $request, Evento $evento)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:256000',
        ]);

        if ($request->hasFile('imagen_archivo')) {
            try {
                $img = $request->file('imagen_archivo');
                $nombreImg = uniqid('evt_') . '.' . $img->getClientOriginalExtension();
                $destino = app()->basePath('../flask_zerowaste/static/img/eventos/');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $img->move($destino, $nombreImg);
                $data['imagen_url'] = $nombreImg;
            } catch (\Exception $e) {
                \Log::error("Error subiendo imagen de evento edit: " . $e->getMessage());
            }
        }
        unset($data['imagen_archivo']);
        $data['activa'] = $request->has('activa');

        $evento->update($data);
        return redirect()->route('eventos.index')->with('success', 'Evento o Jornada actualizada exitosamente.');
    }

    public function destroy(Evento $evento)
    {
        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento eliminado exitosamente.');
    }
}
