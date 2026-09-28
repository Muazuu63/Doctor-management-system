<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ($roles && ! in_array($user->role, $roles, true))) {
            abort(403, 'এই পেজে আপনার অনুমতি নেই।');
        }

        return $next($request);
    }
}
