<?php
/**
 * CommandCallback object to use for Authentication Event Callbacks
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins\Helper;

use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;

class CommandCallback
{
    /**
     * Holds the source event
     *
     * @var Event $commandEvent
     */
    public $commandEvent;

    /**
     * @var Queue
     */
    public $eventQueue;

    /**
     * Info about the user to be used later
     *
     * @var User
     */
    public $user;

    /**
     * Callback creation time
     *
     * @var int
     */
    public $time;

    public function __construct(Event $commandEvent, Queue $queue, $nick, $user_id = null){
        $this->user = new User($nick);
        $this->time = time();
        $this->commandEvent = $commandEvent;
        $this->eventQueue = $queue;
        if ($user_id !== null) $this->user->id = $user_id;
    }

    /**
     * @return User
     */
    public function getUser(){
        return $this->user;
    }

    /**
     * Gets the event to emit on successful callback
     *
     * @return string
     */
    public function getCallbackEvent()
    {
        $prefix = 'coins.callback.';
        $command = $this->commandEvent->getCustomCommand();
        return $prefix.$command;
    }

    /**
     * Gets the authentication event name
     *
     * @return string
     */
    public function getAuthCallbackEventName(){
        return 'coins.callback.auth';
    }
}