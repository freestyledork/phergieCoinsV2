<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 11:45 AM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;


class Roll
{

    /**
     * @return int
     */
    public static function OneToOneHundred(){
        return mt_rand (1,100);
    }

    /**
     * @param $rolls
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
     * @return int
     */
    public static function CollectionAmount(){
        return Settings::COLLECT_BASE + mt_rand (1,Settings::COLLECT_MAX_BONUS);
    }

    /**
     * @return string
     */
    public static function LottoTicket(){
        $n1 = mt_rand(0,9);
        $n2 = mt_rand(0,9);
        $n3 = mt_rand(0,9);

        return (string)($n1 . $n2 . $n3);
    }

    /**
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