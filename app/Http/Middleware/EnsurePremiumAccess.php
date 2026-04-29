<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumAccess
{
    public function handle(Request $request, Closure $next, string $owner = 'user'): Response
    {
        $account = $owner === 'company'
            ? $request->user()?->company
            : $request->user();

        abort_if(!$account || !$account->hasActivePremium(), 403, 'Fitur ini hanya tersedia untuk akun premium.');

        return $next($request);
    }
}
