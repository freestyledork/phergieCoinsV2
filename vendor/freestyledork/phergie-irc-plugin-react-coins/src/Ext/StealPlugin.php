<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 3:04 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Utils\Log;

class StealPlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.steal'         => 'stealCommand',
    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.steal'        => 'stealCallback',
    ];

    protected $stealModel;

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * @param array $config
     * @throws \InvalidArgumentException if an unsupported database is passed.
     */
    public function __construct(array $config = [])
    {
        if(isset($config['database'])){
            $this->stealModel = new Model\StealModel(['database' => $config['database']]);
        }
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array_merge(
            $this->commandEvents,
            $this->callbackEvents
        );
    }

    /**
     * Handles coin bet command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function stealCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        Log::Command($this->getLogger(),$event);
        $nick = $event->getNick();
        $queue->ircPrivmsg($source, 'Steal Command Started. (WIP)');
        $nick = strtolower($nick);

        $callback = new CommandCallback($event,$queue ,$nick);

        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }
    public function stealCallback(CommandCallback $callback)
    {
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source, 'Steal Command callback success. (WIP)');
        Log::Event($this->getLogger(),$callback->getAuthCallbackEventName());
    }

}