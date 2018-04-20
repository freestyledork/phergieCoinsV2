<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/30/18
 * Time: 3:51 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Config;


class Settings
{

    const COLLECT_INTERVAL              = 60*60*2;     // 2 hours in seconds
    const COLLECT_BASE                  = 100;         // fixed base collect
    const COLLECT_MAX_BONUS             = 100;         // 0 - value bonus
    const BET_INTERVAL                  = 60*20;       // 20 minutes in seconds
    const BET_MAX_AMOUNT                = 50;          // 50 coins max
    const BET_MAX_OVERFLOW_TIME         = 60*60*12;    // (bet value / max bet amount)* bet interval <= max overflow
    const LOTTO_TICKET_COST             = 250;         //
    const LOTTO_DAILY_TICKET_LIMIT      = 3;           // can only purchase this many in 24 hours
    const LOTTO_BASE_PRIZE              = 2000;        // start the lotto with this amount
    const LOTTO_DAILY_BONUS             = 500;         // everyday lotto isnt won add this
    const STEAL_LIMIT_PERCENT           = .5;          // value * user worth
    const STEAL_BAIL_PERCENT            = 2;           // value * steal attempt value
    const STEAL_BASE_SUCCESS_PERCENT    = .4;          // success rate 0-1
    const STEAL_RESET_INTERVAL          = 60*60*12;    // reset base success rate after this time passed
    const BANK_TRANSFER_INTERVAL        = 60*60*2;     // 2 hours in seconds
    const BANK_TRANSFER_FEE             = .05;         // value * transfer amount
    const GIVE_INTERVAL                 = 60*20;
    const GIVE_MAX_PERCENT              = .1;
    
}