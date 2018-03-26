<?php
/**
 * User object to hold user info for commands
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins\Helper;


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
     * @param $user_info array
     */
    public function setUserInfo($user_info){
        if (isset($user_info['worth'])){
            $this->worth = $user_info['worth'];
        }
        if (isset($user_info['creation'])){
            $this->creation = $user_info['creation'];
        }
        if (isset($user_info['type'])){
            $this->type = $user_info['type'];
        }
        if (isset($user_info['status'])){
            $this->status = $user_info['status'];
        }
    }

    /**
     * Determines whether the user is authenticated
     *
     * @return Response
     */
    public function isValidIrc(){
        $response = new Response(false);
        if ($this->accLevel === null || $this->accountName === null){
            $msg = 'General Error';
            $response->setError($msg);
            return $response;
        }
        if ($this->accLevel == 0 && $this->accountName === 'Not Registered'){
            $msg = "No Account for: {$this->nick}";
            $response->value = true;
            $response->setError($msg);
            return $response;
        }
        if ($this->accLevel == 0){
            $msg = "{$this->nick} is registered to {$this->accountName} but is not in use.";
            $response->setError($msg);
            return $response;
        }
        if ($this->accLevel == 1){
            $msg = "{$this->nick} is registered to {$this->accountName} but not logged in.";
            $response->setError($msg);
            return $response;
        }
        if ($this->accLevel == 2){
            $msg = "{$this->nick} is registered to {$this->accountName} and recognized.";
            $response->value = true;
            return $response;
        }
        if ($this->accLevel == 3){
            $msg = "{$this->nick} is registered to {$this->accountName} and logged in.";
            $response->value = true;
            return $response;
        }
        // catch anything that makes it through
        $msg = 'General Error';
        $response->setError($msg);
        return $response;
    }

    public function canRegister()
    {
        return $this->accLevel == 2 ||
            $this->accLevel == 3 ||
            ($this->accLevel == 0 && $this->accountName === 'Not Registered');
    }
}