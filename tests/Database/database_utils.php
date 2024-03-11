<?php

namespace Jinya\Router\Extensions;

function get_identity(): string
{
    return match (getenv('DATABASE_TYPE')) {
        'mysql' => 'auto_increment',
        'sqlite' => 'autoincrement',
        'pgsql' => 'generated always as identity',
        default => throw new \RuntimeException(),
    };
}
