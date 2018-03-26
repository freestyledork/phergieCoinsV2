<?php
/**
 * @package Custom\Phergie\Plugin\Coins
 */

namespace Freestyledork\Phergie\Plugin\Coins\Utils;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as CommandEvent;
use Psr\Log\LoggerInterface;

class Log
{
    public static function Command(LoggerInterface $logger, CommandEvent $event){
        $command = ['COMMAND' => $event->getCustomCommand()];
        $params  = ['PARAMS'  => $event->getCustomParams()];
        $logger->info('Command received',[$command,$params]);
    }

    public static function Event(LoggerInterface $logger, $event){
        $command = ['EVENT' => $event];
        $logger->info('Event received',[$command]);
    }
}