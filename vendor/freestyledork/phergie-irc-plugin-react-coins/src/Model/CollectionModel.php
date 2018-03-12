<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Db
 */
namespace Freestyledork\Phergie\Plugin\Coins\Model;

class CollectionModel extends UserModel
{
    /**
     * PDO database object
     *
     * @var \PDO
     */
    protected $connection;

    protected $minCollectInterval = 120;
    protected $collectAmount = 200;

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
