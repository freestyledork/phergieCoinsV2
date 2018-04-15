<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Steal Plugin
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

class StealModel extends UserModel
{
    /**
     * @param $user_id
     * @return null|\DateTime
     */
    public function getUserLastStealTime($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT time
                        FROM steal_attempts
                       WHERE user_id = ?
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
     * @return mixed
     */
    public function getUserTotalStealAttempts($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM steal_attempts
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function getUserTotalStealSuccess($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM steal_attempts
                       WHERE user_id = ? AND result = 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @param $amount
     * @param $result
     * @return bool
     */
    public function addNewStealAttempt($user_id, $amount, $result)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO steal_attempts (user_id, amount, result) VALUES (?,?,?)'
        );
        return $statement->execute([ $user_id,$amount,$result]);
    }

    /**
     * @param $user_id
     * @return float
     */
    public function getUserStealSuccessRate($user_id)
    {
        $successRate = Settings::STEAL_BASE_SUCCESS_PERCENT;
        $resetDuration = -1 * Settings::STEAL_RESET_INTERVAL;
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM steal_attempts
                       WHERE user_id = ? AND time > DATE_ADD(NOW(), INTERVAL ? SECOND)'
        );
        if ($statement->execute([ $user_id,$resetDuration ] )){
            $result = $statement->fetchColumn();
            while ($result > 0){
                $successRate = $successRate / 2;
                $result--;
            }
        }
        return $successRate;
    }

    public function getUserWorstLoss($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT amount 
                        FROM steal_attempts
                       WHERE user_id = ?
                         AND RESULT = 0
                    ORDER BY amount DESC 
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserHighestSteal($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT amount 
                        FROM steal_attempts
                       WHERE user_id = ?
                         AND RESULT = 1
                    ORDER BY amount DESC 
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }
}