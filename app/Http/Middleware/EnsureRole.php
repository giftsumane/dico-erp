<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        $company = app('activeCompany');

        if (! $user || ! $company) {
            abort(403);
        }

        $companyUser = $company->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->first();

        if (! $companyUser) {
            abort(
                403,
                'Não tens acesso a esta empresa.'
            );
        }

        $role = $companyUser->pivot->role;

        if (! in_array($role, $roles, true)) {
            abort(
                403,
                'Não tens permissão para aceder a esta área.'
            );
        }

        /*
         * Disponibilizar o perfil actual.
         */
        app()->instance(
            'activeRole',
            $role
        );

        view()->share(
            'activeRole',
            $role
        );

        return $next($request);
    }
}