<?php

namespace Jinya\Router\Extensions\Database\Extensions;

use Jinya\Database\Migration\AbstractMigration;
use Jinya\Database\Migration\Migrator;
use Jinya\Router\Extensions\Database\Classes\ReferencedTestEntity;
use Jinya\Router\Extensions\Database\Migrations\ReferencedTestEntityMigration;
use Jinya\Router\Extensions\Database\Migrations\TestEntityMigration;
use PHPUnit\Framework\TestCase;

abstract class MigratingTestCase extends TestCase
{
    /**
     * @return AbstractMigration[]
     */
    private function getMigrations(): array
    {
        return [
            new TestEntityMigration(),
            new ReferencedTestEntityMigration(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Migrator::migrateUp($this->getMigrations());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Migrator::migrateDown(array_reverse($this->getMigrations()));
    }
}
