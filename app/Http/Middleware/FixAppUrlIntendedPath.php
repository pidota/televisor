<?php

namespace App\Http\Middleware;

use App\Support\SubdirectoryUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixAppUrlIntendedPath
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has('url.intended')) {
            $request->session()->put(
                'url.intended',
                SubdirectoryUrl::ensureAbsolute((string) $request->session()->get('url.intended')),
            );
        }

        return $next($request);
    }
}
