<?php

namespace Mate\Support;

interface Arrayable
{
    /**
     * Convierte la instancia a un array.
     *
     * @return array El array que representa la instancia.
     */
    public function toArray(): array;
}