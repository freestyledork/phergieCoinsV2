<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Evenement\EventEmitter;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
//use Freestyledork\Phergie\Plugin\Coins\Helper\Helper;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
use Phergie\Irc\Event\ServerEventInterface as ServerEvent;
use Freestyledork\Phergie\Plugin\Authentication as Auth;
use Freestyledork\Phergie\Plugin\Coins\CommandCallback as Callback;

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

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.coins' => 'coinsCallback',
        'coins.callback.test'  => 'testCallback'
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
     * Used for testing commands with callback.
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function testCommand(Event $event, Queue $queue)
    {
        // allow passable nick for testing
        $nick = $event->getCustomParams()[0];
        $nick = strtolower($nick);

        $callback = new CommandCallback($event,$queue ,$nick);

        $this->getEventEmitter()->emit('coins.callback.auth',[$event,$queue,$callback]);
    }

    /**
     * Handles the testCommand CommandCallback response
     *
     * @param CommandCallback $callback
     */
    public function testCallback(CommandCallback $callback)
    {
        $callback->eventQueue->ircNotice("#FSDChannel",'Callback success');
        echo "\r\n";
        print_r($callback->user);
        echo "\r\n";
    }

    /**
     * @param Event $event
     * @param Queue $queue
     */
    public function coinsCallback(CommandCallback $callback)
    {
        $this->getLogger()->info(
            'Event received',
            ['CommandCallback' => $callback->commandEvent->getCustomCommand()]
        );
    }

    /**
     * Handles coin command calls
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function coinCommand(Event $event, Queue $queue)
    {
        $this->getLogger()->info(
            'Command received',
            ['COMMAND' => $event->getCustomCommand()]
        );

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
