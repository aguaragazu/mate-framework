<?php

namespace Mate\Http;

use Closure;
use Mate\Routing\Route;
use Mate\Storage\File;
use Mate\Validation\Validator;

/**
 * HTTP request.
 */
class Request
{
    /**
     * Path part of the request URI
     *
     * @var string
     */
    private string $path;

    /**
     * URI requested by the client.
     *
     * @var string
     */
    protected string $uri;

    /**
     * Route matched by URI.
     *
     * @var Route
     */
    protected Route $route;

    /**
     * HTTP method used for this request.
     *
     * @var HttpMethod
     */
    protected HttpMethod $method;

    /**
     * POST data.
     *
     * @var array
     */
    protected array $data;

    /**
     * Query parameters.
     *
     * @var array
     */
    protected array $query;

    protected array $headers = [];

    /**
     * Uploaded files.
     *
     * @var array<string, \Mate\Storage\File>
     */
    protected array $files = [];

    /**
     * @var \Closure
     */
    protected $userResolver;

    public function __construct()
    {
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->query = $this->sanitize($_GET, INPUT_GET);
        $this->headers = $_SERVER;
        $this->method = $this->method();
        $this->data = $this->sanitize($_POST, INPUT_POST);
        $this->files = $this->createFiles($_FILES);

        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        return $this;
    }


    /**
     * Get the request URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Set request URI.
     *
     * @param string $uri
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get route matched by the URI of this request.
     *
     * @return Route
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * Set route for this request.
     *
     * @param Route $route
     * @return self
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Get the request HTTP method.
     *
     * @return HttpMethod
     */
    public function method(): HttpMethod
    {
        if (!isset($this->method)) {
            $this->method = HttpMethod::from($_SERVER['REQUEST_METHOD']);
        }

        return $this->method;
    }

    /**
     * Set HTTP method.
     *
     * @param HttpMethod $method
     * @return self
     */
    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function headers(string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->headers;
        }

        return $this->headers[strtolower($key)] ?? null;
    }

    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->headers[strtolower($header)] = $value;
        }

        return $this;
    }

    /**
     * Get file from request.
     *
     * @param string $name
     * @return File|null
     */
    public function file(string $name): ?File
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Set uploaded files.
     *
     * @param array<string, \Mate\Storage\File> $files
     * @return self
     */
    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * Get all POST data as key-value or get only specific value by providing
     * a `$key`.
     *
     * @return array|string|null Null if the key doesn't exist, the value of
     * the key if it is present or all the data if no key was provided.
     */
    public function data(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    /**
     * Set POST data.
     *
     * @param array $data
     * @return self
     */
    public function setPostData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get all query params as key-value or get only specific value by providing
     * a `$key`.
     *
     * @return array|string|null Null if the key doesn't exist, the value of
     * the key if it is present or all the query params if no key was provided.
     */
    public function query(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? null;
    }

    /**
     * Get specific key from request data.
     *
     * @return ?string
     */
    public function get(string $key): ?string {
        return $this->data[$key] ?? null;
    }

    /**
     * Set query parameters.
     *
     * @param array $query
     * @return self
     */
    public function setQueryParameters(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Get all route params as key-value or get only specific value by providing
     * a `$key`.
     *
     * @return array|string|null Null if the key doesn't exist, the value of
     * the key if it is present or all the route params if no key was provided.
     */
    public function routeParameters(?string $key = null): array|string|null
    {
        $parameters = $this->route->parseParameters($this->uri);

        if (is_null($key)) {
            return $parameters;
        }

        return $parameters[$key] ?? null;
    }

    public function validate(array $rules, array $messages = []): array
    {
        $validator = new Validator($this->data);
        return $validator->validate($rules, $messages);
    }

    /**
     * Extracts the bearer token from the authorization header.
     *
     * @return string|null The bearer token if it exists, otherwise null.
     */
    public function bearerToken(): ?string
    {
        $authorizationHeader = $this->headers('Authorization');

        if (is_null($authorizationHeader)) {
            return null;
        }

        if (preg_match('/^Bearer\s+(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getPath(): string {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    
    }

    /**
     * Get the path portion of the URI (example: app.com/route/1 returns /route/1)
     *
     * @return string
     */
    public function path(): string {
        return $this->path;
    }

    /**
     * Remove special chars from given data.
     *
     * @param array $data
     * @param int $type One of `INPUT_GET`, `INPUT_POST`
     * @return array
     */
    protected function sanitize(array $data, int $type): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = filter_input($type, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $sanitized;
    }

    /**
     * Convert received files into File objects.
     *
     * @param array $from
     * @return array
     */
    protected function createFiles(array $from): array {
        $files = [];
        foreach ($from as $key => $file) {
            if (!empty($file["tmp_name"])) {
                $basename = basename($file["tmp_name"]);
                $files[$key] = new File(
                    $basename,
                    file_get_contents($file["tmp_name"]),
                    $file["type"]
                );
            }
        }

        return $files;
    }

    /**
     * Get the user making the request.
     *
     * @param  string|null  $guard
     * @return mixed
     */
    public function user($guard = null) { 
        return call_user_func($this->userResolver(), $guard);
    }   

    /**
     * Get the user resolver callback.
     *
     * @return \Closure
     */
    public function userResolver() {
        return $this->userResolver ?: function () { 
            //
        };
    }

    /**
     * Set the user resolver callback.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setUserResolver(Closure $callback)
    {
        $this->userResolver = $callback;

        return $this;
    }
}
