<?php

namespace Jinya\Router\Extensions\Database\Cache;

use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;

use function FastRoute\simpleDispatcher;

class RouteBuilderTest extends TestCase
{
    public function testGetRoutes(): void
    {
        $routeBuilder = new RouteBuilder(__DIR__ . '/../Classes');
        $routes = $routeBuilder->getRoutes();

        self::assertNotEmpty($routes);
        $function = eval($routes);

        $dispatcher = simpleDispatcher($function);
        $getDispatch = $dispatcher->dispatch('GET', '/api/test');
        // [self::FOUND, $handler, ['varName' => 'value', ...]]
        self::assertEquals(Dispatcher::FOUND, $getDispatch[0]);
        self::assertEmpty($getDispatch[2]);

        $getHandler = $getDispatch[1];
        self::assertEquals('fn', $getHandler[0]);
        self::assertIsCallable($getHandler[1]);
        self::assertIsArray($getHandler[2]);
    }
}
