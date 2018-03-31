<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Bank Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;

class BankModel extends UserModel
{

    /**
     * @param $user_id
     * @return null|\DateTime
     */
    public function getUserLastBankTime($user_id){
        $statement = $this->connection->prepare(
            'SELECT time
                        FROM bank_transactions
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
     * @return int|mixed
     */
    public function getUserTotalBankAmount($user_id){
        $statement = $this->connection->prepare(
            'SELECT sum(amount)
                        FROM bank_transactions
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            return $statement->fetchColumn();
        }
        return 0;
    }

    /**
     * @param $user_id
     * @param $amount
     * @return bool
     */
    public function addUserBankTransaction($user_id, $amount){
        $statement = $this->connection->prepare(
            'INSERT INTO bank_transactions (user_id, amount) VALUES (?,?)'
        );
        return $statement->execute([ $user_id,$amount]);
    }
}