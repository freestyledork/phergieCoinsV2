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
    public $nick;
    public $accountName;
    public $accLevel;
    public $auth;
    public $host;
    public $worth;
    public $id;
    public $creation;
    public $status;
    public $type;
    public $aliases = [];

    public function __construct($nick)
    {
        $this->nick = strtolower($nick);
    }

    public function authUser(){
        if ($this->accLevel === null || $this->accountName === null){
            $this->auth = false;
            return $this->auth;
        }
        if ($this->accLevel == 0 && $this->accountName === "Not Registered"){
            $msg = "No Account for: {$this->nick}";
            $this->auth = true;
            return $this->auth;
        }
        if ($this->accLevel == 0){
            $msg = "{$this->nick} is registered to {$this->accountName} but is not in use.";
            $this->auth = false;
            return $this->auth;
        }
        if ($this->accLevel == 1){
            $msg = "{$this->nick} is registered to {$this->accountName} but not logged in.";
            $this->auth = false;
            return $this->auth;
        }
        if ($this->accLevel == 2){
            $msg = "{$this->nick} is registered to {$this->accountName} and recognized.";
            $this->auth = true;
            return $this->auth;
        }
        if ($this->accLevel == 3){
            $msg = "{$this->nick} is registered to {$this->accountName} and logged in.";
            $this->auth = true;
            return $this->auth;
        }
    }
}