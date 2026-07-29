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
        $rules = [
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:256000',
        ];
        $messages = [
            'titulo.required' => 'El título del evento es obligatorio.',
            'titulo.max' => 'El título no puede exceder los 150 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'Debe ingresar una fecha de inicio válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'Debe ingresar una fecha de fin válida.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.max' => 'La imagen no debe superar los 250MB.',
        ];
        $data = $request->validate($rules, $messages);

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
        $rules = [
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:256000',
        ];
        $messages = [
            'titulo.required' => 'El título del evento es obligatorio.',
            'titulo.max' => 'El título no puede exceder los 150 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'Debe ingresar una fecha de inicio válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'Debe ingresar una fecha de fin válida.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.max' => 'La imagen no debe superar los 250MB.',
        ];
        $data = $request->validate($rules, $messages);

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
