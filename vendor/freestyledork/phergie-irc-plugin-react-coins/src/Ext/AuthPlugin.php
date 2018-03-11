<?php
/**
 * Phergie plugin for checking user authentication with nickserv
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Ext
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
use Freestyledork\Phergie\Plugin\Coins\CommandCallback;
use Phergie\Irc\Tests\Plugin\React\EventFilter\CallbackPlugin;

class AuthPlugin extends AbstractPlugin
{
    /**
     * Name of the NickServ agent
     *
     * @var string
     */
    protected $botNick = 'NickServ';
    /**
     * irc Nicks to be authenticated
     *
     * @var array $nickAuth obj
     */
    protected $authNicks = [];

    /**
     * @var CommandCallback[] $authCallbacks
     */
    protected $authCallbacks = [];
    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {

    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'coins.callback.auth' => 'authInit',
            'irc.received.notice'   => 'handleNotice',
        ];
    }


    /**
     * @param Event $event
     * @param Queue $queue
     * @param callable $callback
     */
    public function authInit(Event $event, Queue $queue, CommandCallback $callback)
    {

        $nick = $callback->user->nick;
        $queue->ircPrivmsg($event->getSource(), "auth attempt received {$nick}" );

        // below might not be needed (overflow protection)
        if (in_array($nick,$this->authNicks)) {
            $queue->ircPrivmsg('#FSDChannel', "Command Pending for {$nick}. Please try again in a few seconds.");
        }

        $this->authCallbacks[$nick] = $callback;
//        $this->authNicks[$nick] = new nickAuth($event,$nick);

        //print_r($this->authNicks);

        // init nickserv response
        $queue->ircPrivmsg($this->botNick, 'ACC ' . $nick );
        $queue->ircPrivmsg($this->botNick, 'INFO ' . $nick );
    }

    /**
     * Stores Account info response from nickserv
     *
     * @param \Phergie\Irc\Event\UserEventInterface $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleNotice(UserEvent $event, Queue $queue)
    {
        // acc regex
        $acc = '/(?P<nick>[a-zA-Z0-9_\-\[\]{}^`|]+)\s+acc\s(?P<value>\d)/';

        //valid account regex
        $account = '/information\son\s(?P<nick>[a-zA-Z0-9_\-\[\]{}^`|]+)\s\(account\s(?P<account>[a-zA-Z0-9_\-\[\]{}^`|]+)\)/';

        //invalid account regex
        $notRegistered = '/(?P<nick>[a-zA-Z0-9_\-\[\]{}^`|]+)\s+is\s+not\s+registered/';

        // end info regex
        $endInfo = '/(?P<end>\*{3} end of info)/';

        // Ignore notices that aren't from the NickServ agent
        if (strcasecmp($event->getNick(), $this->botNick) !== 0) {
            return;
        }

        $params = $event->getParams();
        $message = strtolower($params['text']);
        $message = strip_tags($message);
        $message = preg_replace("/[^a-zA-Z0-9().@\/~\-_:{}^`* ]+/", "", $message);

        //$queue->ircPrivmsg('#FSDChannel', $message );

        // ACC also returns the unique entity ID of the given account.
        // The answer is in the form <nick> [-> account] ACC <digit> <EID>,
        // where <digit> is one of the following:
        //   0 - account or user does not exist
        //   1 - account exists but user is not logged in
        //   2 - user is not logged in but recognized (see ACCESS)
        //   3 - user is logged in

        // handle nick status
        if (preg_match($acc, $message,$accInfo)) {
            //$queue->ircPrivmsg('#FSDChannel', $accInfo['nick'] . ' is: '. $accInfo['value'] );
//            $this->authNicks[$accInfo['nick']]->acc = $accInfo['value'];
            $this->authCallbacks[$accInfo['nick']]->user->accLevel = $accInfo['value'];
            //print_r($accInfo);
            return;
        }

        // handle registered names
        if (preg_match($account, $message,$accountInfo)) {
            //$queue->ircPrivmsg('#FSDChannel', 'Account is: '. $accountInfo['account'] );
//            $this->authNicks[$accountInfo['nick']]->account = $accountInfo['account'];
            $this->authCallbacks[$accountInfo['nick']]->user->accountName = $accountInfo['account'];
            //print_r($accountInfo);
        }

        // look for names that are not registered
        if(preg_match($notRegistered, $message,$accountInfo)){
            //$queue->ircPrivmsg('#FSDChannel', 'No Account for: '. $accountInfo['nick'] );
//            $this->authNicks[$accountInfo['nick']]->account = 'Not Registered';
            $this->authCallbacks[$accountInfo['nick']]->user->accountName = 'Not Registered';
            //print_r($accountInfo);
        }

//        $this->sendAuthReturn($queue);
        $this->returnCallbacks();
    }

    protected function returnCallbacks()
    {
        foreach ($this->authCallbacks as $nick => $callback) {
            // purge unprocessed entries older then 30 seconds.
            if (time() - $callback->time >= 30) {
                unset($this->authCallbacks[$nick]);
                continue;
            }
            // only emit callback if account info is populates
            if ($callback->user->accountName !== null && $callback->user->accLevel !== null)
            {
                $this->getEventEmitter()->emit($callback->getCallbackEvent(),[$callback]);
                unset($this->authCallbacks[$nick]);
            }
        }
    }


    protected function sendAuthReturn(Queue $queue){
        //print_r($this->authNicks);
        //TODO add command dispatch/router
        foreach ($this->authNicks as $nick => $authNick){
            //print_r($authNick);
            // purge unprocessed entries older then 30 seconds.
            if (time() - $authNick->time >= 30) {
                unset($this->authNicks[$nick]);
                continue;
            }
            //probably redundant check

        }
    }
}
class nickAuth{
    public $acc = null;
    public $account = null;
    public $nick = null;
    public $time = null;
    public $event = null;
    public $valid = null;

    public function __construct($event, $nick){
        $this->event = $event;
        $this->nick = $nick;
        $this->time = time();
    }
}