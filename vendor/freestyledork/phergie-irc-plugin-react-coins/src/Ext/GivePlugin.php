<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 3:04 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Freestyledork\Phergie\Plugin\Coins\Config\Settings;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Log;
use Freestyledork\Phergie\Plugin\Coins\Utils\Format;

class GivePlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.give'         => 'giveCommand',
        'command.give.info'    => 'giveInfoCommand',

    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.give'        => 'giveCallback',
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
            $this->database = new Model\GiveModel(['database' => $config['database']]);
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
     * Handles coin give command calls
     * Usage: <nick> <amount>
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function giveCommand(CommandEvent $event, Queue $queue)
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
            $msg = 'Please use the following format: give <target nick> <amount>';
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
        $giveAttemptAmount = $params[1];
        $targetAvailWorth = $this->database->getUserAvailableWorth($target_id);
        $sourceAvailWorth = $this->database->getUserAvailableWorth($user_id);
        // user has enough to pay bail
        if ($giveAttemptAmount > $sourceAvailWorth*Settings::GIVE_MAX_PERCENT){
            $msg = 'You can only give up to 10% of your unbanked worth';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // target has enough to be taken
        if ($targetAvailWorth < $giveAttemptAmount){
            $msg = 'You target does not have that much to steal.';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // avoid giving to self
        if ($target_id == $user_id){
            $msg = 'Try giving to someone else!';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // check last bet time
        $lastGive = $this->database->getUserLastGiveTime($user_id);
        $elapsedTime = Time::timeElapsedInSeconds($lastGive);
        if ($elapsedTime < Settings::GIVE_INTERVAL && $elapsedTime !== NULL && $lastGive){
            $remaining = Format::formatTime( Settings::BET_INTERVAL - $elapsedTime);
            $msg = "you must wait {$remaining} before you can give again!";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }

        // pass command to callback to be verified and executed
        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }


    public function giveCallback(CommandCallback $callback)
    {
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $params = $event->getCustomParams();
        $giveAmount = $params[1];
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

        $this->database->addNewGive($user_id,$giveAmount, $target_id);
        $this->database->addCoinsToUser($target_id,$giveAmount);
        $this->database->removeCoinsFromUser($user_id,$giveAmount);

        $msg = "You successfully gave $targetNick $giveAmount coins.";
        $queue->ircNotice($sourceNick, $msg);
    }

    /**
     * gets steal info for user and outputs to irc line
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function giveInfoCommand(CommandEvent $event, Queue $queue)
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
        $lastGive = $this->database->getUserLastGiveTime($user_id);
        $tTimesGave =  $this->database->getUserTotalGives($user_id);
        $tGave = $this->database->getUserTotalGiveAmount($user_id);
        $tReceived =  $this->database->getUserTotalReceivedAmount($user_id);
        $tTimesReceived = $this->database->getUserTotalReceives($user_id);

        // send message
        $msg  = "[Times Given] {$tTimesGave} [Last] {$lastGive} [Total Given] {$tGave} ";
        $msg .= "[Times Received] {$tTimesReceived} [Total Received] {$tReceived}";
        $queue->ircPrivmsg($event->getSource(), $msg);
    }

}