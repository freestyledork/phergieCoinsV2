<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Bet Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */
namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Utils\Settings;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Freestyledork\Phergie\Plugin\Coins\Helper\Response;
use Phergie\Irc\Plugin\React\Command\CommandEvent;

class BetModel extends UserModel
{


    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    // todo : add total hilo bets as well
    public function getUserTotalBets($user_id){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM bets
                  INNER JOIN bets_hilo
                          ON bets.user_id = bets_hilo.user_id
                       WHERE bets.user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserLastBetTime($user_id){
        $statement = $this->connection->prepare(
            'SELECT time
                        FROM bets
                       WHERE user_id = ?
                       UNION 
                      SELECT time
                        FROM bets_hilo
                       WHERE user_id = ? AND second_roll IS NOT NULL 
                   ORDER  BY time DESC
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id, $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function addNewBet($user_id, $amount, $roll){
        $statement = $this->connection->prepare(
            'INSERT INTO bets (user_id, amount, roll) VALUES (?,?,?)'
        );
        $statement->execute([ $user_id,$amount,$roll ]);
    }

    public function addNewBetHilo($user_id, $amount, $roll){
        $statement = $this->connection->prepare(
            'INSERT INTO bets_hilo (user_id, amount, first_roll) VALUES (?,?,?)'
        );
        $statement->execute([ $user_id,$amount,$roll ]);
    }

    public function getBetHiloTurn($user_id){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM bets_hilo
                       WHERE user_id = ? AND second_roll IS NULL'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        if ($result == 1){
            return 2;
        }
        return 1;
    }

    /**
     * validates the bet amount for the user, returns Response Obj
     *
     * @param int $amount
     * @param int $user_id
     * @return Response
     */
    public function isBetValid($amount, $user_id){
        $response = new Response(true);
        $betAmount = $amount;
        $elapsedTime = Time::timeElapsedInSeconds($this->getUserLastBetTime($user_id));

        // check last bet time
        if ($elapsedTime < Settings::BET_INTERVAL && $elapsedTime !== NULL){
            $remaining = Format::formatTime( Settings::BET_INTERVAL - $elapsedTime);
            $response->value = false;
            $response->addError("you must wait {$remaining} before betting again!");
            return $response;
        }
        // check value
        if (!is_numeric($betAmount)){
            $response->value = false;
            $response->addError("you're confusing me, try a numeric value");
            return $response;
        }
        // confirm they have enough
        if ($betAmount > $this->getUserWorthById($user_id)){
            $response->value = false;
            $response->addError("you don't have that much to bet");
            return $response;
        }
        // eliminate below 0 attempts
        if ($betAmount <= 0 ) {
            $response->value = false;
            $response->addError("you can't bet 0 or below");
            return $response;
        }
        return $response;
    }

}
