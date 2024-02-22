<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Jinya\Database\Updatable;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class UpdateColumnIsNullException extends Exception
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly Updatable|null $entity,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}
