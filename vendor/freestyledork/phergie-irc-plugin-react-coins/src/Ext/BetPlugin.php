<?php
/**
 * Plugin for users betting coins
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Ext
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Freestyledork\Phergie\Plugin\Coins\Helper\Response;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Utils\Log;

class BetPlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.bet'           => 'betCommand',
        'command.bet.hilo'      => 'hiloCommand',
        'command.bet.last'      => 'betLastCommand',
        'command.bet.info'      => 'betInfoCommand',
        'command.high'          => 'highCommand',
        'command.low'           => 'lowCommand'
    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.bet'        => 'betCallback',
        'coins.callback.bet.hilo'   => 'hiloCallback',
    ];

    /**
     * database model
     * @var Model\BetModel
     */
    protected $database;

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
            $this->database = new Model\BetModel(['database' => $config['database']]);
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
     * Handles coin bet command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function betCommand(CommandEvent $event, Queue $queue)
    {
        Log::Command($this->getLogger(),$event);

        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }

    /**
     * @param CommandCallback $callback
     */
    public function betCallback(CommandCallback $callback)
    {
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $source = $event->getSource();
        $nick = $user->nick;

        // is the user in a valid state
        $response = $user->isValidIrc();
        if (!$response->value){
            foreach ($response->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }

        // check user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircPrivmsg($source, "Sorry {$nick}, you must use coins command before betting.");
            return;
        }

        // validate bet amount
        count($event->getCustomParams()) > 0 ? $amount = $event->getCustomParams()[0] : $amount = 0;
        $validBet = $this->database->isBetValid($amount,$user_id);
        if (!$validBet->value){
            foreach ($validBet->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }

        // handle bet
        $roll = Roll::OneToOneHundred();
        $multiplier = $this->_getBetMultiplier($roll);
        // handle wins
        if ($multiplier > 0){
            $winAmount = $amount * $multiplier;
            $this->database->addCoinsToUser($user_id,$amount * ($multiplier-1));
            $msg = "You rolled a {$roll} and won {$winAmount} more coins!";

        }
        // handle losses
        else {
            $this->database->removeCoinsFromUser($user_id,$amount);
            $msg = "You rolled a {$roll} and lost {$amount} more coins!";
        }

        $this->database->addNewBet($user_id, $amount, $roll);
        $queue->ircNotice($nick,$msg);
        Log::Callback($this->getLogger(),$callback);
    }

    /**
     * Handles coin bet hilo command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function hiloCommand(CommandEvent $event, Queue $queue)
    {
        Log::Command($this->getLogger(),$event);

        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()));
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }

    /**
     * @param CommandCallback $callback
     */
    public function hiloCallback(CommandCallback $callback)
    {
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $source = $event->getSource();
        $nick = $user->nick;
        // is the user in a valid state
        $response = $user->isValidIrc();
        if (!$response->value){
            foreach ($response->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }

        // check user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircPrivmsg($source, "Sorry {$nick}, you must use coins command before betting.");
            return;
        }

        // get turn
        $turn = $this->database->getBetHiloTurn($user_id);
        if ($turn === 1){
            //start a new turn

            // validate bet amount
            count($event->getCustomParams()) > 0 ? $amount = $event->getCustomParams()[0] : $amount = 0;
            $validBet = $this->database->isBetValid($amount,$user_id);
            if (!$validBet->value){
                foreach ($validBet->getErrors() as $error){
                    $queue->ircNotice($nick,$error);
                }
                return;
            }

            // log and return first roll
            $roll = Roll::ZeroToOneHundred();
            $this->database->addNewBetHilo($user_id,$amount,$roll);
            $msg = "Your first roll is {$roll}";
            $queue->ircNotice($nick,$msg);

        }else{
            //finish old turn
            $roll = $this->database->getBetHiloFirstRoll($user_id);
            // validate second command
            $msg = "Your first roll was {$roll}";
            $queue->ircNotice($nick,$msg);
        }


    }

    /**
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function betLastCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $params = $event->getCustomParams();

        // decide who to target
        if (count($params) == 0){
            $nick = $event->getNick();
        }else {
            $nick = $params[0];
        }

        // check user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircPrivmsg($source, "I don't know anyone named {$nick}!");
            return;
        }
    }

    /**
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function betInfoCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $params = $event->getCustomParams();

        // decide who to target
        if (count($params) == 0){
            $nick = $event->getNick();
        }else {
            $nick = $params[0];
        }

        // check user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircPrivmsg($source, "I don't know anyone named {$nick}!");
            return;
        }

    }

    /**
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function highCommand(CommandEvent $event, Queue $queue)
    {

    }

    /**
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function lowCommand(CommandEvent $event, Queue $queue)
    {

    }


    private function _handleHiloHighCommand()
    {

    }

    private function _handleHiloLowCommand()
    {

    }

    /**
     * @param $roll
     * @return int
     */
    private function _getBetMultiplier($roll)
    {
        if ($roll == 100) return 5;
        if ($roll >= 90) return 3;
        if ($roll > 50) return 2;
        return -1;
    }

    /**
     * @param $roll
     * @return int
     */
    private function _getBetHiloMultiplier($roll)
    {
        // find bet
        return -1;
    }
}
