<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Bet Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */
namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;

class BetModel extends UserModel
{

    protected $minBetInterval = 20;
    protected $maxBetAmount = 200;


    /**
     * validates the bet amount for the user, returns results array
     * todo : refactor
     * @param $betAmount
     * @param $user_id
     * @return array mixed
     */
    public function validateBet($betAmount, $user_id){
        $elapsedTime = $this->connection->checkBetInterval($user_id);
        $interval = $this->connection->getMinBetInterval()*60;

        if ($elapsedTime < $interval && $elapsedTime !== NULL){
            $remaining = Format::formatTime($interval - $elapsedTime);
            $valid['error'] = 1;
            $valid['errorMsg'] = "you must wait {$remaining} before betting again!";
            $valid['display'] = 'private';
            return $valid;
        }
        if (!is_numeric($betAmount)){
            $valid['error'] = 1;
            $valid['errorMsg'] = "you're confusing me";
            $valid['display'] = 'private';
            return $valid;
        }
        if ($betAmount > $this->connection->retrieveWorth($user_id)){
            $valid['error'] = 1;
            $valid['errorMsg'] = "you don't have that much to bet.";
            $valid['display'] = 'private';
            return $valid;
        }
        if ($betAmount <= 0 ) {
            $valid['error'] = 1;
            $valid['errorMsg'] = "you can't bet 0 or below";
            $valid['display'] = 'private';
            return $valid;
        }
        $valid['error'] = 0;
        return $valid;
    }

}
