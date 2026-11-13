<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $subscription = app('activeCompany')->currentSubscription()->first();

        // Empresas anteriores à introdução das subscrições permanecem activas.
        if (! $subscription || $subscription->isUsable()) {
            return $next($request);
        }

        return redirect()->route('settings.subscription.show')->with(
            'error',
            'A subscrição desta empresa não está activa. Contacte a DICO Solutions para renovar ou regularizar o serviço.'
        );
    }
}
