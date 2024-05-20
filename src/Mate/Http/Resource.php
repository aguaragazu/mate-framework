<?php

namespace Mate\Http;

use Mate\Http\Resources\JsonResource;

class Resource extends JsonResource
{

    public function toArray(Request $request = null): array {
        // Suponiendo que $this->resource ya es un array o tiene un método toArray()
        return is_array($this->resource) ? $this->resource : $this->resource->toArray();
    }
}

