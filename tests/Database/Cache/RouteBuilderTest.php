<?php

namespace Jinya\Router\Extensions\Database\Cache;

use FastRoute\Dispatcher;
use Jinya\Router\Extensions\Database\Extensions\MigratingTestCase;
use Jinya\Router\Http\FunctionMiddleware;

use function FastRoute\simpleDispatcher;

class RouteBuilderTest extends MigratingTestCase
{
    public function testGetRoutes(): void
    {
        $routeBuilder = new RouteBuilder(__DIR__ . '/../Classes');
        $routes = $routeBuilder->getRoutes();

        self::assertNotEmpty($routes);
        $function = eval($routes);

        $dispatcher = simpleDispatcher($function);

        $getAllDispatch = $dispatcher->dispatch('GET', '/api/test');
        self::assertEquals(Dispatcher::FOUND, $getAllDispatch[0]);
        self::assertEmpty($getAllDispatch[2]);
        self::assertIsArray($getAllDispatch[2]);

        $handler = $getAllDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);

        $getDispatch = $dispatcher->dispatch('GET', '/api/test/1');
        self::assertEquals(Dispatcher::FOUND, $getDispatch[0]);
        self::assertNotEmpty($getDispatch[2]);
        self::assertIsArray($getDispatch[2]);
        self::assertArrayHasKey('id', $getDispatch[2]);

        $handler = $getDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);

        $putDispatch = $dispatcher->dispatch('PUT', '/api/api-test-entity/1');
        self::assertEquals(Dispatcher::FOUND, $putDispatch[0]);
        self::assertNotEmpty($putDispatch[2]);
        self::assertIsArray($putDispatch[2]);
        self::assertArrayHasKey('id', $putDispatch[2]);

        $handler = $putDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);

        $postDispatch = $dispatcher->dispatch('POST', '/api/api-test-entity');
        self::assertEquals(Dispatcher::FOUND, $postDispatch[0]);
        self::assertEmpty($postDispatch[2]);
        self::assertIsArray($postDispatch[2]);

        $handler = $postDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);

        $deleteDispatch = $dispatcher->dispatch('DELETE', '/api/api-test-entity/1');
        self::assertEquals(Dispatcher::FOUND, $deleteDispatch[0]);
        self::assertNotEmpty($deleteDispatch[2]);
        self::assertIsArray($deleteDispatch[2]);
        self::assertArrayHasKey('id', $deleteDispatch[2]);

        $handler = $deleteDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);

        // Method not allowed results
        $getAllDispatch = $dispatcher->dispatch('GET', '/api/api-test-entity');
        self::assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $getAllDispatch[0]);

        $getDispatch = $dispatcher->dispatch('GET', '/api/api-test-entity/1');
        self::assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $getDispatch[0]);

        $putDispatch = $dispatcher->dispatch('PUT', '/api/test/1');
        self::assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $putDispatch[0]);

        $postDispatch = $dispatcher->dispatch('POST', '/api/test');
        self::assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $postDispatch[0]);

        $deleteDispatch = $dispatcher->dispatch('DELETE', '/api/test/1');
        self::assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $deleteDispatch[0]);

        $getAllWithMiddlewareDispatch = $dispatcher->dispatch('GET', '/api/api-test-entity-with-middleware');
        self::assertEquals(Dispatcher::FOUND, $getAllWithMiddlewareDispatch[0]);
        self::assertEmpty($getAllWithMiddlewareDispatch[2]);
        self::assertIsArray($getAllWithMiddlewareDispatch[2]);

        $handler = $getAllWithMiddlewareDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);
        self::assertInstanceOf(FunctionMiddleware::class, $handler[2][0]);

        $getWithMiddlewareDispatch = $dispatcher->dispatch('GET', '/api/api-test-entity-with-middleware/1');
        self::assertEquals(Dispatcher::FOUND, $getWithMiddlewareDispatch[0]);
        self::assertNotEmpty($getWithMiddlewareDispatch[2]);
        self::assertIsArray($getWithMiddlewareDispatch[2]);
        self::assertArrayHasKey('id', $getWithMiddlewareDispatch[2]);

        $handler = $getWithMiddlewareDispatch[1];
        self::assertEquals('fn', $handler[0]);
        self::assertIsCallable($handler[1]);
        self::assertIsArray($handler[2]);
        self::assertInstanceOf(FunctionMiddleware::class, $handler[2][0]);
    }
}
