<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/17/18
 * Time: 7:22 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;


class Time
{
    /**
     * returns the elapsed time since the given time
     *
     * @param $time
     * @return int
     */
    public static function timeElapsedInSeconds($time){
        $date_time = date_create($time);
        return time() - $date_time->format('U');
    }

    /**
     * returns the elapsed time since the given time
     *
     * @param $time
     * @return int
     */
    public static function timeElapsedInDays($time){
        $date_time = date_create($time);
        $diff = time() - $date_time->format('U');
        return floor($diff/ (60*60*24));
    }

}