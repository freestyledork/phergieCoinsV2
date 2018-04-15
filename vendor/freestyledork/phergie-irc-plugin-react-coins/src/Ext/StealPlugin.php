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
        'command.steal.info'    => 'stealInfoCommand',

    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.steal'        => 'stealCallback',
    ];

    protected $database;

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
            $this->database = new Model\StealModel(['database' => $config['database']]);
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
     * Usage: <nick> <amount>
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function stealCommand(CommandEvent $event, Queue $queue)
    {
        Log::Command($this->getLogger(),$event);
        $params = $event->getCustomParams();

        // TODO: pre validation

        // check user exists
        $user_id = $this->database->getUserIdByNick($event->getNick());
        $target_id = $this->database->getUserIdByNick($params[0]);
        if (!$user_id){
            $msg = "Sorry {$event->getNick()}, you must use coins command before attempting to steal.";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // make sure param is a number if passed TODO: different validation message for bad target
        if (count($params)!==2 || !is_numeric($params[1]) || !$target_id){
            $msg = 'Please use the following format: lotto.buy <amount>';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // make sure the param is a positive
        if (count($params)>=1 && $params[0] <=0){
            $msg = 'Please use the following format: lotto.buy <amount>';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // check steal amounts are within defined limits
        $tagetAvailWorth = $this->database->getUserAvailableWorth($target_id);
        $sourceAvailWorth = $this->database->getUserAvailableWorth($user_id);


        // debugging
        $queue->ircPrivmsg($event->getSource(), 'Steal Command Started. (WIP)');

        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }


    public function stealCallback(CommandCallback $callback)
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

        $callback->eventQueue->ircPrivmsg($source, 'Steal Command callback success. (WIP)');
    }

    public function stealInfoCommand(CommandEvent $event, Queue $queue)
    {
        $params = $event->getCustomParams();
        Log::Command($this->getLogger(),$event);

        // find target
        if (count($params) == 0){
            $nick = $event->getNick();
        }else {
            $nick = $params[0];
        }

        // make sure user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $msg = "I don't know anyone by {$nick}!";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }

        // get params
        $lastSteal = $this->database->getUserLastStealTime($user_id);
        $tSteals = $this->database->getUserTotalStealAttempts($user_id);
        $stealSuccessRate = floor(($this->database->getUserTotalStealSuccess($user_id)/$tSteals)*100);
        $mostStolen = $this->database->getUserHighestSteal($user_id);
        $mostPaid = $this->database->getUserWorstLoss($user_id);

        // send message
        $msg  = "[Total Attempts] {$tSteals} [Last] {$lastSteal} [Most Stolen] {$mostStolen}";
        $msg .= "[Most Paid] {$mostPaid} [Success Rate] {$stealSuccessRate}%";
        $queue->ircPrivmsg($event->getSource(), $msg);
    }

}