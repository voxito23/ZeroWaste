<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Support\Media;

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
            'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'password_actual' => 'nullable|required_with:password|current_password',
            'password'    => 'nullable|string|min:6|confirmed',
        ], [
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La imagen debe ser JPEG, PNG o WebP.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 5 MB.',
            'foto_perfil.uploaded' => 'Error al subir la imagen. Intenta con una de menor tamaño.',
            'password_actual.required_with' => 'Debes ingresar tu contraseña actual para establecer una nueva.',
            'password_actual.current_password' => 'La contraseña actual es incorrecta.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Actualización de foto de perfil
        $newImage = null;
        if ($request->hasFile('foto_perfil')) {
            try {
                $newImage = Media::store($request->file('foto_perfil'), 'perfiles');
                $user->foto_perfil = $newImage;
            } catch (\Throwable $error) {
                Log::error('No fue posible guardar la foto del perfil administrativo.', [
                    'exception' => get_class($error),
                    'user_id' => $user->id,
                ]);

                return back()->withInput()->withErrors([
                    'foto_perfil' => 'No fue posible guardar la fotografía. Intenta de nuevo o elige otra imagen.',
                ]);
            }
        }

        // Actualización de contraseña
        // NO usar Hash::make() aquí: el modelo User tiene cast 'hashed'
        // que auto-hashea al asignar. Hash::make + cast = doble hash = login roto.
        if ($request->filled('password')) {
            $user->password = $request->input('password');
        }

        try {
            $user->save();
        } catch (\Throwable $error) {
            Media::discard($newImage, 'perfiles');
            Log::error('No fue posible actualizar el perfil administrativo.', [
                'exception' => get_class($error),
                'user_id' => $user->id,
            ]);

            return back()->withInput()->withErrors([
                'perfil' => 'No fue posible guardar los cambios del perfil. Inténtalo nuevamente.',
            ]);
        }

        return redirect()->route('admin.perfil.edit')->with('success', 'Perfil actualizado correctamente.');
    }
}
