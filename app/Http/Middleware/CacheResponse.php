<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 5): Response
    {
        if ($request->user() || $request->method() !== 'GET') {
            return $next($request);
        }

        $key = 'page_cache_' . md5($request->fullUrl());

        return Cache::remember($key, now()->addMinutes($minutes), function () use ($request, $next) {
            return $next($request);
        });
    }
}
