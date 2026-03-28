<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Verifica que el usuario autenticado tenga permisos de administrador.
     * Devuelve un error 403 si no se cumplen los permisos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_admin) {
            abort(403, 'Acceso restringido. Solo administradores.');
        }

        return $next($request);
    }
}
