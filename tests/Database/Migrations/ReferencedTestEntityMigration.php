<?php

namespace Jinya\Router\Extensions\Database\Migrations;

use Jinya\Database\Migration\AbstractMigration;
use Jinya\Router\Extensions\Database\Classes\ReferencedTestEntity;
use Jinya\Router\Extensions\Database\Classes\TestEntity;
use PDO;

use function Jinya\Router\Extensions\get_identity;

class ReferencedTestEntityMigration extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function up(PDO $pdo): void
    {
        $identity = get_identity();

        $testEntityTableName = TestEntity::getTableName();
        $tableName = ReferencedTestEntity::getTableName();
        $pdo->exec(
            "
        create table $tableName (
            id integer primary key $identity,
            name varchar(255) not null,
            display_name varchar(255) not null,
            date timestamp not null,
            test_entity_id integer not null,
            constraint fk_test_entity
                foreign key(test_entity_id) 
                    references $testEntityTableName(id)
        )"
        );

        $rows = [];
        for ($i = 11; $i < 21; ++$i) {
            $rows[] = [
                'name' => "Test $i",
                'display_name' => "Test case $i",
                'date' => "20$i-09-11 20:34:25",
                'test_entity_id' => $i - 10
            ];
        }

        $statement = ReferencedTestEntity::getQueryBuilder()
            ->newInsert()
            ->into(ReferencedTestEntity::getTableName())
            ->addRows($rows);
        $pdo->prepare($statement->getStatement())->execute($statement->getBindValues());
    }

    /**
     * @inheritDoc
     */
    public function down(PDO $pdo): void
    {
        $tableName = ReferencedTestEntity::getTableName();
        $pdo->exec("drop table $tableName");
    }
}
