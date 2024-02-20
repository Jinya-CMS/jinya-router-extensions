<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class NotFoundException extends Exception
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 404, $previous);
    }
}
