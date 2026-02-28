<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Settings\GeneralSettings;

class PosAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $providedKey = $request->header('X-POS-Key');
        $settings = app(GeneralSettings::class);
        $expectedKey = $settings->pos_pin ?? '123456';

        if ($providedKey !== $expectedKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid POS Key.'
            ], 401);
        }

        return $next($request);
    }
}
