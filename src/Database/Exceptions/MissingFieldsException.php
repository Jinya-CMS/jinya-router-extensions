<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class MissingFieldsException extends Exception
{
    /**
     * @param ServerRequestInterface $request - The server request object.
     * @param string[] $missingFields - An array of missing fields.
     * @param string $message - Optional. A custom error message.
     * @param Throwable|null $previous - Optional. A previous exception that caused this exception.
     */
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly array $missingFields,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}
