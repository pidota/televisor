<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->roles()->whereIn('name', $roles)->exists()) {
            return $next($request);
        }

        abort(403, 'No tiene permisos para acceder a esta sección.');
    }
}
