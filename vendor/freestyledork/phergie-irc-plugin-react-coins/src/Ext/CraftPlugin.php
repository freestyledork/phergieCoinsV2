<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 3:05 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\CommandCallback;

class CraftPlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.craft'         => 'craftCommand',
    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.craft'        => 'craftCallback',
    ];

    protected $craftModel;

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
            $this->craftModel = new Model\CraftModel(['database' => $config['database']]);
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
    public function craftCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $logger = $this->logger;
        $logger->info('Command received',['COMMAND' => $event->getCommand()]);
        $nick = $event->getNick();
        $queue->ircPrivmsg($source, 'craft Command Started. (WIP)');
        $nick = strtolower($nick);

        $callback = new CommandCallback($event,$queue ,$nick);

        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }
    public function craftCallback(CommandCallback $callback)
    {
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source, 'craft Command callback success. (WIP)');
        $logger =  $this->logger;
        $logger->info('Event received',['CommandCallback' => 'betCallback']);
    }

}