<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    public const HEADER = 'X-Request-ID';

    /**
     * Attach a server-generated correlation ID to every request. The ID is
     * never read for authentication or authorization; it exists only for
     * log correlation and support diagnostics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) Str::uuid();

        Context::add('request_id', $id);
        $request->attributes->set('request_id', $id);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }
}
