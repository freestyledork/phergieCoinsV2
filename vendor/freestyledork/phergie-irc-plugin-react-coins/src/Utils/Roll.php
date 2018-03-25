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
    public static function OneToOneHundred(){
       return mt_rand (1,100);
    }

    public static function UniqueOneToOneHundred($rolls){
        $roll = self::OneToOneHundred();
        while(in_array($roll,$rolls)){
            $roll = self::OneToOneHundred();
        }
        return $roll;
    }
}