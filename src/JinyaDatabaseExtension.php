<?php

namespace Jinya\Router\Extensions;

use Jinya\Router\Extensions\Database\Cache\RouteBuilder;
use Jinya\Router\Extensions\Database\ErrorHandler;
use Jinya\Router\Extensions\Database\Handlers;
use Jinya\Router\Extensions\Database\StatusErrorHandler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class JinyaDatabaseExtension extends Extension
{
    public function __construct(
        private readonly string $cacheDirectory,
        private readonly string $entityDirectory,
        ErrorHandler $errorHandler = new StatusErrorHandler()
    ) {
        Handlers::$errorHandler = $errorHandler;
    }

    private function getCacheFilePath(): string
    {
        $cacheDirectory = $this->cacheDirectory;
        if (!is_dir($this->cacheDirectory)) {
            $cacheDirectory = ((PHP_SAPI === 'cli' ? getcwd() : $_SERVER['DOCUMENT_ROOT']) . '/var/cache');
        }

        $cacheDirectory .= '/jinya/router-extensions/';
        if (!@mkdir($cacheDirectory, recursive: true) && !is_dir($cacheDirectory)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('Directory "%s" was not created', $cacheDirectory));
            // @codeCoverageIgnoreEnd
        }

        return $cacheDirectory . 'jinya-router-extensions.php';
    }

    public function recreateCache(): bool
    {
        $cacheFileModTime = filemtime($this->getCacheFilePath());
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->entityDirectory));
        foreach ($iterator as $file) {
            /** @var $file SplFileInfo */
            if ($file->isFile() && $file->getMTime() > $cacheFileModTime) {
                return true;
            }
        }

        return false;
    }

    public function additionalRoutes(): string
    {
        $routeBuilder = new RouteBuilder($this->entityDirectory);
        $cacheFileContent = $routeBuilder->getRoutes();
        $cacheFile = $this->getCacheFilePath();
        file_put_contents($cacheFile, $cacheFileContent);

        $functionName = uniqid('$jinyaRouterExtensionsRegistrationFunction', true);

        return <<<PHP
$functionName = include '$cacheFile';
$functionName(\$r);
PHP;
    }
}
