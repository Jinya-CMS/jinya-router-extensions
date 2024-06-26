<?php

namespace Jinya\Router\Extensions\Database\Migrations;

use Jinya\Database\Migration\AbstractMigration;
use Jinya\Router\Extensions\Database\Classes\ApiTestEntity;
use PDO;

use function Jinya\Router\Extensions\get_identity;

class ApiTestEntityMigration extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function up(PDO $pdo): void
    {
        $identity = get_identity();

        $tableName = ApiTestEntity::getTableName();
        $pdo->exec(
            "
        create table $tableName (
            id integer primary key $identity,
            name varchar(255) not null unique,
            ignored varchar(255) not null,
            display_name varchar(255) not null,
            date timestamp not null
        )"
        );

        $rows = [];
        for ($i = 11; $i < 21; ++$i) {
            $rows[] = [
                'name' => "Test $i",
                'ignored' => "Ignored $i",
                'display_name' => "Test case $i",
                'date' => "20$i-09-11 20:34:25"
            ];
        }

        $statement = ApiTestEntity::getQueryBuilder()
            ->newInsert()
            ->into(ApiTestEntity::getTableName())
            ->addRows($rows);
        $pdo->prepare($statement->getStatement())->execute($statement->getBindValues());
    }

    /**
     * @inheritDoc
     */
    public function down(PDO $pdo): void
    {
        $tableName = ApiTestEntity::getTableName();
        $pdo->exec("drop table $tableName");
    }
}
