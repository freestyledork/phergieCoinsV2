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
use Freestyledork\Phergie\Plugin\Coins\Config\Settings;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;

class GiveModel extends UserModel
{

    /**
     * @param $user_id
     * @return null|\DateTime
     */
    public function getUserLastGiveTime($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT time
                        FROM gives
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
    public function getUserTotalGives($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM gives
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @param $amount
     * @param $target_id
     * @return bool
     */
    public function addNewGive($user_id, $amount, $target_id)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO gives (user_id, amount, target_id) VALUES (?,?,?)'
        );
        return $statement->execute([ $user_id, $amount, $target_id]);
    }

    /**
     * @param $user_id
     * @return int:bool
     */
    public function getUserTotalGiveAmount($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT SUM(amount)
                        FROM gives
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result=null?0:$result;
    }

    /**
     * @param $user_id
     * @return int:bool
     */
    public function getUserTotalReceivedAmount($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT SUM(amount)
                        FROM gives
                       WHERE target_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result===null?0:$result;
    }

    /**
     * @param $user_id
     * @return int:bool
     */
    public function getUserTotalReceives($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM gives
                       WHERE target_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

}