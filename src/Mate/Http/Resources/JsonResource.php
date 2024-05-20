<?php
namespace Mate\Http\Resources;

use ArrayAccess;
use JsonSerializable;
use Mate\Contracts\Support\Arrayable;
use Mate\Contracts\Support\Responsable;
use Mate\Database\Exception\JsonEncodingException;
use Mate\Http\DelegatesToResource;
use Mate\Http\JsonResponse;
use Mate\Http\Request;

abstract class JsonResource implements JsonSerializable, ArrayAccess, Responsable
{
    use ConditionallyLoadsAttributes,DelegatesToResource;

    protected $resource;
    protected static $wrap = 'data';
    protected $with = []; 

    /**
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    // Método mágico para acceder a propiedades del modelo directamente
    public function __get($name) {
        return $this->resource->{$name};
    }

    // Permite llamar métodos del modelo directamente
    public function __call($name, $arguments) {
        return $this->resource->{$name}(...$arguments);
    }

    public static function make( ...$parameters) {
        return new static(...$parameters);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Mate\Http\Request  $request
     * @return array|\Mate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        if ($request) {
            return $this->wrappedData($request);
        }
        return is_array($this->resource) 
            ? $this->resource 
            : $this->resource->toArray();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Mate\Database\Exception\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw JsonEncodingException::forResource($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Mate\Http\Request  $request
     * @return array
     */
    public function with(Request $request)
    {
        return $this->with;
    }

    public function jsonOptions()
    {
        return 0;
    }

    public function jsonSerialize(): array
    {
        return $this->resolve(request());
    }

    public static function wrap($wrap)
    {
        static::$wrap = $wrap;
    }

    protected function wrappedData(Request $request)
    {
        return [static::$wrap => $this->toArray($request)];
    }

    /**
     * Transform the resource into an HTTP response.
     *
     * @param  \Mate\Http\Request|null  $request
     * @return \Mate\Http\JsonResponse
     */
    public function response($request = null)
    {
        return $this->toResponse(
            $request
        );
    }

    public function toResponse($request): \Mate\Http\JsonResponse
    {
        return (new ResponseResource($this))->toResponse($request);
    }

    /**
     * Customize the response for a request.
     *
     * @param  \Mate\Http\Request  $request
     * @param  \Mate\Http\JsonResponse  $response
     * @return void
     */
    public function withResponse(Request $request, JsonResponse $response)
    {
        //
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return \Mate\Http\Resources\AnonymousCollectionResource
     */
    public static function collection($resource)
    {
        return tap(static::newCollection($resource), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Create a new resource collection instance.
     *
     * @param  mixed  $resource
     * @return \Mate\Http\Resources\AnonymousCollectionResource
     */
    protected static function newCollection($resource)
    {
        return new AnonymousCollectionResource($resource, static::class);
    }

    /**
     * Resolve the resource to an array.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return array
     */
    public function resolve($request = null)
    {
        $data = $this->toArray(
            $request = $request ?: request()
        );

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }
}