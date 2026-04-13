<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class MapController extends Controller
{
    public function index()
    {
        $locations = Location::query()->orderByDesc('created_at')->get();
        return view('admin.mapa.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.mapa.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'direccion' => 'required|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'tipo' => 'required|string|max:100',
            'imagen_archivo' => 'nullable|image|max:2048',
            'materiales' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['nombre', 'direccion', 'latitud', 'longitud', 'tipo']);
        $data['materiales'] = $request->input('materiales', 'No especificado (Material General)');

        // Manejar subida de imagen del punto
        if ($request->hasFile('imagen_archivo')) {
            try {
                $img = $request->file('imagen_archivo');
                $nombreImg = uniqid('punto_') . '.' . $img->getClientOriginalExtension();
                $destino = base_path('../flask_zerowaste/static/img/');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $img->move($destino, $nombreImg);
                $data['imagen'] = $nombreImg;
            } catch (\Exception $e) {
                // If it fails due to permissions, log it and continue without image
                // so the user doesn't get a 500 error.
                \Log::error("Error subiendo imagen de punto: " . $e->getMessage());
            }
        }

        Location::query()->create($data);
        return redirect()->route('mapa.index')->with('success', 'Punto de acopio creado exitosamente.');
    }

    public function edit(Location $location)
    {
        return view('admin.mapa.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'direccion' => 'required|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'tipo' => 'required|string|max:100',
            'imagen_archivo' => 'nullable|image|max:2048',
            'materiales' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['nombre', 'direccion', 'latitud', 'longitud', 'tipo']);
        $data['materiales'] = $request->input('materiales', 'No especificado (Material General)');

        // Manejar subida de imagen del punto
        if ($request->hasFile('imagen_archivo')) {
            try {
                $img = $request->file('imagen_archivo');
                $nombreImg = uniqid('punto_') . '.' . $img->getClientOriginalExtension();
                $destino = base_path('../flask_zerowaste/static/img/');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $img->move($destino, $nombreImg);
                $data['imagen'] = $nombreImg;
            } catch (\Exception $e) {
                \Log::error("Error subiendo imagen de punto en edit: " . $e->getMessage());
            }
        }

        $location->update($data);
        return redirect()->route('mapa.index')->with('success', 'Punto actualizado exitosamente.');
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->route('mapa.index')->with('success', 'Punto eliminado exitosamente.');
    }
}
