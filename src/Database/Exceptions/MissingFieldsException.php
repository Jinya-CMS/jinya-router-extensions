<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class MissingFieldsException extends Exception
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly array $missingFields,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}
