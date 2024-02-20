<?php

namespace Jinya\Router\Extensions;

use Jinya\Router\Extensions\Database\ErrorHandler;
use Jinya\Router\Extensions\Database\Exceptions\NotFoundException;
use Jinya\Router\Extensions\Database\Handlers;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

class JinyaDatabaseExtension extends Extension
{
    public function __construct(
        string $cacheDirectory,
        ErrorHandler $errorHandler
    ) {
        $routingCacheBaseDir = $cacheDirectory . DIRECTORY_SEPARATOR . 'routing' . DIRECTORY_SEPARATOR;
        if (!@mkdir($routingCacheBaseDir, recursive: true) && !is_dir($routingCacheBaseDir)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('Directory "%s" was not created', $routingCacheBaseDir));
            // @codeCoverageIgnoreEnd
        }

        Handlers::$errorHandler = $errorHandler;
    }

    public function additionalRoutes(): string
    {
        return parent::additionalRoutes();
    }
}
