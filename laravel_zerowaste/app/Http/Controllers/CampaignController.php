<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Location;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::all();
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
            'recompensa_puntos' => 'nullable|integer',
        ]);

        $data['activa'] = $request->has('activa') ? true : false;

        Campaign::create($data);
        return redirect()->route('campanas.index')->with('success', 'Campaña creada.');
    }
}
