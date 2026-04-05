<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showRegistrationForm(): View
    {
        return view('front.auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authService->registerCustomer($request->validated());

        Auth::login($user);

        return redirect()->route('front.home')
            ->with('success', 'Registration successful! Welcome to ShoppingCart.');
    }
}
