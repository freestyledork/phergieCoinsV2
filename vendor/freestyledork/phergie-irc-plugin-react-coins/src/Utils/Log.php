<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Freestyledork\Phergie\Plugin\Coins\Helper\CommandCallback;
use Psr\Log\LoggerInterface;

class Log
{
    public static function Command(LoggerInterface $logger, CommandEvent $event){
        $command = $event->getCustomCommand();
        $params  = ['PARAMS'  => $event->getCustomParams()];
        $source = $event->getSource();
        $issuer = $event->getNick();
        $logger->info("{$command} COMMAND triggered by {$issuer} in {$source}",[$params]);
    }

    public static function Callback(LoggerInterface $logger, CommandCallback $callback){

        $command = $callback->getCallbackEvent();
        $params  = ['PARAMS'  => $callback->commandEvent->getParams()];

        $logger->info("{$command} CALLBACK triggered",[$params]);
    }

    public static function Line(LoggerInterface $logger, $line){
        $logger->info($line);
    }

}