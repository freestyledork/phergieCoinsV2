<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 3:04 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Freestyledork\Phergie\Plugin\Coins\Config\Settings;
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
     * Handles coin steal command calls
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
            $msg = 'Please use the following format: steal <target nick> <amount>';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // make sure the param is a positive
        if ( $params[1] <=0){
            $msg = 'Please use a positive number.';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // check steal amounts are within defined limits
        $stealAttemptAmount = $params[1];
        $targetAvailWorth = $this->database->getUserAvailableWorth($target_id);
        $sourceAvailWorth = $this->database->getUserAvailableWorth($user_id);
        // user has enough to pay bail
        if ($stealAttemptAmount > $sourceAvailWorth*Settings::STEAL_LIMIT_PERCENT){
            $msg = 'You can only attempt to steal upto 50% of your ...';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // target has enough to be taken
        if ($targetAvailWorth < $stealAttemptAmount){
            $msg = 'You target does not have that much to steal.';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }

        // pass command to callback to be verified and executed
        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }


    public function stealCallback(CommandCallback $callback)
    {
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $source = $event->getSource();
        $params = $event->getCustomParams();
        $stealAttemptAmount = $params[1];
        $sourceNick = $event->getNick();
        $targetNick = $params[0];

        // is the user in a valid state
        $response = $user->isValidIrc();
        if (!$response->value){
            foreach ($response->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }
        $user_id = $this->database->getUserIdByNick($sourceNick);
        $target_id = $this->database->getUserIdByNick($targetNick);

        $currentSuccessRate = $this->database->getUserStealSuccessRate($user_id);
        $success = Roll::Success($currentSuccessRate)? 1 : 0;
        $this->database->addNewStealAttempt($user_id,$stealAttemptAmount, $success,$target_id);
        if ($success){
            $this->database->addCoinsToUser($user_id,$stealAttemptAmount);
            $this->database->removeCoinsFromUser($target_id,$stealAttemptAmount);
            $msg = "You successfully stole {$stealAttemptAmount} from {$targetNick}!";
            $queue->ircNotice($sourceNick, $msg);
            return;
        }
        $bailAmount = $stealAttemptAmount * Settings::STEAL_BAIL_PERCENT;
        $this->database->removeCoinsFromUser($user_id,$bailAmount);
        $msg = "Whoops! You got caught.. you paid {$bailAmount} to post bail!";
        $queue->ircNotice($sourceNick, $msg);

        //$queue->ircPrivmsg($source, 'Steal Command callback success. (WIP)');
    }

    /**
     * gets steal info for user and outputs to irc line
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
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
        // TODO: remove in production
        $currentSuccessRate = floor($this->database->getUserStealSuccessRate($user_id)*100);

        // send message
        $msg  = "[Total Attempts] {$tSteals} [Last] {$lastSteal} [Most Stolen] {$mostStolen}";
        $msg .= "[Most Paid] {$mostPaid} [Success Rate] {$stealSuccessRate}% [Current Rate] {$currentSuccessRate} %";
        $queue->ircPrivmsg($event->getSource(), $msg);
    }

}