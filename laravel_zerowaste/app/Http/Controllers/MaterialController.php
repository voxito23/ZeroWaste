<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::all();
        return view('admin.materiales', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|string|max:50',
            'unidades_medida' => 'required|string|max:20',
            'valor_puntos' => 'nullable|integer'
        ]);

        Material::create($request->all());
        return redirect()->route('materiales.index')->with('success', 'Material agregado.');
    }
}
