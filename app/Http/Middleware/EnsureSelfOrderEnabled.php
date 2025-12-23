<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSelfOrderEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(GeneralSettings::class);

        if (!$settings->enable_self_order) {
            abort(403, 'Self Order Module is disabled.');
        }

        return $next($request);
    }
}
