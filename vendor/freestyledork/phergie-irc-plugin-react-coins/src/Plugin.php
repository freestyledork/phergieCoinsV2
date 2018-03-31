<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Freestyledork\Phergie\Plugin\Coins\Helper\Response;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Freestyledork\Phergie\Plugin\Coins\Utils\Settings;
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
        'command.coins.info'    => 'coinsInfoCommand'
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
        $minInterval = $this->database->getCollectIntervalInSeconds();
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
}
