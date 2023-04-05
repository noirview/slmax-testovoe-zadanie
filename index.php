<?php

require_once __DIR__ . "/vendor/autoload.php";

use App\Services\Database;

use App\Enums\Condition;
use App\Enums\Gender;

use App\Models\Human;
use App\Collections\HumanCollection;

$connection = Database::getInstance()->connection;

pg_query($connection, "create table people
(
    id         serial
        primary key,
    first_name varchar(100) not null,
    last_name  varchar(100) not null,
    birth_at   date         not null,
    gender     integer,
    birth_city varchar(100)
)");

$human = new Human(
    'Антон',
    'Степурко',
    new DateTime('1994-02-10'),
    Gender::MALE
);

$human->dump();

$collection = new HumanCollection(Condition::LESS, 9);

array_map(
    fn(Human $human) => $human->dump(),
    $collection->get());