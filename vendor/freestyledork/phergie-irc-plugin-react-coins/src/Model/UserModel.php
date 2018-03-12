<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/11/18
 * Time: 10:02 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;


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

    public function dbTest(){
        return 'dbTestSuccess!!';
    }
}