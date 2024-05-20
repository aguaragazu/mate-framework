<?php

namespace Mate\Routing;

abstract class RoutingBase
{
    protected array $attributesAllowed = [
        'middleware',
        'name',
        'namespace',
        'prefix',
    ];

    protected array $methodsAllowed = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
    ];
}