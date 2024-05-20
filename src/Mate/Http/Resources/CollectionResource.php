<?php

namespace Mate\Http\Resources;

use Countable;
use IteratorAggregate;
use Mate\Http\Request;
use Mate\Http\Resources\JsonResource;

/**
 * A collection of resources.
 */

class CollectionResource extends JsonResource implements Countable, IteratorAggregate
{
  use CollectsResources;
  /**
   * The mapped collection instance.
   *
   * @var \Mate\Collections\Collection
   */
  public $collection;

  /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

  
  protected $resourceClass;

  /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {

  // public function __construct(array $collection, string $resourceClass = Resource::class)
  // {

    parent::__construct($resource);

    $this->resource = $this->collectResource($resource);

    // $this->resourceClass = $resourceClass;
    // $this->collection = $collection;


  }

  /**
   * Transform the resource into an array.
   *
   * @param  \Mate\Http\Request  $request
   * @return array|\Mate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray(Request $request)
  {

    return $this->collection->map->toArray($request)->all();
  }

  /**
   * Create an HTTP response that represents the object.
   *
   * @param  \Mate\Http\Request  $request
   * @return \Mate\Http\JsonResponse
   */
  public function toResponse($request): \Mate\Http\JsonResponse
  {
    return parent::toResponse($request);
  }

  // public function toJson(Request $request): string
  // {
  //   return json_encode($this->toArray($request));
  // }

  /**
   * Counts the number of resources in the collection.
   *
   * @return int The number of resources in the collection.
   */
  public function count(): int
  {
    return $this->collection->count();
  }

  // public static function resolve(array $collection, string $resourceClass = Resource::class): CollectionResource
  // {
  //   if (empty($collection)) {
  //     return new CollectionResource([], $resourceClass);
  //   }

  //   return new CollectionResource($collection, $resourceClass);
  // }

}
