<?php

namespace Mate\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Mate\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue();
}