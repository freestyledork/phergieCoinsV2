<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/16/18
 * Time: 4:17 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Helper;

use Freestyledork\Phergie\Plugin\Coins\Enum;

class Error
{
    /**
     * @var
     */
    public $type;
    public $msg;

    public function __construct($type, $msg)
    {
        $this->type = $type;
        $this->msg = $msg;
    }

}