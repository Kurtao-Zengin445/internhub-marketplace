<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        abort_if(!$company, 403, 'Profil perusahaan belum tersedia.');
        abort_if(!$company->is_verified, 403, 'Perusahaan harus diverifikasi admin terlebih dahulu.');

        return $next($request);
    }
}
