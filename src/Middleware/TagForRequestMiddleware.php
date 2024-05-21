<?php

namespace Artemis\Repository\Middleware;

use Closure;
use Artemis\Repository\Cache\FlushCache;
use Illuminate\Http\Request;

class TagForRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $request->tag = uniqid('request_') . uniqid('-');
        $response = $next($request);
        FlushCache::request($request);
        return $response;
    }
}
