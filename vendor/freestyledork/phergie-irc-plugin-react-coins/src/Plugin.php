<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Freestyledork\Phergie\Plugin\Coins\Helper\Response;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Freestyledork\Phergie\Plugin\Coins\Config\Settings;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Freestyledork\Phergie\Plugin\Coins\Helper\User;


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
        'command.coins.last'    => 'coinsLastCommand',
        'command.test'          => 'testCommand',
        'command.worth'         => 'worthCommand',
        'command.worth.info'    => 'worthInfoCommand',
        'command.coins.info'    => 'coinsInfoCommand',
        'command.bank'          => 'bankCommand',
        'command.bank.info'     => 'bankInfoCommand',
    ];

    /**
     * Array of callback events to listen.
     *
     * @var array
     */
    protected $callbackEvents = [
        'coins.callback.coins'  => 'coinsCallback',
        'coins.callback.test'   => 'testCallback',
        'coins.callback.bank'   => 'bankCallback',

    ];

    /**
     * Database Wrapper for basic coins info
     *
     * @var Model\CollectionModel
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
            $this->database = new Model\CollectionModel(['database' => $config['database']]);
        }
        if(isset($config['style'])){
            $this->text = $config['style'];
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
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function testCommand(CommandEvent $event, Queue $queue)
    {
        // allow passable nick for testing
        $nick = $event->getCustomParams()[0];
        $nick = strtolower($nick);

        $callback = new CommandCallback($event,$queue ,$nick);

        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }

    /**
     * Handles the testCommand CommandCallback response
     *
     * @param CommandCallback $callback
     */
    public function testCallback(CommandCallback $callback)
    {

        $callback->eventQueue->ircPrivmsg($callback->commandEvent->getSource(),Format::color("some random text",'white','red'));

    }

    /**
     * Handles coin command calls
     * @see coinsCallback()
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function coinsCommand(CommandEvent $event, Queue $queue)
    {
        $nick = strtolower($event->getNick());
        $callback = new CommandCallback($event,$queue ,$nick);
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);

        $this->getLogger()->info('Command received',
            ['COMMAND' => $event->getCustomCommand(),
                'PARAMS' => $event->getCustomParams()]);
    }

    /**
     * Finishes coin Callback
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function coinsCallback(CommandCallback $callback)
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

        // is the user already registered with coins
        $newUser = $this->database->isNewUser($user);
        var_dump($newUser);
        if ($newUser && $user->canRegister()){
            if ($user->accountName !== 'Not Registered'){
                $this->database->addNewRegisteredUser($user->accountName ,$user->nick);
            }else{
                $this->database->addNewNonRegisteredUser($user->nick);
            }
        }

        //check for new alias
        $user_id = $this->database->getUserIdByNick($user->nick);
        if (!$user_id){
            $this->database->addUserAlias($user->accountName,$user->nick);
            $user_id = $this->database->getUserIdByNick($user->nick);
        }

        // check ability to collect
        $lastCollection = $this->database->getUserLastCollectionTime($user_id);
        $elaspedTime = Time::timeElapsedInSeconds($lastCollection);
        $minInterval = Settings::COLLECT_INTERVAL;
        if ($lastCollection !== false && $elaspedTime <= $minInterval){
            // unable to collect
            $ft = Format::formatTime($minInterval - $elaspedTime);
            $msg = "You must wait {$ft} until you can do that again.";
            $queue->ircNotice($user->nick,$msg);
            return;
        }
        if ($lastCollection === false || $elaspedTime >= $minInterval){
            // able to collect
            $collectAmount = Roll::CollectionAmount();
            $this->database->addNewCollection($user_id, $collectAmount);
            $this->database->addCoinsToUser($user_id,$collectAmount);
            $msg = "You have collected {$collectAmount} more coins!";
            $queue->ircNotice($user->nick,$msg);
            return;
        }
    }

    /**
     * Handles coin help command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function coinsHelpCommand(CommandEvent $event, Queue $queue)
    {
        $nick = $event->getNick();
        $command = substr($event->getCustomCommand(), 0, -5);
        $queue->ircNotice($nick, 'Usage: '.$command);
        $queue->ircNotice($nick, 'Add some coins to your name.');
    }

    /**
     * Handles worth calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function worthCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $params = $event->getCustomParams();

        // find target
        if (count($params) == 0){
            $nick = $event->getNick();
        }else {
            $nick = $params[0];
        }

        // make sure user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircNotice($source, "I don't know you {$nick}!");
            return;
        }

        // get users worth
        $worth = $this->database->getUserWorthById($user_id);
        if ($worth == 0){
            $queue->ircNotice($source,"{$nick} is worthless!");
            return;
        }

        // format & return users worth to chat source
        $worth = Format::formatCoinAmount($worth);

        $queue->ircPrivmsg($source,"{$nick} is currently worth {$worth} coins.");
    }

    /**
     * displays info about last coins command
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function coinsLastCommand(CommandEvent $event, Queue $queue)
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
        // todo: get and return last collection info to user
        $lastCollected = $this->database->getUserLastCollectionInfo($user_id);

        $queue->ircPrivmsg($source, "[Last Collection] {$lastCollected['time']} [Amount] {$lastCollected['amount']}");

    }


    public function coinsInfoCommand(CommandEvent $event, Queue $queue)
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

        // populate info
        $totalCollections = $this->database->getUserTotalCollections($user_id);
        $avgCollections = $this->database->getUserAverageCollections($user_id);
        $age = $this->database->getUserAge($user_id);

        // return info to chat
        $queue->ircPrivmsg($source, "[Total Collections] {$totalCollections} [Average] {$avgCollections} [Age] {$age} days");
    }

    /**
     * Handles coin bet command calls
     *
     * @param CommandEvent $event
     * @param Queue $queue
     */
    public function bankCommand(CommandEvent $event, Queue $queue)
    {
        $nick = $event->getNick();
        $source = $event->getSource();
        $params = $event->getCustomParams();

        // make sure values are correct
        if (($params[0] !== 'withdrawal' && $params[0] !== 'deposit') || !is_numeric($params[1]) || $params[1] <= 0){
            $queue->ircNotice($nick,'Please use correct format: <bank> <deposit or withdrawal> <amount>');
            return;
        }

        // check user exists
        $user_id = $this->database->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircPrivmsg($source, "I don't know anyone named {$nick}!");
            return;
        }
        $callback = new CommandCallback($event,$queue ,strtolower($event->getNick()), $user_id);
        $this->getEventEmitter()->emit($callback->getAuthCallbackEventName(),[$callback]);
    }

    public function bankCallback(CommandCallback $callback)
    {
        $queue = $callback->eventQueue;
        $event = $callback->commandEvent;
        $user =  $callback->user;
        $source = $event->getSource();
        $params = $event->getCustomParams();
        $queue = $callback->eventQueue;

        // is the user in a valid state
        $response = $user->isValidIrc();
        if (!$response->value)
        {
            foreach ($response->getErrors() as $error){
                $queue->ircNotice($user->nick,$error);
            }
            return;
        }

        // check last bank time
        $user_id = $user->id;
        $lastBank = $this->database->getUserLastBankTime($user_id);

        if (Time::timeElapsedInSeconds($lastBank) < Settings::BANK_TRANSFER_INTERVAL && $lastBank !== false)
        {
            $remaining = Format::formatTime(Settings::BANK_TRANSFER_INTERVAL - Time::timeElapsedInSeconds($lastBank));
            $queue->ircNotice($user->nick, "You must wait {$remaining} before you can do that again!");
            return;
        }

        // handle bank options
        $fee = $params[1] * Settings::BANK_TRANSFER_FEE;
        $bankOption = $params[0];

        $bankedAmount = $this->database->getUserTotalBankAmount($user_id);
        if ($bankOption === 'withdrawal')
        {
            $transactionAmount = $params[1] - $fee;
            if ($bankedAmount > $transactionAmount) {
                $this->database->addUserBankTransaction($user_id,$transactionAmount);
                $queue->ircNotice($user->nick, "You successfully withdrew {$params[1]} coins with a {$fee} coins fee");
                return;
            }
                $queue->ircNotice($user->nick, "You only have {$bankedAmount} available, try a lower number");
                return;

        }
        if ($bankOption === 'deposit')
        {
            $worth = $this->database->getUserWorthById($user_id);
            $transactionAmount = $params[1] + $fee;

            $available = $worth - $bankedAmount;
            if ($available > $transactionAmount) {
                $this->database->addUserBankTransaction($user_id,$transactionAmount);
                $queue->ircNotice($user->nick, "You successfully deposited {$params[1]} coins with a {$fee} coins fee");
                return;
            }
                $queue->ircNotice($user->nick, "You only have {$available} coins available, try a lower number");
                return;
        }
    }

    public function bankInfoCommand(CommandEvent $event, Queue $queue)
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

        // populate info
        $bankedAmount = $this->database->getUserTotalBankAmount($user_id);

        $totalDeposits = $this->database->getUserTotalBankDeposits($user_id);
        $totalWithdrawals = $this->database->getUserTotalBankWithdrawals($user_id);
        $totalTransactions = $totalDeposits + $totalWithdrawals;


        $lastTransaction = $this->database->getUserLastBankTime($user_id);
        $last = Format::formatTime(Time::timeElapsedInSeconds($lastTransaction));

        // return info to chat
        $msg ="[Banked] {$bankedAmount} [Last] {$last} [Transactions] {$totalTransactions}";
        $msg .= " [Deposits] {$totalDeposits} [Withdrawals] {$totalWithdrawals}";
        $queue->ircPrivmsg($source, $msg);
    }

    public function worthInfoCommand(CommandEvent $event, Queue $queue)
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

        // populate info
        $bankedAmount = $this->database->getUserTotalBankAmount($user_id);
        $worth = $this->database->getUserWorthById($user_id);
        $available = $worth - $bankedAmount;

        // return info to chat
        $msg ="[Available] {$available} [Banked] {$bankedAmount} [Total] {$worth}";
        $queue->ircPrivmsg($source, $msg);
    }
}
