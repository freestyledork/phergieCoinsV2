<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Craft Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;

class CraftModel extends UserModel
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
}