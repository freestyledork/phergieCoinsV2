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
use Phergie\Irc\Event\UserEventInterface;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
//use Freestyledork\Phergie\Plugin\Coins\Helper\Helper;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
use Phergie\Irc\Event\ServerEventInterface as ServerEvent;
use Freestyledork\Phergie\Plugin\Authentication as Auth;


class BetPlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.bet'         => 'betCommand',
    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.bet'         => 'coinsCallback'
    ];


    protected $commandQueue = [];

    protected $coinsDbWrapper;

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
            $this->coinsDbWrapper = new Model\CollectionModel(['database' => $config['database']]);
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
     * Handles coin command calls
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function betCommand(Event $event, Queue $queue)
    {
        $logger = $this->logger;
        $logger->info('Command received',['COMMAND' => $event->getCommand()]);
        $nick = $event->getNick();
        $queue->ircNotice($nick, 'Bet Command Started. (WIP)');
    }
    public function coinsCallback()
    {
        $logger =  $this->logger;
        $logger->info('Event received',['CommandCallback' => 'betCallback']);
    }
}
