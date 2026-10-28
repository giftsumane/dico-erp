<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ): Response {
        $company = app('activeCompany');

        abort_unless(
            $company
                && $company->hasFeature($feature),
            403,
            'Esta funcionalidade não está activa para esta empresa.'
        );

        return $next($request);
    }
}