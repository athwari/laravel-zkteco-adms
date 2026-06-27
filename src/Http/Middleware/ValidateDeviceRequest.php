<?php

namespace Athwari\LaravelZktecoAdms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeviceRequest
{
    /**
     * Validate that the request body does not exceed the configured max body size.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maxSize = config('zkteco-adms.max_body_size', 10 * 1024 * 1024);

        if ($request->header('Content-Length') && (int) $request->header('Content-Length') > $maxSize) {
            return response('Request body too large', 413);
        }

        return $next($request);
    }
}
