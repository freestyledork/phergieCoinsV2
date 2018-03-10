<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Phergie\Irc\Connection;
use Phergie\Irc\Plugin\React\Command\Plugin as CommandPlugin;
use Phergie\Irc\Plugin\React\Quit\Plugin as QuitPlugin;
use Phergie\Irc\Plugin\React\AutoJoin\Plugin as AutoJoinPlugin;
use Phergie\Irc\Plugin\React\EventFilter as Filters;
use Phergie\Irc\Plugin\React\EventFilter\Plugin as EventFilterPlugin;
use Phergie\Irc\Plugin\React\NickServ\Plugin as NickServPlugin;
//use EnebeNb\Phergie\Plugin\Tell\Plugin as TellPlugin; todo
use Freestyledork\Phergie\Plugin\Coins\Plugin as CoinsPlugin;
use Freestyledork\Phergie\Plugin\Coins\Ext\BetPlugin;
use Freestyledork\Phergie\Plugin\Coins\Ext\AuthPlugin;
use Freestyledork\Phergie\Plugin\CallbackTest\Plugin as CallbackPlugin;

$prefix      = '!';
$credentials = json_decode(file_get_contents("credentials.json"),true);

/**********************************************************
 * Logger Info
 *********************************************************/
$logger = new Logger('PhergieLog');
$logger->pushHandler(new StreamHandler('log/Testing.log', Logger::INFO));

$stderr = defined('\STDERR') && null !== \STDERR
    ? \STDERR : fopen('php://stderr', 'wb');

$handler = new StreamHandler($stderr, Logger::DEBUG);
$handler->setFormatter(new LineFormatter("%datetime% %level_name% %message% %context%\n"));
$logger->pushHandler($handler);


/**********************************************************
 * Database Info
 *********************************************************/
$dbIp       = $credentials['databaseInfo']['ip'];
$dbName     = $credentials['databaseInfo']['dbName'];
$dbUser     = $credentials['databaseInfo']['user'];
$dbPass     = $credentials['databaseInfo']['pass'];
$dbConnStr  = "mysql:dbname={$dbName};host={$dbIp}";
$database   = new PDO($dbConnStr, $dbUser, $dbPass);

/**********************************************************
 * Connection Info
 *********************************************************/
$ircChannels = $credentials['ircChannels'];
$ircNick     = $credentials['botID']['nick'];
$ircPass     = $credentials['botID']['pass'];
$connection  = new Connection(['serverHostname' => 'irc.freenode.net',
    'username' => $ircNick,'realname' => $ircNick,'nickname' => $ircNick]);

/**********************************************************
 * Plugins Info
 *********************************************************/
$nickServ    = new NickServPlugin(array('password' => $ircPass));
$command     = new CommandPlugin(['prefix' => $prefix]);
$autoJoin    = new AutoJoinPlugin(['channels' => $ircChannels,'wait-for-nickserv' => true]);
$auth        = new AuthPlugin();
$callback    = new CallbackPlugin();
$coins       = new CoinsPlugin(['database' => $database]);
$bet         = new BetPlugin(['database' => $database]);
$quit        = new QuitPlugin(['message' => 'because %s said so']);

/**********************************************************
 * Event Filter Info
 *********************************************************/
$quitUsers   = $credentials['quitUsers'];
foreach ($quitUsers as $key => $quitUser){
    $quitUsers[$key] =  preg_quote($quitUser,'/');
}
$quitFilter      = new Filters\UserFilter($quitUsers);
$quitEventFilter = new EventFilterPlugin(['filter' => $quitFilter,'plugins' => [$quit]]);

/**********************************************************
 * Response
 *********************************************************/
return array(
    // Plugins to include for all connections
    'plugins' => array(
        $command,
        $autoJoin,
        $nickServ,
        $auth,
        $callback,
        $quitEventFilter,
        $coins,
        $bet
    ),
    // Connections
    'connections' => array(
        $connection
    ),
    'logger' => $logger
);