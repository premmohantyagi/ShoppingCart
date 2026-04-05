<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VendorRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showForm(): View
    {
        return view('vendor.auth.register');
    }

    public function register(VendorRegisterRequest $request): RedirectResponse
    {
        $user = $this->authService->registerVendor($request->validated());

        Auth::login($user);

        return redirect()->route('vendor.dashboard')
            ->with('info', 'Your vendor account is pending approval. You will be notified once approved.');
    }
}
