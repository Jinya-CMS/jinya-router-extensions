<?php

namespace Jinya\Router\Extensions\Database\Attributes;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ApiRoute
{
    /** @var MiddlewareInterface[] */
    public array $middlewares;

    /**
     * Marks an entity as findable by an api
     *
     * @param ApiRouteType $routeType The type of this route
     * @param string|null $path Can be null to use the autogenerated path
     * @param MiddlewareInterface ...$middleware The middlewares to add to the handler
     */
    public function __construct(
        public ApiRouteType $routeType,
        public string|null $path = null,
        MiddlewareInterface ...$middleware
    ) {
        $middlewares = [];
        foreach ($middleware as $item) {
            $middlewares[] = $item;
        }

        $this->middlewares = $middlewares;
    }
}
