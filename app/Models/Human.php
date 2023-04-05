<?php

namespace App\Models;

use App\Enums\Gender;
use App\Services\Database;

class Human
{
    public ?int $id;
    public string $firstName;
    public string $lastName;
    public \DateTime $birthAt;
    public ?Gender $gender;
    public ?string $birthPlace;

    public function __construct(...$args)
    {
        $countArgs = count($args);

        if (
            $countArgs == 1
            && is_int($args[0])
        ) {
            $id = $args[0];

            return $this->find($id);
        } elseif (
            $countArgs >= 3
            && $countArgs <= 5
            && is_string($args[0])
            && is_string($args[1])
            && $args[2] instanceof \DateTime
        ) {
            $name = $args[0];
            $lastName = $args[1];
            $birthday = $args[2];
            $gender = $args[3] ?? null;
            $birthPlace = $args[4] ?? null;

            return $this->store($name, $lastName, $birthday, $gender, $birthPlace);
        } else {
            throw new \InvalidArgumentException();
        }
    }

    private function insert(array $data): int|bool
    {
        $database = Database::getInstance();
        $connection = $database->connection;

        $data = array_filter($data);

        $columns = array_keys($data);
        $values = array_values($data);

        $columnsString = join(', ', $columns);
        $columnIndexes = range(1, count(array_keys($columns)));
        $placeholders = array_map(fn(int $index) => "$" . $index, $columnIndexes);
        $valuesString = join(', ', $placeholders);

        $queryString = "INSERT INTO people (" . $columnsString . ") VALUES (" . $valuesString . ") RETURNING id;";

        $result = pg_query_params($connection, $queryString, $values);

        if ($result) {
            return intval(pg_fetch_assoc($result)['id']);
        }

        return false;
    }

    private function select(int $id): array|bool
    {
        $database = Database::getInstance();
        $connection = $database->connection;

        $result = pg_query_params($connection,
            'SELECT ' .
            'first_name,' .
            'last_name,' .
            'birth_at,' .
            'gender,' .
            'birth_city' .
            ' FROM people WHERE id = $1', ['id' => $id]);

        if ($result) {
            $data = pg_fetch_row($result);

            return $data;
        }

        return false;
    }

    private function update(array $data): bool
    {
        $database = Database::getInstance();
        $connection = $database->connection;


        $data = array_filter($data);

        $columns = array_keys($data);

        $valuesString = join(', ',
            array_map(fn(string $column, int $index) => "$column=$$index", $columns, range(1, count($columns))),
        );

        $result = pg_query_params($connection,
            "UPDATE people SET $valuesString WHERE id=" . $this->id,
            $data
        );

        return !!$result;
    }

    public function store(string $firstName, string $lastName, \DateTime $birthday, ?Gender $gender, ?string $birthPlace): Human|null
    {
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_at' => $birthday->format('d-m-Y'),
            'gender' => $gender->value,
            'birth_place' => $birthPlace,
        ];

        $id = $this->insert($data);

        if ($id) {
            $this->id = $id;
            $this->firstName = $firstName;
            $this->lastName = $lastName;
            $this->gender = $gender;
            $this->birthAt = $birthday;
            $this->birthPlace = $birthPlace;

            return $this;
        }

        return null;
    }

    public function find(int $id): Human|null
    {
        $data = $this->select($id);

        if ($data) {
            $this->id = $id;
            $this->firstName = $data[0];
            $this->lastName = $data[1];
            $this->birthAt = new \DateTime($data[2]);
            $this->gender = Gender::from(intval($data[3]));
            $this->birthPlace = $data[4];

            return $this;
        }

        throw new \Exception('Не найдена сущность с id = ' . $id);
    }

    public function save(): bool
    {
        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birth_at' => $this->birthAt->format('d-m-Y'),
            'gender' => $this->gender->value,
            'birth_place' => $this->birthPlace,
        ];

        if (isset($this->id)) {
            return $this->update($data);
        }

        $this->id = $this->insert($data);
        return true;
    }

    public function delete(): bool
    {
        $database = Database::getInstance();
        $connection = $database->connection;

        $result = pg_query_params($connection,
            'DELETE FROM people WHERE id = $1', [$this->id]);

        if ($result) {
            unset($this->id);
        }

        return !!$result;
    }

    public static function age(\DateTime $birthAt): int
    {
        $now = new \DateTime();
        $age = date_diff($now, $birthAt);

        return $age->y;
    }

    public static function gender(Gender $gender): string
    {
        return match ($gender) {
            Gender::MALE => 'Мужской',
            Gender::FEMALE => 'Женский',
        };
    }

    public function dump()
    {
        $result = '';

        $age = self::age($this->birthAt);

        if (isset($this->id)) {
            $result .= "ID: $this->id" . PHP_EOL;
        }

        $result .= "Имя: $this->firstName" . PHP_EOL;
        $result .= "Фамилия: $this->lastName" . PHP_EOL;
        $result .= "Возраст: $age" . PHP_EOL;

        if ($this->gender) {
            $gender = self::gender($this->gender);
            $result .= "Пол: $gender" . PHP_EOL;
        }

        if ($this->birthPlace) {
            $result .= "Город рождения: $this->birthPlace" . PHP_EOL;
        }

        echo $result . PHP_EOL;
    }
}