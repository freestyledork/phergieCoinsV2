<?php
/**
 * Callback object to use for Authentication Event Callbacks
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;


class Callback
{
    public $user;
    public $event;
    public $time;
    public $valid;

    public function __construct(){

        $this->time = time();

    }
}