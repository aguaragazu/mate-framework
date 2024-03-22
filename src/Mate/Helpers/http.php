<?php

use Mate\Http\Request;
use Mate\Http\Response;

/**
 * Get current request.
 *
 * @return \Mate\Http\Request
 */
function request(): Request {
    return app()->request;
}

/**
 * Create a new view response.
 *
 * @param string $view
 * @param array $params
 * @param ?string $layout
 * @return \Mate\Http\Response
 */
function view(string $view, array $params = [], ?string $layout = null): Response {
    return Response::view($view, $params, $layout);
}

/**
 * Create a new json response.
 *
 * @param array $json
 * @return \Mate\Http\Response
 */
function json(array $json): Response {
    return Response::json($json);
}

/**
 * Create a new redirect response.
 *
 * @param string $response
 * @return \Mate\Http\Response
 */
function redirect(string $route): Response {
    return Response::redirect($route);
}

/**
 * Redirect back to previous URL.
 *
 * @param string $response
 * @return \Mate\Http\Response
 */
function back(): Response {
    return Response::redirect(session()->get('_previous'));
}
