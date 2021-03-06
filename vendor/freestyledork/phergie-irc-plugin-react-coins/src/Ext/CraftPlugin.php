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
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Utils\Log;

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
        Log::Command($this->getLogger(),$event);

        $queue->ircPrivmsg($event->getSource(), 'craft Command Started. (WIP)');

        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }
    public function craftCallback(CommandCallback $callback)
    {
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $source = $event->getSource();

        // is the user in a valid state
        $response = $user->isValidIrc();
        if (!$response->value){
            foreach ($response->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }

        $callback->eventQueue->ircPrivmsg($source, 'craft Command callback success. (WIP)');
        Log::Event($this->getLogger(),$callback->getAuthCallbackEventName());
    }

}