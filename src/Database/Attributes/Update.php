<?php

namespace Jinya\Router\Extensions\Database\Attributes;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class Update extends ApiRoute
{
    public function __construct(string|null $path = null, MiddlewareInterface ...$middleware)
    {
        parent::__construct(ApiRouteType::Updatable, $path, ...$middleware);
    }

}
