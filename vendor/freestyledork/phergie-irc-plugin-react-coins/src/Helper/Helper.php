<?php

/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Custom\Phergie\Plugin\Coins\Helper;

class Helper
{
    /**
     * @param int $seconds
     * @return string formattedTime
     */
    public static function formatTime($seconds) {
        $t = round($seconds);
        return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
    }

    /**
     * adds commas to coins value, can be expanded later
     *
     * @param $coins
     * @return string
     */
    public static function formatCoinAmount($coins){
        return number_format($coins);
    }

    /**
     * validates the bet amount for the user, returns results array
     * todo : refactor
     * @param $betAmount
     * @param $user_id
     * @return array mixed
     */
    public function validateBet($betAmount, $user_id){
        $elapsedTime = $this->database->checkBetInterval($user_id);
        $interval = $this->database->getMinBetInterval()*60;

        if ($elapsedTime < $interval && $elapsedTime !== NULL){
            $remaining = self::formatTime($interval - $elapsedTime);
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
        if ($betAmount > $this->database->retrieveWorth($user_id)){
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