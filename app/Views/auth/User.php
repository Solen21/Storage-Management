<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    /**
     * Find a user by their username.
     *
     * @param string $username The username to search for.
     * @return array|false The user data as an associative array, or false if not found.
     */
    public function findByUsername(string $username)
    {
        $stmt = self::pdo()->prepare("SELECT * FROM {$this->table} WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
}