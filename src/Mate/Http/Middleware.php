<?php

namespace Mate\Http;

use Closure;

/**
 * HTTP Middleware.
 */
interface Middleware {
    /**
     * Handle the request and return a response, or call the next middleware.
     *
     * @param \Mate\Http\Request $request
     * @param \Close $next
     * @return \Mate\Http\Response
     */
    public function handle(Request $request, Closure $next): Response;
}
