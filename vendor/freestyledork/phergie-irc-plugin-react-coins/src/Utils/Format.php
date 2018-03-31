<?php
/**
 * 
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;

class Format
{

    /**
     * @var string
     */
    protected static $colorTag = "\x03";

    /**
     * @var array
     */
    protected static $colorCodes = array(
        'white' => '00',
        'black' => '01',
        'blue' => '02',
        'green' => '03',
        'red' => '04',
        'brown' => '05',
        'purple' => '06',
        'orange' => '07',
        'yellow' => '08',
        'lightGreen' => '10',
        'teal' => '10',
        'cyan' => '11',
        'lightBlue' => '12',
        'pink' => '13',
        'grey' => '14',
        'lightGrey' => '15'
    );

    /**
     * @var array
     */
    protected static $styleTag = array(
        'bold' => "\x02",
        'underline' => "\x1F",
        //'italic'        => "\x09",    // Poor support
        //'strikethrough' => "\x13",    // Poor support
        'reverse' => "\x16",
    );
    
    
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
     * Generate a string with the foreground/background color specified
     *
     * @param string $text
     * @param string $foregroundColor
     * @param string $backgroundColor
     * @return string
     */
    public static function color($text, $foregroundColor, $backgroundColor = null)
    {
        // If the foreground doesn't exist or is empty, quit now and return the original string
        return (!$foregroundColor || !isset(self::$colorCodes[$foregroundColor])) ? $text : sprintf(
            "%s%s%s%s%s",
            self::$colorTag,
            self::$colorCodes[$foregroundColor],
            ($backgroundColor !== null && isset(self::$colorCodes[$backgroundColor])) ? sprintf(",%s", self::$colorCodes[$backgroundColor]) : "",
            $text,
            self::$colorTag
        );
    }

    /**
     * Generate a string with the text style specified
     *
     * @param string $text
     * @param string $style
     * @return string
     */
    public static function style($text, $style)
    {
        return sprintf(
            "%s%s%s",
            (isset(self::$styleTag[$style])) ? self::$styleTag[$style] : "",
            $text,
            (isset(self::$styleTag[$style])) ? self::$styleTag[$style] : ""
        );
    }

    /**
     * Generate a rainbow coloured string
     *
     * @param $text
     * @return string
     */
    public static function rainbow($text)
    {
        $rainbow = array('red', 'yellow', 'pink', 'green', 'purple', 'orange', 'blue');
        $output = "";
        $rainbowKey = 0;
        $charCount = strlen($text);

        for ($a = 0; $a < $charCount; $a++) {

            if ($rainbowKey > count($rainbow) - 1) {
                $rainbowKey = 0;
            }

            $char = substr($text, $a, 1);

            // Ignore spaces
            if ($char === " ") {
                $output .= $char;
                continue;
            }

            // Style the current character
            $output .= self::color($char, $rainbow[$rainbowKey]);

            $rainbowKey++;
        }

        return $output;
    }
}