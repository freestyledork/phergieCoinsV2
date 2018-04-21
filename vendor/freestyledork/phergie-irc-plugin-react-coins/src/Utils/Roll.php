<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 11:45 AM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;

use Freestyledork\Phergie\Plugin\Coins\Config\Settings;

class Roll
{

    /**
     * rolls a number between 1-100
     *
     * @return int
     */
    public static function OneToOneHundred(){
        return mt_rand (1,100);
    }

    /**
     * returns a roll number not in submitted rolls
     *
     * @param array $rolls
     * @return int
     */
    public static function UniqueOneToOneHundred($rolls){
        $roll = self::OneToOneHundred();
        while(in_array($roll,$rolls)){
            $roll = self::OneToOneHundred();
        }
        return $roll;
    }

    /**
     * rolls a number between 0-100
     *
     * @return int
     */
    public static function ZeroToOneHundred(){
        return mt_rand (0,100);
    }

    /**
     * returns a roll number not in submitted rolls
     *
     * @param array $rolls
     * @return int
     */
    public static function UniqueZeroToOneHundred($rolls){
        $roll = self::ZeroToOneHundred();
        while(in_array($roll,$rolls)){
            $roll = self::ZeroToOneHundred();
        }
        return $roll;
    }

    /**
     * rolls a new collection total
     *
     * @return int
     */
    public static function CollectionAmount(){
        return Settings::COLLECT_BASE + mt_rand (1,Settings::COLLECT_MAX_BONUS);
    }

    /**
     * string format 0-9|0-9|0-9
     *
     * @return string
     */
    public static function LottoTicket(){
        $n1 = mt_rand(0,9);
        $n2 = mt_rand(0,9);
        $n3 = mt_rand(0,9);

        return (string)($n1 . $n2 . $n3);
    }

    /**
     * 0-1 based success roll
     *
     * @param $goal float
     * @return bool
     */
    public static function Success($goal){
        //convert to float value
        $roll = mt_rand(0,100)/100;
        return $roll <= $goal;
    }
}