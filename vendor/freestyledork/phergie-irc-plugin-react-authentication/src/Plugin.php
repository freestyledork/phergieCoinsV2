<?php
/**
 * Phergie plugin for checking user authentication with nickserv ()
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-currency for the canonical source repository
 * @copyright Copyright (c) 2017 Mike (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\Testing
 */


namespace Freestyledork\Phergie\Plugin\Authentication;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
/**
 * Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\Authentication
 */
class Plugin extends AbstractPlugin
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
            'command.auth' => 'authCommand',
            'irc.received.notice'   => 'handleNotice',
        ];
    }

    /**
     *
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function authCommand(Event $event, Queue $queue)
    {
        // debug info
        $getValues['getCustomCommand'] = $event->getCustomCommand();
        $getValues['getCommand']       = $event->getCommand();
        $getValues['getHost']          = $event->getHost();
        $getValues['getNick']          = $event->getNick();
        $getValues['getPrefix']        = $event->getPrefix();
        $getValues['getSource']        = $event->getSource();
        $getValues['getMessage']       = $event->getMessage();
        $getValues['getParams']        = $event->getParams();
        $getValues['getTargets']       = $event->getTargets();
        $getValues['getUsername']      = $event->getUsername();
        $getValues['getCustomParams']  = $event->getCustomParams();

        $nick = $event->getNick();
        $userMask = sprintf('%s!%s@%s',
            $nick,
            $event->getUsername(),
            $event->getHost()
        );

        print_r($userMask);
        // end debug info

        $queue->ircPrivmsg($event->getSource(), "auth attempt received" );

        // store nick info for response handle.
        $nick = $getValues['getCustomParams'][0];
        $nick = strtolower($nick);

        // below might not be needed (overflow protection)
        if (in_array($nick,$this->authNicks)) {
            $queue->ircPrivmsg('#FSDChannel', "Command Pending for {$nick}. Please try again in a few seconds.");
        }

//        $this->authNicks[$nick]['acc'] = null;
//        $this->authNicks[$nick]['account'] = null;
//        $this->authNicks[$nick]['time'] = time(); // used to purge old bad entries
//        $this->authNicks[$nick]['event'] = $event;
        $this->authNicks[$nick] = new nickAuth($event,$nick);
//        $this->authNicks[$nick]['command'] = null; // used once name is verified
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
            $this->authNicks[$accInfo['nick']]->acc = $accInfo['value'];
            //print_r($accInfo);
            return;
        }

        // handle registered names
        if (preg_match($account, $message,$accountInfo)) {
            //$queue->ircPrivmsg('#FSDChannel', 'Account is: '. $accountInfo['account'] );
            $this->authNicks[$accountInfo['nick']]->account = $accountInfo['account'];
            //print_r($accountInfo);
        }

        // look for names that are not registered
        if(preg_match($notRegistered, $message,$accountInfo)){
            //$queue->ircPrivmsg('#FSDChannel', 'No Account for: '. $accountInfo['nick'] );
            $this->authNicks[$accountInfo['nick']]->account = 'Not Registered';
            //print_r($accountInfo);
        }

        $this->sendAuthReturn($queue);
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
            if ($authNick->acc === null || $authNick->account === null){
                continue;
            }
            if ($authNick->acc == 0 && $authNick->account === "Not Registered"){
                $queue->ircPrivmsg('#FSDChannel', "No Account for: {$nick}" );
                unset($this->authNicks[$nick]);
                continue;
            }
            if ($authNick->acc == 0){
                $queue->ircPrivmsg('#FSDChannel', "{$nick} is registered to {$authNick->account} but is not in use." );
                unset($this->authNicks[$nick]);
                continue;
            }
            if ($authNick->acc == 1){
                $queue->ircPrivmsg('#FSDChannel', "{$nick} is registered to {$authNick->account} but not logged in." );
                unset($this->authNicks[$nick]);
                continue;
            }
            if ($authNick->acc == 2){
                $queue->ircPrivmsg('#FSDChannel', "{$nick} is registered to {$authNick->account} and recognized." );
                unset($this->authNicks[$nick]);
                continue;
            }
            if ($authNick->acc == 3){
                $queue->ircPrivmsg('#FSDChannel', "{$nick} is registered to {$authNick->account} and logged in." );
                //$array['message'] = "event EMIT SUCCESS";
                $authNick->valid = true;
                $this->getEventEmitter()->emit('command.callback', [$authNick->event, $queue, $authNick]);
                unset($this->authNicks[$nick]);
                continue;
            }
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