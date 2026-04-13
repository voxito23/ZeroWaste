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
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:2048',
        ]);

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

        $data['activa'] = $request->has('activa') ? true : false;
        
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
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'lugar' => 'nullable|string|max:200',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'tipo_etiqueta' => 'nullable|string|max:50',
            'link_evento' => 'nullable|string|max:500',
            'imagen_archivo' => 'nullable|image|max:2048',
        ]);

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

        $data['activa'] = $request->has('activa') ? true : false;
        $campaign->update($data);
        return redirect()->route('campanas.index')->with('success', 'Campaña actualizada.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campanas.index')->with('success', 'Campaña eliminada.');
    }
}
