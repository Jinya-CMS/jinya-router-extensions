<?php

namespace Jinya\Router\Extensions\Database\Classes;

use Iterator;
use Jinya\Database\Findable;
use RuntimeException;

class JustFindable implements Findable
{

    public static function findAll(string $orderBy = 'id ASC'): Iterator
    {
        throw new RuntimeException();
    }

    public static function findById(int|string $id): mixed
    {
        throw new RuntimeException();
    }

    public static function findByFilters(array $filters, string $orderBy = 'id ASC'): Iterator
    {
        throw new RuntimeException();
    }

    public static function findRange(int $start, int $count, string $orderBy = 'id ASC'): Iterator
    {
        throw new RuntimeException();
    }

    public static function countAll(): int
    {
        throw new RuntimeException();
    }

    public static function countByFilters(array $filters): int
    {
        throw new RuntimeException();
    }
}