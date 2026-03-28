<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Procesa el inicio de sesión del panel de administración.
     * Valida las credenciales contra la tabla de usuarios.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            // Verificar permisos de administrador
            if (!Auth::user()->is_admin) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['email' => 'Acceso restringido. Solo administradores.']);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Correo o contraseña incorrectos.',
        ])->withInput(['email' => $request->email]);
    }

    /**
     * Cierra la sesión del administrador.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
