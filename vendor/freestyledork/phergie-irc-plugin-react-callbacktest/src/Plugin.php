<?php
/**
 * Phergie plugin for testing callback events/methods
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-currency for the canonical source repository
 * @copyright Copyright (c) 2017 Mike (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\Testing
 */


namespace Freestyledork\Phergie\Plugin\CallbackTest;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
use Freestyledork\Phergie\Plugin\Coins\Ext\nickAuth as Auth;


/**
 * Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\Authentication
 */
class Plugin extends AbstractPlugin
{

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {

    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'command.callback' => 'callbackCommand',
        ];
    }

    /**
     *
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function callbackCommand(Event $event, Queue $queue, Auth $valid = null)
    {
        if ($valid == null){
            $queue->ircPrivmsg('#FSDChannel', "callback started." );
            $this->getEventEmitter()->emit('command.auth', [$event, $queue]);
        }elseif($valid->valid == true){
            $queue->ircPrivmsg('#FSDChannel', "callback valid. {$valid->nick}" );
        }
    }
}