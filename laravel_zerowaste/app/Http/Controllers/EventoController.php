<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Support\Media;

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
            'descripcion' => 'required|string|max:500',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
        $messages = [
            'titulo.required' => 'El título del evento es obligatorio.',
            'titulo.max' => 'El título no puede exceder los 150 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'Debe ingresar una fecha de inicio válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'Debe ingresar una fecha de fin válida.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
        ];
        $data = $request->validate($rules, $messages);

        $newImage = null;
        if ($request->hasFile('imagen_archivo')) {
            $newImage = Media::store($request->file('imagen_archivo'), 'eventos');
            $data['imagen_url'] = $newImage;
        }
        unset($data['imagen_archivo']);
        $data['activa'] = $request->has('activa') ? 'true' : 'false';

        try {
            Evento::create($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'eventos');
            throw $error;
        }
        
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
            'descripcion' => 'required|string|max:500',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
        $messages = [
            'titulo.required' => 'El título del evento es obligatorio.',
            'titulo.max' => 'El título no puede exceder los 150 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'Debe ingresar una fecha de inicio válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'Debe ingresar una fecha de fin válida.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
        ];
        $data = $request->validate($rules, $messages);

        $newImage = null;
        if ($request->hasFile('imagen_archivo')) {
            $newImage = Media::store($request->file('imagen_archivo'), 'eventos');
            $data['imagen_url'] = $newImage;
        }
        unset($data['imagen_archivo']);
        $data['activa'] = $request->has('activa') ? 'true' : 'false';

        try {
            $evento->update($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'eventos');
            throw $error;
        }
        return redirect()->route('eventos.index')->with('success', 'Evento o Jornada actualizada exitosamente.');
    }

    public function destroy(Evento $evento)
    {
        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento eliminado exitosamente.');
    }
}
