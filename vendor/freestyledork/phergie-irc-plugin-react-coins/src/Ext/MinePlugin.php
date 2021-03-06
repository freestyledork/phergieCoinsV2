<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 3:04 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Utils\Log;
use React\EventLoop\LoopInterface;
use Phergie\Irc\ConnectionInterface;
use React\EventLoop\TimerInterface;

class MinePlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.mine'          => 'mineCommand',
//        'command.mine.level'    => 'mineLevelCommand',

    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.mine'        => 'mineCallback',
    ];

    /**
     * Array of connect events to listen
     *
     * @var array
     */
    protected $connectEvents = [
        'connect.after.each' => 'addConnection',
    ];
    /**
     * Database Wrapper for mine related info
     *
     * @var Model\CollectionModel
     */
    protected $database;

    /**
     * Connection used for queue factory
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * Loop implementation
     *
     * @var LoopInterface
     */
    protected $loop;


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
            $this->database = new Model\MineModel(['database' => $config['database']]);
        }
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {

        return array_merge(
            $this->commandEvents,
            $this->callbackEvents,
            $this->connectEvents
        );
    }

    /**
     * Handles coin mine command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function mineCommand(CommandEvent $event, Queue $queue)
    {
        Log::Command($this->getLogger(),$event);

        //TODO pre-validation
        $user_id = $this->database->getUserIdByNick($event->getNick());
        if (!$user_id){
            $msg = "Sorry {$event->getNick()}, you must use coins command before attempting to mine.";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }

        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }

    public function mineCallback(CommandCallback $callback)
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
    }

    /**
     * Sets the event loop to use.
     *
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function addConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->loop->addPeriodicTimer(30, array($this, 'myTimerCallback'));
    }

    public function myTimerCallback(TimerInterface $timer)
    {
        $factory = $this->getEventQueueFactory();
        $queue = $factory->getEventQueue($this->connection);
        $queue->ircPrivmsg("#FSDChannel","tick");
    }

    public function attemptMine($lvl)
    {
        // pick a type
        $tRoll = mt_rand(0,1);
        if ($tRoll == 0){
            $this->attemptMineGem($lvl);
        } else {
            $this->attempMineMetal($lvl);
        }

    }

    public function attemptMineGem($lvl)
    {
        // check if successful attempt
        $continue = Roll::Success(.5);
        if (!$continue) return; // failed

        // pick gem
        $gems = $this->database->getAllGems();

    }

    public function attempMineMetal($lvl)
    {

    }

}