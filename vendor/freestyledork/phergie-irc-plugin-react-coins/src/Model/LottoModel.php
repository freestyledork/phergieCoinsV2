<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Lotto Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;

class LottoModel extends UserModel
{
    /**
     * @return int
     */
    public function getTotalTickets()
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM lotto_tickets'
        );
        if ($statement->execute()) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return int
     */
    public function getUserTotalTickets($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM lotto_tickets
                       WHERE user_id = ?'
        );
        if ($statement->execute([$user_id])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getUserTicketNumbers($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT ticket
                        FROM lotto_tickets
                       WHERE user_id = ?'
        );
        if ($statement->execute([$user_id])) {
            $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getAllTicketNumbers()
    {
        $statement = $this->connection->prepare(
            'SELECT ticket
                        FROM lotto_tickets'
        );
        if ($statement->execute()) {
            $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $result;
    }

    /**
     * @param int $user_id
     * @param string $ticket
     * @return bool
     */
    public function addNewUserTicket($user_id, $ticket)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO lotto_tickets (user_id, ticket) VALUES (?,?)'
        );
        return $statement->execute([ $user_id,$ticket ]);
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getUserDailyTicketCount($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT count(*)
                        FROM lotto_tickets
                       WHERE user_id = ? AND DATE(purchase_date) = CURDATE()'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @return bool|\PDOStatement
     */
    public function clearAllTickets()
    {
        return $this->connection->query('TRUNCATE TABLE lotto_tickets');
    }

    /**
     * @param $ticket
     * @return bool
     */
    public function addNewWinningTicket($ticket)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO lotto_wins (ticket) VALUES (?)'
        );
        return $statement->execute([ $ticket ]);
    }

    /**
     * @param $user_id
     * @param $amount
     * @param $ticket
     * @return bool
     */
    public function addUserToWinningTicket($user_id, $amount, $ticket)
    {
        $statement = $this->connection->prepare(
            'UPDATE lotto_wins SET user_id = ?, amount = ? WHERE ticket = ?'
        );
        return $statement->execute([ $user_id,$amount,$ticket ]);
    }

    /**
     * @return bool
     */
    public function getLastLottoWinner()
    {
        $statement = $this->connection->prepare(
            'SELECT user_id, ticket, amount, date
                        FROM lotto_wins
                       WHERE user_id IS NOT NULL
                    ORDER BY date DESC
                       LIMIT 1'
        );
        if ($statement->execute()) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

}
