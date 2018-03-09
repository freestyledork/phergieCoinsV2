<?php
/**
 * @package Freestyledork\Phergie\Plugin\Coins\Db
 */
namespace Freestyledork\Phergie\Plugin\Coins\Db;

/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Db
 */
class CoinsBetDbWrapper extends CoinsDbWrapper
{
    /**
     * PDO database object
     *
     * @var \PDO
     */
    protected $connection;

    protected $minCollectInterval = 30;
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

}
