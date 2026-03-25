<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::all();
        return view('admin.usuarios', compact('usuarios'));
    }

    public function create()
    {
        return view('admin.usuarios_create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:usuarios',
            'password' => 'required|string|min:6',
        ]);

        $data = $request->all();
        $data['password'] = bcrypt($data['password']);

        User::create($data);
        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }
}
