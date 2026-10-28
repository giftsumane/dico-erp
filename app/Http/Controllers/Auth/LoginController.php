<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Mostrar o formulário de login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Processar o login.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'O endereço de e-mail é obrigatório.',
            'email.email' => 'Introduza um endereço de e-mail válido.',
            'password.required' => 'A palavra-passe é obrigatória.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors([
                    'email' => 'O e-mail ou a palavra-passe estão incorrectos.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Esta conta está bloqueada ou inactiva.',
                ])
                ->onlyInput('email');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Terminar sessão.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Sessão terminada com sucesso.');
    }
}