<?php

namespace Mate\Http\Resources;

use Mate\Http\Resources\CollectionResource;

class AnonymousCollectionResource extends CollectionResource
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * Indicates if the collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = false;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @param  string  $collects
     * @return void
     */
    public function __construct($resource, $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }
}
