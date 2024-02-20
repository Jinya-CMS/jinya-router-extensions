<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Jinya\Database\Deletable;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class DeleteReferencedException extends Exception
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly Deletable|null $entity,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 409, $previous);
    }
}
