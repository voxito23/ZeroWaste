<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::orderByDesc('created_at')->get();
        return view('admin.campanas', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.campanas_create');
    }

    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string|max:500',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:256000',
        ];
        $messages = [
            'nombre.required' => 'El nombre de la campaña es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 150 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'fecha_inicio.date' => 'Debe ingresar una fecha de inicio válida.',
            'fecha_fin.date' => 'Debe ingresar una fecha de fin válida.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar los 250MB.',
        ];
        $data = $request->validate($rules, $messages);

        // Manejar subida de imagen
        if ($request->hasFile('imagen_archivo')) {
            try {
                $img = $request->file('imagen_archivo');
                $nombreImg = uniqid('camp_') . '.' . $img->getClientOriginalExtension();
                $destino = app()->basePath('../flask_zerowaste/static/img/eventos/');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $img->move($destino, $nombreImg);
                $data['imagen_url'] = $nombreImg;
            } catch (\Exception $e) {
                \Log::error("Error subiendo imagen de campaña: " . $e->getMessage());
            }
        }
        unset($data['imagen_archivo']);

        $data['activa'] = $request->has('activa') ? 'true' : 'false';
        
        // Así se actualiza la BD con la nueva campaña
        Campaign::create($data);
        
        return redirect()->route('campanas.index')->with('success', 'Campaña creada.');
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campanas_edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string|max:500',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:256000',
        ];
        $messages = [
            'nombre.required' => 'El nombre de la campaña es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 150 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'fecha_inicio.date' => 'Debe ingresar una fecha de inicio válida.',
            'fecha_fin.date' => 'Debe ingresar una fecha de fin válida.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar los 250MB.',
        ];
        $data = $request->validate($rules, $messages);

        // Manejar subida de imagen
        if ($request->hasFile('imagen_archivo')) {
            try {
                $img = $request->file('imagen_archivo');
                $nombreImg = uniqid('camp_') . '.' . $img->getClientOriginalExtension();
                $destino = app()->basePath('../flask_zerowaste/static/img/eventos/');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $img->move($destino, $nombreImg);
                $data['imagen_url'] = $nombreImg;
            } catch (\Exception $e) {
                \Log::error("Error subiendo imagen de campaña edit: " . $e->getMessage());
            }
        }
        unset($data['imagen_archivo']);

        $data['activa'] = $request->has('activa') ? 'true' : 'false';
        $campaign->update($data);
        return redirect()->route('campanas.index')->with('success', 'Campaña actualizada.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campanas.index')->with('success', 'Campaña eliminada.');
    }
}
