<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Application-level response headers that are always safe for this
     * JSON API and its SPA consumer. Transport security (HSTS/TLS) and
     * edge filtering (WAF/rate shielding) belong to the production reverse
     * proxy and are documented in CONTEXT.md instead.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        return $response;
    }
}
