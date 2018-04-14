<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Lotto Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;
use Freestyledork\Phergie\Plugin\Coins\Utils\Settings;

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
    public function getUserTickets($user_id)
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
     * returns the users new ticket or false on failure
     *
     * @param int $user_id
     * @return string|bool
     */
    public function addNewUserTicket($user_id)
    {
        $ticket = Roll::LottoTicket();
        $statement = $this->connection->prepare(
            'INSERT INTO lotto_tickets (user_id, ticket) VALUES (?,?)'
        );
        if ($statement->execute([ $user_id,$ticket ])){
            return $ticket;
        }
        return false;
    }

    /**
     * @param $user_id
     * @return int
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
     * @return bool|array
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
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function getGrandPrizeAmount()
    {
        $base = Settings::LOTTO_BASE_PRIZE;
        $tickets = $this->getTotalTickets();
        $days = $this->getDaysSinceLastWin();
        return $base + ($tickets * Settings::LOTTO_TICKET_COST) + ($days * Settings::LOTTO_DAILY_BONUS);
    }

    /**
     * @return int
     */
    public function getDaysSinceLastWin()
    {
        $statement = $this->connection->prepare(
            'SELECT DATEDIFF(NOW(),date) 
                        FROM lotto_wins 
                    ORDER BY date desc 
                       LIMIT 1'
        );
        if ($statement->execute()) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @return int|bool
     */
    public function getTotalPlayers()
    {
        $result = false;
        $statement = $this->connection->prepare(
            'SELECT count(DISTINCT user_id) from lotto_tickets'
        );
        if ($statement->execute()) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @return int|bool
     */
    public function isWinToday()
    {
        $statement = $this->connection->prepare(
            'SELECT Count(*) 
                        FROM lotto_wins 
                       WHERE CAST(date AS DATE) = CAST(now() AS DATE)'
        );
        if ($statement->execute()) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @return bool|string
     */
    public function getTodayTicket()
    {
        $statement = $this->connection->prepare(
            'SELECT ticket
                        FROM lotto_drawings
                       WHERE CAST(date AS DATE) = CAST(now() AS DATE)'
        );
        if ($statement->execute()) {
            $ticket = $statement->fetchColumn();
        }
        if ($ticket === false){
            $ticket = $this->addNewTicketDrawing();
        }
        return $ticket;
    }

    /**
     * @return bool|string
     */
    public function addNewTicketDrawing()
    {
        $ticket = Roll::LottoTicket();
        $statement = $this->connection->prepare(
            'INSERT INTO lotto_drawings (ticket) VALUES (?)'
        );
        if ($statement->execute([ $ticket ])) {
            return $ticket;
        }
        return false;
    }

    /**
     * @param $ticket
     * @return bool
     */
    public function isWinningTicket($ticket)
    {
        return ($this->getTodayTicket() == $ticket)? true : false;
    }

}
