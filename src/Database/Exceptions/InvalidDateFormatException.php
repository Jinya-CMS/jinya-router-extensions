<?php

namespace Jinya\Router\Extensions\Database\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class InvalidDateFormatException extends Exception
{
    /**
     * @param ServerRequestInterface $request
     * @param string $date
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly string $date,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}
