<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Event\UserEventInterface;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
//use Freestyledork\Phergie\Plugin\Coins\Helper\Helper;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
use Phergie\Irc\Event\ServerEventInterface as ServerEvent;
use Freestyledork\Phergie\Plugin\Authentication as Auth;

/**
 * Plugin for users collecting coins
 *
 * @category Phergie
 * @package Custom\Phergie\Plugin\Coins
 */
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
            $this->coinsDbWrapper = new Db\CoinsDbWrapper(['database' => $config['database']]);
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
            $this->commandEvents
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
        $logger = $this->getLogger();
        $logger->info('Command received',['COMMAND' => $event->getCommand()]);
        $nick = $event->getNick();
        $queue->ircNotice($nick, 'Bet Command Started. (WIP)');
    }
    public function Testing()
    {
        $logger = $this->getLogger();
        $logger->info('Command received',['COMMAND' => 'betCallback']);
    }
}
