<?php
/**
 * User object to hold user info for commands
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

class User
{
    protected $nick;
    protected $accountName;
    protected $accLevel;
    protected $host;
    protected $worth;
    protected $id;
    protected $creation;
    protected $status;
    protected $type;
    protected $aliases = [];

    public function __construct($nick)
    {
        $this->nick = $nick;
    }
}