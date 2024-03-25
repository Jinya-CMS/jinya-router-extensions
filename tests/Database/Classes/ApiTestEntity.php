<?php

namespace Jinya\Router\Extensions\Database\Classes;

use DateTime;
use Jinya\Database\Attributes\Column;
use Jinya\Database\Attributes\Id;
use Jinya\Database\Attributes\Table;
use Jinya\Database\Entity;
use Jinya\Router\Extensions\Database\Attributes\ApiIgnore;
use Jinya\Router\Extensions\Database\Attributes\Create;
use Jinya\Router\Extensions\Database\Attributes\Delete;
use Jinya\Router\Extensions\Database\Attributes\Find;
use Jinya\Router\Extensions\Database\Attributes\Update;

#[Find('/api/test')]
#[Create]
#[Delete]
#[Update]
#[Table('api_test_entity')]
class ApiTestEntity extends Entity
{
    #[Id]
    #[Column(autogenerated: true)]
    public int $id;

    #[Column]
    public string $name;

    #[Column]
    #[ApiIgnore]
    public string $ignored = '';

    #[Column(sqlName: 'display_name')]
    public string $displayName;

    #[Column]
    public DateTime $date;

    public string $unused = '';
}
