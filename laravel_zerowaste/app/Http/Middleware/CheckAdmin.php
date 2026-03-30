<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica que esté autenticado y que sea administrador
        if (!Auth::check() || !Auth::user()->is_admin) {
            Auth::logout(); // Destruye la sesión no autorizada
            
            return redirect()->route('login')->withErrors([
                'error' => 'Acceso restringido: Se requieren credenciales de administrador'
            ]);
        }

        return $next($request);
    }
}
