<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/25/18
 * Time: 3:03 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Freestyledork\Phergie\Plugin\Coins\Config\Settings;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Model;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Utils\Log;

class LottoPlugin extends AbstractPlugin
{
    /**
     * Array of command events to listen.
     *
     * @var array
     */
    protected $commandEvents = [
        'command.lotto.buy'     => 'lottoBuyCommand',
        'command.lotto.info'    => 'lottoInfoCommand',
        'command.lotto.tickets' => 'lottoTicketsCommand'


    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.lotto.buy'        => 'lottoBuyCallback',
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
            $this->database = new Model\LottoModel(['database' => $config['database']]);
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
     * Supported Options:
     *  <amount> buy more than 1
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function lottoBuyCommand(CommandEvent $event, Queue $queue)
    {
        Log::Command($this->getLogger(),$event);

        /** basic validation **/

        // check for winner TODO: add new day check
        if($this->database->isWinToday()){
            $msg = "Sorry {$event->getNick()}, looks like someone already won today, the lotto will reset at 00:00 -5UTC";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // check user exists
        $user_id = $this->database->getUserIdByNick($event->getNick());
        if (!$user_id){
            $msg = "Sorry {$event->getNick()}, you must use coins command before using the lotto.";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        $params = $event->getCustomParams();
        // make sure param is a number if passed
        if (count($params)>=1 && !is_numeric($params[0])){
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
        // limit daily purchase amount
        $buyAmount = (count($params)>=1) ? $params[0] : 1;
        $boughtTodayAmount = $this->database->getUserDailyTicketCount($user_id);
        if ($boughtTodayAmount == Settings::LOTTO_DAILY_TICKET_LIMIT){
            $msg = 'Looks like you already bought all the lotto tickets you could today, try again tomorrow.';
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        if ($boughtTodayAmount + $buyAmount > Settings::LOTTO_DAILY_TICKET_LIMIT){
            $a = Settings::LOTTO_DAILY_TICKET_LIMIT - $boughtTodayAmount;
            $msg = "Looks like you can only purchase $a ticket(s)";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }
        // make sure user can afford
        $aWorth = $this->database->getUserAvailableWorth($user_id);
        if ($aWorth < (Settings::LOTTO_TICKET_COST * $buyAmount)){
            $msg = "Sorry {$event->getNick()}, you cant afford that many lotto ticket(s).";
            $queue->ircNotice($event->getNick(), $msg);
            return;
        }

        // emit account verification callback
        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()),$user_id);
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }

    public function lottoBuyCallback(CommandCallback $callback)
    {
        // init helpful vars
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $source = $event->getSource();
        $nick = $user->nick;
        $user_id = $user->id;

        // is the user in a valid state
        $response = $user->isValidIrc();
        if (!$response->value){
            foreach ($response->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }

        // get buying amount
        $params = $event->getCustomParams();
        $buyAmount = (count($params)>=1) ? $params[0] : 1;

        for($i = 0; $i<$buyAmount; $i++)
        {
            $ticket = $this->database->addNewUserTicket($user_id);
            $won = $this->database->isWinningTicket($ticket);
            if ($won){
                $prize = $this->database->getGrandPrizeAmount();
                $msg = "{$nick} CONGRATS! You won the lotto with a grand prize of {$prize} coins!";
                $queue->ircPrivmsg($source, $msg);
                break;
            }
        }
        if (!$won){
            $msg = "You successfully bought $buyAmount tickets!";
            $queue->ircPrivmsg($source, $msg);
        }
    }

    /**
     * return info about the current lotto
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function lottoInfoCommand(CommandEvent $event, Queue $queue)
    {
        Log::Command($this->getLogger(),$event);
        $prize = $this->database->getGrandPrizeAmount();
        $sold = $this->database->getTotalTickets();
        $days = $this->database->getDaysSinceLastWin();
        $players = $this->database->getTotalPlayers();
        $tTicket = $this->database->getTodayTicket();
        $msg = "[Grand Prize] {$prize} [Players] {$players} [Tickets Sold] {$sold} ";
        $msg .= "[Today's Ticket] {$tTicket} [Duration] {$days} Day(s)";
        $queue->ircPrivmsg($event->getSource(), $msg);
    }

    /**
     * return user tickets
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function lottoTicketsCommand(CommandEvent $event, Queue $queue)
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
        $tickets = $this->database->getUserTickets($user_id);
        $tCount = count($tickets);
        $msg = "[User] {$nick} [Total Tickets] {$tCount} [Ticket Numbers] |" . implode("|",$tickets). "|";
        $queue->ircNotice($event->getNick(), $msg);
    }

}