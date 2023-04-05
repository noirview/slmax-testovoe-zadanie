<?php

namespace App\Collections;

use App\Enums\Condition;
use App\Models\Human;
use App\Services\Database;

class HumanCollection
{
    private array $ids;

    public function __construct(Condition $condition, int $id)
    {
        $connection = Database::getInstance()->connection;

        $result = pg_query_params($connection,
            "SELECT id FROM people WHERE id $condition->value $1",
            [$id]
        );

        $ids = array_map(
            fn(array $row) => intval($row['id']),
            pg_fetch_all($result)
        );

        $this->ids = $ids;
    }

    public function get(): array
    {
        $people = array_map(
            fn(int $id) => new Human($id),
        $this->ids);

        return $people;
    }

    public function delete()
    {
        $people = $this->get();

        array_map(
            fn(Human $human) => $human->delete(),
        $people);
    }
}