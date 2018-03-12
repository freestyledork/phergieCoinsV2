<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */
namespace Freestyledork\Phergie\Plugin\Coins\Model;

class CollectionModel extends UserModel
{
    protected $minCollectInterval = 120;
    protected $baseCollectAmount = 200;

}
