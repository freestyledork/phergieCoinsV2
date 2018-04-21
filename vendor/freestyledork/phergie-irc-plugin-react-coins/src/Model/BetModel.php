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
use Freestyledork\Phergie\Plugin\Coins\Config\Settings;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Helper\Response;

class BetModel extends UserModel
{


    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function getUserTotalBets($user_id){
        $statement = $this->connection->prepare(
            'SELECT (SELECT count(*) FROM bets WHERE user_id = :user_id) 
                             +
                             (SELECT count(*) FROM bets_hilo WHERE user_id = :user_id)
                          AS total'
        );
        if ($statement->execute([ ':user_id' => $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return mixed
     */
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

    /**
     * @param $user_id
     * @param $amount
     * @param $roll
     * @param $payout
     */
    public function addNewBet($user_id, $amount, $roll, $payout){
        $statement = $this->connection->prepare(
            'INSERT INTO bets (user_id, amount, roll, payout) VALUES (?,?,?,?)'
        );
        $statement->execute([ $user_id,$amount,$roll,$payout ]);
    }

    /**
     * @param $user_id
     * @param $amount
     * @param $roll
     */
    public function addNewBetHilo($user_id, $amount, $roll){
        $statement = $this->connection->prepare(
            'INSERT INTO bets_hilo (user_id, amount, first_roll) VALUES (?,?,?)'
        );
        $statement->execute([ $user_id,$amount,$roll ]);
    }

    /**
     *
     * @param $user_id
     * @param $guess
     * @param $sRoll
     * @param $payout
     */
    public function updateBetHiloTurn($user_id, $guess, $sRoll, $payout)
    {
        $statement = $this->connection->prepare(
            'UPDATE bets_hilo 
                         SET guess = :guess, second_roll = :sRoll, payout = :payout 
                       WHERE user_id = :user_id AND guess IS NULL'
        );
        $binds = array( ':guess'    => $guess,
                        ':sRoll'    => $sRoll,
                        ':payout'   => $payout,
                        ':user_id'  => $user_id,
        );
        $statement->execute($binds);
    }

    /**
     *
     *
     * @param $user_id
     * @return int
     */
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

    public function getBetHiloFirstRoll($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT first_roll
                        FROM bets_hilo
                       WHERE user_id = ? AND second_roll IS NULL 
                   ORDER  BY time DESC
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getBetHiloFirstRollBetAmount($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT amount
                        FROM bets_hilo
                       WHERE user_id = ? AND second_roll IS NULL 
                   ORDER  BY time DESC
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return bool|int
     */
    public function getUserMostWon($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT payout FROM bets WHERE user_id = :user_id AND payout > 0
                       UNION
                      SELECT payout FROM bets_hilo WHERE user_id = :user_id AND payout > 0
                    ORDER BY payout DESC LIMIT 1'
        );
        if ($statement->execute([ ':user_id' => $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return bool|int
     */
    public function getUserMostLost($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT payout FROM bets 
                       WHERE user_id = :user_id AND payout IS NOT NULL AND payout < 0
                       UNION 
                      SELECT payout FROM bets_hilo 
                       WHERE user_id = :user_id AND payout IS NOT NULL AND payout < 0
                    ORDER BY payout ASC LIMIT 1'
        );
        if ($statement->execute([ ':user_id' => $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserTotalBetWins($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT (SELECT count(*) FROM bets WHERE payout > 0 AND user_id = :user_id)
                             +
                             (SELECT count(*) FROM bets_hilo WHERE payout > 0 AND user_id = :user_id)
                          AS total'
        );
        if ($statement->execute([ ':user_id' => $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
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
        $elapsedTime = $this->getUserLastBetTime($user_id);
        if ($elapsedTime){
            $elapsedTime = Time::timeElapsedInSeconds($elapsedTime);
        }

        // check last bet time
        if ($elapsedTime < Settings::BET_INTERVAL && $elapsedTime !== FALSE){
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
        if ($betAmount > $this->getUserAvailableWorth($user_id)){
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
