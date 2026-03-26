<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Verifica que el usuario autenticado sea administrador.
     * Si no lo es, devuelve un error 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_admin) {
            abort(403, 'Acceso restringido. Solo administradores.');
        }

        return $next($request);
    }
}
