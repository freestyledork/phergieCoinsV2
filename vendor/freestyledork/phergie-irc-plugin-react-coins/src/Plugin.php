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
class Plugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.coins'         => 'coinCommand',
        'command.coins.help'    => 'coinsHelpCommand',
        'command.test'          => 'testCommand',
        'command.worth'         => 'worthCommand'
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

    public function testCommand(Event $event, Queue $queue)
    {
        call_user_func(['Freestyledork\Phergie\Plugin\Coins\BetPlugin','Testing']);
    }

    public function Testing()
    {
        $logger = $this->getLogger();
        $logger->info('Command received',['COMMAND' => 'callback']);
    }
    /**
     * Handles coin command calls
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function coinCommand(Event $event, Queue $queue)
    {
        $logger = $this->getLogger();
        $logger->info('Command received',['COMMAND' => $event->getCommand()]);
        $nick = $event->getNick();
        $queue->ircNotice($nick, 'Coin Command Started. (WIP)');
    }

    /**
     * Handles coin help command calls
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function coinsHelpCommand(Event $event, Queue $queue)
    {
        $nick = $event->getNick();
        $command = substr($event->getCustomCommand(), 0, -5);
        $queue->ircNotice($nick, 'Usage: '.$command);
        $queue->ircNotice($nick, 'Add some coins to your name.');
    }

    /**
     * Handles worth calls
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function worthCommand(Event $event, Queue $queue)
    {
        $nick = $event->getNick();
        $queue->ircNotice($nick, 'Worth Command Started. (WIP)');
    }

}
