<?php

namespace App\Services;

use PgSql\Connection;

class Database
{
    protected static self $instance;
    public Connection $connection;

    private function __construct() {
        $this->connection = pg_connect("host=localhost port=5432 dbname=lady user=postgres password=postgres");
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}