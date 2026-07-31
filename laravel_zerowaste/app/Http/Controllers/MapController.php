<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Support\Media;

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
        $rules = [
            'nombre' => 'required|string|max:150',
            'direccion' => 'required|string',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'tipo' => 'required|string|max:100',
            'imagen_archivo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'materiales' => 'nullable|string|max:255',
        ];
        $messages = [
            'nombre.required' => 'El nombre del punto es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            'latitud.required' => 'La latitud es obligatoria.',
            'longitud.required' => 'La longitud es obligatoria.',
            'tipo.required' => 'El tipo de punto es obligatorio.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
        ];
        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'direccion', 'latitud', 'longitud', 'tipo']);
        $data['materiales'] = $request->input('materiales', 'No especificado (Material General)');

        $newImage = null;
        if ($request->hasFile('imagen_archivo')) {
            $newImage = Media::store($request->file('imagen_archivo'), 'puntos');
            $data['imagen'] = $newImage;
        }

        try {
            Location::query()->create($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'puntos');
            throw $error;
        }
        return redirect()->route('mapa.index')->with('success', 'Punto de acopio creado exitosamente.');
    }

    public function edit(Location $location)
    {
        return view('admin.mapa.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'direccion' => 'required|string',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'tipo' => 'required|string|max:100',
            'imagen_archivo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'materiales' => 'nullable|string|max:255',
        ];
        $messages = [
            'nombre.required' => 'El nombre del punto es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            'latitud.required' => 'La latitud es obligatoria.',
            'longitud.required' => 'La longitud es obligatoria.',
            'tipo.required' => 'El tipo de punto es obligatorio.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
        ];
        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'direccion', 'latitud', 'longitud', 'tipo']);
        $data['materiales'] = $request->input('materiales', 'No especificado (Material General)');

        $newImage = null;
        if ($request->hasFile('imagen_archivo')) {
            $newImage = Media::store($request->file('imagen_archivo'), 'puntos');
            $data['imagen'] = $newImage;
        }

        try {
            $location->update($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'puntos');
            throw $error;
        }
        return redirect()->route('mapa.index')->with('success', 'Punto actualizado exitosamente.');
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->route('mapa.index')->with('success', 'Punto eliminado exitosamente.');
    }
}
