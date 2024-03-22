<?php

namespace Mate\Exceptions;

/**
 * Global exception, handled by the framework. Exceptions caused by user
 * code will be caught as well to return HTTP 500 INTERNAL SERVER ERROR
 * response.
 */
class MateException extends \Exception {
    //
}
