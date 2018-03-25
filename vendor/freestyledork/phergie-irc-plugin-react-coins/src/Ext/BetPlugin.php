<?php
/**
 * Plugin for users betting coins
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Ext
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\CommandCallback;


class BetPlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.bet'         => 'betCommand',
        'command.bet.hilo'    => 'hiloCommand',
    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.bet'        => 'betCallback',
        'coins.callback.bet.hilo'   => 'hiloCallback',
    ];

    protected $betModel;

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * min-collect-interval - time in minutes between collections
     *
     * TODO add cost to other plugins
     *
     * @param array $config
     * @throws \InvalidArgumentException if an unsupported database is passed.
     */
    public function __construct(array $config = [])
    {
        if(isset($config['database'])){
            $this->betModel = new Model\BetModel(['database' => $config['database']]);
        }
    }

    /**
     * Indicates that the plugin monitors PART and KICK events.
     *
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
    public function betCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $logger = $this->logger;
        $logger->info('Command received',['COMMAND' => $event->getCommand()]);
        $nick = $event->getNick();
        $queue->ircPrivmsg($source, 'Bet Command Started. (WIP)');
        $nick = strtolower($nick);

        $callback = new CommandCallback($event,$queue ,$nick);

        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }
    public function betCallback(CommandCallback $callback)
    {
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source, 'bet Command callback success. (WIP)');
        $logger =  $this->logger;
        $logger->info('Event received',['CommandCallback' => 'betCallback']);
    }

    /**
     * Handles coin bet hilo command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function hiloCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $logger = $this->logger;
        $logger->info('Command received',['COMMAND' => $event->getCommand()]);
        $nick = $event->getNick();
        $queue->ircPrivmsg($source, 'hilo Command Started. (WIP)');
        $nick = strtolower($nick);

        $callback = new CommandCallback($event,$queue ,$nick);

        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }
    public function hiloCallback(CommandCallback $callback)
    {
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source, 'hilo Command callback success. (WIP)');
        $logger =  $this->logger;
        $logger->info('Event received',['CommandCallback' => 'hiloCallback']);
    }
}
