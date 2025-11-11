<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Abstract base model for all database queries.
 * It handles the database connection and provides CRUD functionalities.
 */
abstract class Model
{
    /**
     * The PDO database connection instance.
     * @var PDO|null
     */
    protected static ?PDO $pdo = null;

    /**
     * The database table associated with the model.
     * This should be overridden in child classes.
     * @var string
     */
    protected string $table;

    /**
     * Model constructor.
     * Establishes a database connection if not already present.
     */
    public function __construct()
    {
        if (self::$pdo === null) {
            $this->connect();
        }
    }

    /**
     * Establishes a database connection using settings from config.
     */
    private function connect(): void
    {
        // The config file is included to get the database credentials.
        // __DIR__ is used to ensure the path is correct from this file's location.
        require __DIR__ . '/../../config/database.php';

        // The $dsn, $user, $pass, and $options are defined in the included database.php
        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // If the connection fails, we stop the application and show an error.
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Find a single record by its ID.
     *
     * @param int $id The primary key of the record.
     * @return array|false The record as an associative array, or false if not found.
     */
    public function find(int $id)
    {
        $stmt = self::$pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Fetch all records from the model's table.
     *
     * @return array An array of associative arrays representing all records.
     */
    public function findAll(): array
    {
        $stmt = self::$pdo->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new record in the database.
     *
     * @param array $data An associative array of column => value pairs.
     * @return string|false The ID of the last inserted row, or false on failure.
     */
    public function create(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = self::$pdo->prepare($sql);

        if ($stmt->execute($data)) {
            return self::$pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Get the underlying PDO connection object.
     *
     * @return PDO|null
     */
    public static function pdo(): ?PDO
    {
        if (self::$pdo === null) {
            (new static())->connect();
        }
        return self::$pdo;
    }
}
