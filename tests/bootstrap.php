<?php

use Symfony\Component\Filesystem\Filesystem;

use function Jinya\Database\configure_jinya_database;

require_once __DIR__ . '/../vendor/autoload.php';

(new Filesystem())->remove(getenv('CACHE_DIRECTORY'));

/** @phpstan-ignore-next-line */
configure_jinya_database(getenv('CACHE_DIRECTORY') . '/Database', getenv('DATABASE_DSN'));
