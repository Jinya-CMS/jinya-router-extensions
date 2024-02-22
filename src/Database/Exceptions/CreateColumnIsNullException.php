<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Jinya\Database\Creatable;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class CreateColumnIsNullException extends Exception
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly Creatable $entity,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}
