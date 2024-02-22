<?php

namespace Jinya\Router\Extensions;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

function get_identity(): string
{
    return match (getenv('DATABASE_TYPE')) {
        'mysql' => 'auto_increment',
        'sqlite' => 'autoincrement',
        'pgsql' => 'generated always as identity',
        default => throw new \RuntimeException(),
    };
}
