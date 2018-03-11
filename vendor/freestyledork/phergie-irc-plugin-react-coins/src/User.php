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
    /**
     * @var $nick string
     */
    public $nick;
    /**
     * @var $accountName string
     */
    public $accountName;
    /**
     * ACC also returns the unique entity ID of the given account.
     * The answer is in the form <nick> [-> account] ACC <digit> <EID>,
     * where <digit> is one of the following:
     *   0 - account or user does not exist
     *   1 - account exists but user is not logged in
     *   2 - user is not logged in but recognized (see ACCESS)
     *   3 - user is logged in
     *
     * @var $accLevel integer
     */
    public $accLevel;
    /**
     *
     * @var $authValid bool
     */
    public $authValid;
    /**
     * @var $host string
     */
    public $host;
    /**
     * @var $worth integer
     */
    public $worth;
    /**
     * @var $id integer
     */
    public $id;
    /**
     * @var $creation object
     */
    public $creation;
    /**
     * @var $status string
     */
    public $status;
    /**
     * @var $type string
     */
    public $type;
    /**
     * @var $aliases array
     */
    public $aliases = [];

    /**
     * User constructor.
     * @param $nick string
     */
    public function __construct($nick)
    {
        $this->nick = strtolower($nick);
    }

    /**
     * Determines whether the user is authenticated
     *
     * @return bool
     */
    public function authUser(){
        if ($this->accLevel === null || $this->accountName === null){
            $this->authValid = false;
            return $this->authValid;
        }
        if ($this->accLevel == 0 && $this->accountName === "Not Registered"){
            $msg = "No Account for: {$this->nick}";
            $this->authValid = true;
            return $this->authValid;
        }
        if ($this->accLevel == 0){
            $msg = "{$this->nick} is registered to {$this->accountName} but is not in use.";
            $this->authValid = false;
            return $this->authValid;
        }
        if ($this->accLevel == 1){
            $msg = "{$this->nick} is registered to {$this->accountName} but not logged in.";
            $this->authValid = false;
            return $this->authValid;
        }
        if ($this->accLevel == 2){
            $msg = "{$this->nick} is registered to {$this->accountName} and recognized.";
            $this->authValid = true;
            return $this->authValid;
        }
        if ($this->accLevel == 3){
            $msg = "{$this->nick} is registered to {$this->accountName} and logged in.";
            $this->authValid = true;
            return $this->authValid;
        }
        // catch anything that makes it through
        $this->authValid = false;
        return $this->authValid;
    }
}