<?php
/**
 * Phergie plugin for checking user authentication with nickserv
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Ext
 */

namespace Freestyledork\Phergie\Plugin\Coins\Ext;

use Freestyledork\Phergie\Plugin\Coins\Utils\Log;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use Phergie\Irc\Event\UserEventInterface as UserEvent;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;


class AuthPlugin extends AbstractPlugin
{
    /**
     * Name of the NickServ agent
     *
     * @var string
     */
    protected $botNick = 'NickServ';

    /**
     * @var CommandCallback[] $authCallbacks
     */
    protected $authCallbacks = [];

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {

    }

    /**
     * Array of Authentication events to listen for.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'coins.callback.auth' => 'startAuthentication',
            'irc.received.notice' => 'handleNotice',
        ];
    }

    /**
     * @param CommandCallback $callback
     */
    public function startAuthentication( CommandCallback $callback )
    {
        $event = $callback->commandEvent;
        $queue = $callback->eventQueue;
        $nick = $callback->user->nick;

        Log::Line($this->getLogger(),"auth attempt received for {$nick}");

        // below might not be needed (overflow protection)
        if (in_array($nick,$this->authCallbacks)) {
            $queue->ircPrivmsg('#FSDChannel', "Command Pending for {$nick}. Please try again in a few seconds.");
        }

        $this->authCallbacks[$nick] = $callback;

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
    public function handleNotice(UserEvent $event)
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

        /** TODO: possibly find better solution to remove formatting. */
        $params = $event->getParams();
        $message = preg_replace('/[^\x20-\x7E]/','', $params['text']);
        $message = strtolower($message);

        // handle nick status
        if (preg_match($acc, $message,$accInfo)) {
            // ACC also returns the unique entity ID of the given account.
            // The answer is in the form <nick> [-> account] ACC <digit> <EID>,
            // where <digit> is one of the following:
            //   0 - account or user does not exist
            //   1 - account exists but user is not logged in
            //   2 - user is not logged in but recognized (see ACCESS)
            //   3 - user is logged in
            $this->authCallbacks[$accInfo['nick']]->user->accLevel = $accInfo['value'];
            return;
        }

        // handle registered names
        if (preg_match($account, $message,$accountInfo)) {
            $this->authCallbacks[$accountInfo['nick']]->user->accountName = $accountInfo['account'];
        }

        // look for names that are not registered
        if(preg_match($notRegistered, $message,$accountInfo)){
            $this->authCallbacks[$accountInfo['nick']]->user->accountName = 'Not Registered';
        }

        $this->returnCallbacks();
    }

    /**
     * After authentication is completed, emit callback event
     */
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
}