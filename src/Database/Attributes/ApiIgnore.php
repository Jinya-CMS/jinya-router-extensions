<?php

namespace Jinya\Router\Extensions\Database\Attributes;

use Attribute;

/**
 * Added to a property, this property will be ignored in post and put requests
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiIgnore
{
}
