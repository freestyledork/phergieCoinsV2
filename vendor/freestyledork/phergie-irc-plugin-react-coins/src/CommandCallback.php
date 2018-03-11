<?php
/**
 * CommandCallback object to use for Authentication Event Callbacks
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;

class CommandCallback
{
    public $commandEvent;
    public $eventQueue;
    public $user;
    public $time;

    public function __construct(Event $commandEvent, Queue $queue, User $user){
        $this->time = time();
        $this->commandEvent = $commandEvent;
        $this->user = $user;
        $this->eventQueue = $queue;
    }

    public function getUser(){
        return $this->user;
    }

    public function getCallbackEvent()
    {
        $prefix = 'coins.callback.';
        $command = $this->commandEvent->getCustomCommand();
        return $prefix.$command;
    }
}