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

    protected $minBetInterval = 20;
    protected $maxBetAmount = 200;


}
