<?php

use Phergie\Irc\Connection;
use Phergie\Irc\Plugin\React\Command\Plugin as CommandPlugin;
use Phergie\Irc\Plugin\React\Quit\Plugin as QuitPlugin;
use Phergie\Irc\Plugin\React\AutoJoin\Plugin as AutoJoinPlugin;
use Phergie\Irc\Plugin\React\EventFilter as Filters;
use Phergie\Irc\Plugin\React\EventFilter\Plugin as EventFilterPlugin;
use Phergie\Irc\Plugin\React\NickServ\Plugin as NickServPlugin;
//use EnebeNb\Phergie\Plugin\Tell\Plugin as TellPlugin; todo
use Freestyledork\Phergie\Plugin\Coins\Plugin as CoinsPlugin;
use Freestyledork\Phergie\Plugin\Coins\BetPlugin;
use Freestyledork\Phergie\Plugin\Authentication\Plugin as AuthPlugin;
use Freestyledork\Phergie\Plugin\CallbackTest\Plugin as CallbackPlugin;


$credentials = json_decode(file_get_contents("credentials.json"),true);
$pdoConnStr  = "mysql:dbname={$credentials['databaseInfo']['dbName']};host={$credentials['databaseInfo']['ip']}";
$pdoUser     = $credentials['databaseInfo']['user'];
$pdoPass     = $credentials['databaseInfo']['pass'];
$ircNick     = $credentials['botID']['nick'];
$ircPass     = $credentials['botID']['pass'];
$database    = new PDO($pdoConnStr, $pdoUser, $pdoPass);
$connection  = new Connection(['serverHostname' => 'irc.freenode.net','username' => $ircNick,'realname' => $ircNick,'nickname' => $ircNick]);
$prefix      = '!';
$filterUser  = preg_quote('freestyledork!~freestyle@unaffiliated/freestyledork','/');
$nickServ    = new NickServPlugin(array('password' => $ircPass));
$command     = new CommandPlugin(['prefix' => $prefix]);
$autoJoin    = new AutoJoinPlugin(['channels' => array('#FSDChannel'),'wait-for-nickserv' => true]);
$auth        = new AuthPlugin();
$callback    = new CallbackPlugin();
$coins       = new CoinsPlugin(['database' => $database]);
$bet         = new BetPlugin(['database' => $database]);
$quit        = new QuitPlugin(['message' => 'because %s said so']);
$eventFilter = new EventFilterPlugin(['filter' => new Filters\UserFilter([$filterUser]),'plugins' => [$quit]]);



return array(
    // Plugins to include for all connections
    'plugins' => array(
        $command,
        $autoJoin,
        $nickServ,
        $auth,
        $callback,
        $eventFilter,
        $coins,
        $bet
    ),
    // Connections
    'connections' => array(
        $connection
    )
);