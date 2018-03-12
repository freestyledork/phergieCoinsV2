<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Db
 */
namespace Freestyledork\Phergie\Plugin\Coins\Model;

class BetModel extends UserModel
{
    /**
     * PDO database object
     *
     * @var \PDO
     */
    protected $connection;

    protected $minBetInterval = 20;
    protected $maxBetAmount = 200;

    /**
     * Creates a wrapper to handle PDO connection for Coins Plugin
     *
     * @param \PDO $connection
     */
    public function __construct(array $config = [])
    {
        parent::__construct();
        $this->connection = $config['database'];
    }

    public function betDbTest(){
        return 'betDbTestSuccess!!';
    }

}
