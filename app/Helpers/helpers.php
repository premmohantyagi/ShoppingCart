<?php

if (! function_exists('format_price')) {
    function format_price(float|int|string|null $amount, ?string $currency = null): string
    {
        $amount = (float) ($amount ?? 0);
        $currency = $currency ?? \App\Models\Setting::get('currency_symbol', '₹');

        return $currency . number_format($amount, 2);
    }
}

if (! function_exists('active_route')) {
    function active_route(string|array $routes, string $class = 'active'): string
    {
        if (is_array($routes)) {
            foreach ($routes as $route) {
                if (request()->routeIs($route)) {
                    return $class;
                }
            }

            return '';
        }

        return request()->routeIs($routes) ? $class : '';
    }
}
