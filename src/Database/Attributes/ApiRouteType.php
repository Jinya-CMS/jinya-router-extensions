<?php

namespace Jinya\Router\Extensions\Database\Attributes;

/**
 * @internal
 */
enum ApiRouteType
{
    case Findable;
    case Creatable;
    case Updatable;
    case Deletable;
}
