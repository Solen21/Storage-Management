<?php

require_once __DIR__ . '/../Core/Model.php';

use App\Core\Model;

class User extends Model 
{
    // The table name for this model
    protected string $table = 'users';

    /**
     * Find a user by their username.
     *
     * @param string 
     * @return array|false
     */
    public function findByUsername(string $username)
    {
        $stmt = self::pdo()->prepare("SELECT * FROM {$this->table} WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
}
