<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class MapController extends Controller
{
    public function index()
    {
        $locations = Location::all();
        return view('admin.mapa.index', compact('locations')); // Changed view path
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
            'materiales' => 'nullable|string',
        ]);

        Location::create($request->all());

        return redirect()->route('mapa.index')->with('success', 'Punto de reciclaje creado exitosamente.');
    }
}
