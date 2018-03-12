<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;


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
        'command.coins'         => 'coinsCommand',
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

    /**
     * Database Wrapper for basic coins info
     *
     * @var Model\CollectionModel
     */
    protected $collectionModel;

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
            $this->collectionModel = new Model\CollectionModel(['database' => $config['database']]);
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

        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$event,$queue,$callback]);
    }

    /**
     * Handles the testCommand CommandCallback response
     *
     * @param CommandCallback $callback
     */
    public function testCallback(CommandCallback $callback)
    {
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source,'Callback success');
        $callback->eventQueue->ircNotice($source,'Callback success');
    }

    /**
     * Handles coin command calls
     * @see coinsCallback()
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function coinsCommand(Event $event, Queue $queue)
    {
        $this->getLogger()->info(
            'Command received',
            ['COMMAND' => $event->getCustomCommand()]
        );

        $nick = $event->getNick();
        $nick = strtolower($nick);
        $callback = new CommandCallback($event,$queue ,$nick);
        $queue->ircNotice($nick, 'Coin Command Started. (WIP)');
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$event,$queue,$callback]);

    }

    /**
     * Finishes coin Callback
     *
     * @param Event $event
     * @param Queue $queue
     */
    public function coinsCallback(CommandCallback $callback)
    {
        $test = $this->collectionModel->dbTest();
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source,"Callback success {$test}");
        $callback->eventQueue->ircNotice($source,"Callback success {$test}");
        $this->getLogger()->info(
            'Event received',
            ['CommandCallback' => $callback->commandEvent->getCustomCommand()]
        );
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
