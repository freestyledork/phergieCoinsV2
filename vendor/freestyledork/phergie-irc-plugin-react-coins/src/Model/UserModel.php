<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\User;

class UserModel
{
    /**
     * PDO database object
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Creates a wrapper to handle PDO connection for Coins Plugin
     *
     * @param \PDO $connection
     */
    public function __construct(array $config = [])
    {
        $this->connection = $config['database'];
    }

    public function addNewRegisteredUser($account, $nick)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (account,nick) VALUES (?,?);'
        );
        $statement->execute([ $account,$nick ]);
    }

    public function addNewNonRegisteredUser($nick)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (nick) VALUES (?);'
        );
        $statement->execute([ $nick ]);
    }

    public function getUserIdByAccount($account)
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
                        FROM users
                       WHERE account = ?
                       LIMIT 1;'
        );
        if ($statement->execute([ $account ])) {
            $result = $statement->fetch();
        }
        return $result;
    }

    public function getUserIdByNick($nick)
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
                        FROM users
                       WHERE nick = ?
                       LIMIT 1;'
        );
        if ($statement->execute([ $nick ])) {
            $result = $statement->fetch();
        }
        return $result;
    }

    public function getUserInfoById($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT *
                        FROM users
                       WHERE user_id = ?
                       LIMIT 1;'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetch();
        }
        return $result;
    }

    public function getUserAliases($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT alias
                        FROM aliases
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchAll();
        }
        return $result;
    }

    public function getTotalUsers(){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM users'
        );
        if ($statement->execute()) {
            $result = $statement->fetch();
        }
        return $result;
    }

}