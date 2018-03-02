<?php

use Phergie\Irc\Connection;
use Phergie\Irc\Plugin\React\Command\Plugin as CommandPlugin;
use Phergie\Irc\Plugin\React\Quit\Plugin as QuitPlugin;
use Phergie\Irc\Plugin\React\AutoJoin\Plugin as AutoJoinPlugin;
//use EnebeNb\Phergie\Plugin\Tell\Plugin as TellPlugin;
//use Custom\Phergie\Plugin\Coins\Plugin as CoinsPlugin;
use Freestyledork\Phergie\Plugin\Authentication\Plugin as AuthPlugin;
use Freestyledork\Phergie\Plugin\CallbackTest\Plugin as CallbackPlugin;

$credentials = json_decode(file_get_contents("credentials.json"),true);
$pdoConnStr = "mysql:dbname={$credentials['databaseInfo']['dbName']};host={$credentials['databaseInfo']['ip']}";
$pdoUser = $credentials['databaseInfo']['user'];
$pdoPass = $credentials['databaseInfo']['pass'];
$ircNick = $credentials['botID']['nick'];
$prefix = '!';

$command = new CommandPlugin(['prefix' => $prefix]);
$quit = new QuitPlugin(['message' => 'because %s said so']);
$autoJoin = new AutoJoinPlugin(['channels' => array('#FSDChannel') , 'wait-for-nickserv' => false]);

$auth = new AuthPlugin();
$callback = new CallbackPlugin();


return array(

    // Plugins to include for all connections

    'plugins' => array(
        $command,
        $quit,
        $autoJoin,
        $auth,
        $callback
    ),



    'connections' => array(
        new Connection(array(
            // Required settings
            'serverHostname' => 'irc.freenode.net',
            'username' => $ircNick,
            'realname' => $ircNick,
            'nickname' => $ircNick
        )),
    )
);