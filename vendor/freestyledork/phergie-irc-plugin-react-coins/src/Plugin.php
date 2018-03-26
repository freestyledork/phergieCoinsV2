<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins;

use Freestyledork\Phergie\Plugin\Coins\Helper\Response;
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
        'command.test'          => 'testCommand',
        'command.worth'         => 'worthCommand',
        'command.info'          => 'infoCommand'
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
        $source = $callback->commandEvent->getSource();
        $callback->eventQueue->ircPrivmsg($source,'Callback success');
        $callback->eventQueue->ircNotice($source,'Callback success');

        $this->getLogger()->info(
            'Event received',
            ['CommandCallback' => $callback->commandEvent->getCustomCommand()]
        );
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
        $newUser = $this->collectionModel->isNewUser($user);
        var_dump($newUser);
        if ($newUser && $user->canRegister()){
            if ($user->accountName !== 'Not Registered'){
                $this->collectionModel->addNewRegisteredUser($user->accountName ,$user->nick);
            }else{
                $this->collectionModel->addNewNonRegisteredUser($user->nick);
            }
        }

        //check for new alias
        $user_id = $this->collectionModel->getUserIdByNick($user->nick);
        if (!$user_id){
            $this->collectionModel->addUserAlias($user->accountName,$user->nick);
            $user_id = $this->collectionModel->getUserIdByNick($user->nick);
        }

        // check ability to collect
        $lastCollection = $this->collectionModel->getUserLastCollectionTime($user_id);
        $elaspedTime = Time::timeElapsedInSeconds($lastCollection);
        $minInterval = $this->collectionModel->getCollectIntervalInSeconds();
        if ($lastCollection !== false && $elaspedTime <= $minInterval){
            // unable to collect
            $ft = Format::formatTime($minInterval - $elaspedTime);
            $msg = "You must wait {$ft} until you can do that again.";
            $queue->ircNotice($user->nick,$msg);
            return;
        }
        if ($lastCollection === false || $elaspedTime >= $minInterval){
            // able to collect
            $collectAmount = 175;
            $this->collectionModel->addNewCollection($user_id, $collectAmount);
            $this->collectionModel->addCoinsToUser($user_id,$collectAmount);
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

        if (count($params) == 0){
            $nick = $event->getNick();
        }else {
            $nick = $params[0];
        }

        $user_id = $this->collectionModel->getUserIdByNick($nick);

        if (!$user_id){
            $queue->ircNotice($source, "I don't know you {$nick}!");
            return;
        }

        $worth = $this->collectionModel->getUserWorthById($user_id);
        if ($worth == 0){
            $queue->ircNotice($source,"{$nick} is worthless!");
            return;
        }
        $worth = Format::formatCoinAmount($worth);
        $queue->ircNotice($source,"{$nick} is currently worth {$worth} coins.");
    }

    public function infoCommand(CommandEvent $event, Queue $queue)
    {
        $source = $event->getSource();
        $params = $event->getCustomParams();
        if (count($params) == 0){
            $nick = $event->getNick();
        }else {
            $nick = $params[0];
        }
        $user_id = $this->collectionModel->getUserIdByNick($nick);
        if (!$user_id){
            $queue->ircNotice($source, "I don't know anyone named {$nick}!");
            return;
        }

        $aliases = $this->collectionModel->getUserAliases($user_id);
        $info = $this->collectionModel->getUserInfoById($user_id);
        $aliasesString = implode(",",$aliases);

        echo "\n";
        var_dump($aliases);
        echo "\n";
        echo "\n";
        var_dump($aliasesString);

        echo "\n";

        $response = "";
        foreach ($info as $k => $v){
            $key = " [". $k . "] ";
            $response = $response . $key . $v;
        }
        $response = $response . " [aliases] " . $aliasesString;
        $queue->ircNotice($nick, "info for {$nick}: {$response}");
    }
}
