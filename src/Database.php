<?php

namespace App;

use PDO;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $this->pdo = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTable();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    private function createTable()
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS transacoes (
            id TEXT PRIMARY KEY,
            valor REAL NOT NULL,
            dataHora TEXT NOT NULL
        )');
    }
}
