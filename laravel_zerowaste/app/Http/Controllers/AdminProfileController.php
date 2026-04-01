<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminProfileController extends Controller
{
    /**
     * Muestra la vista de edición de perfil del administrador.
     */
    public function edit()
    {
        return view('admin.perfil');
    }

    /**
     * Actualiza la foto de perfil y/o contraseña del administrador autenticado.
     */
    public function update(Request $request)
    {
        $request->validate([
            'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password'    => 'nullable|string|min:6|confirmed',
        ], [
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La imagen debe ser de tipo jpeg, png, jpg o gif.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 2MB.',
            'foto_perfil.uploaded' => 'Error al subir la imagen. Intenta con una de menor tamaño.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Actualización de foto de perfil
        if ($request->hasFile('foto_perfil')) {
            try {
                $file = $request->file('foto_perfil');
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid('admin_') . '.' . $extension;

                // Guardar en el volumen compartido (named volume perfiles_compartidos)
                $destino = public_path('img/perfiles');
                if (!file_exists($destino)) { mkdir($destino, 0777, true); }
                $file->move($destino, $filename);

                $user->foto_perfil = $filename;
            } catch (\Exception $e) {
                error_log('No se pudo guardar la foto de perfil admin: ' . $e->getMessage());
            }
        }

        // Actualización de contraseña
        // NO usar Hash::make() aquí: el modelo User tiene cast 'hashed'
        // que auto-hashea al asignar. Hash::make + cast = doble hash = login roto.
        if ($request->filled('password')) {
            $user->password = $request->input('password');
        }

        $user->save();

        return redirect()->route('admin.perfil.edit')->with('success', 'Perfil actualizado correctamente.');
    }
}
