<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;

class Format
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



}