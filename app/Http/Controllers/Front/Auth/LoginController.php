<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showLoginForm(): View
    {
        return view('front.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! $this->authService->login(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('front.home'));
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('front.home');
    }
}
