<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isVendor()) {
            if ($request->expectsJson()) {
                abort(403, 'Unauthorized.');
            }

            return redirect()->route('vendor.login');
        }

        $vendor = $request->user()->vendor;
        if (! $vendor || ! $vendor->isApproved()) {
            if ($request->expectsJson()) {
                abort(403, 'Vendor account not approved.');
            }

            return redirect()->route('vendor.login')
                ->with('error', 'Your vendor account is pending approval.');
        }

        return $next($request);
    }
}
