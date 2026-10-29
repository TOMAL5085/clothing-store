<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class BlocklistedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1/admin/*') || ! $request->is('api/*') || ! Schema::hasTable('blocked_ips')) {
            return $next($request);
        }

        if (BlockedIp::where('ip_address', $request->ip())->exists()) {
            return response()->json(['message' => 'Access from this IP address is blocked.'], 403);
        }

        return $next($request);
    }
}
