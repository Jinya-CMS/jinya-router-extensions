<?php

namespace Jinya\Router\Extensions\Database\Attributes;

use Jinya\Database\Deletable;

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
