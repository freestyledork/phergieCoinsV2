<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

//use Freestyledork\Phergie\Plugin\Coins\Enum\ErrorType;
use Freestyledork\Phergie\Plugin\Coins\Helper\Error;
use Freestyledork\Phergie\Plugin\Coins\Helper\User;

class UserModel
{
    /**
     * PDO database object
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Creates a wrapper to handle PDO connection for Coins Plugin
     *
     * @param \PDO $connection
     */
    public function __construct(array $config = [])
    {
        $this->connection = $config['database'];
    }

    /**
     * @param $account
     * @param $nick
     * @return bool
     */
    public function addNewRegisteredUser($account, $nick)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (account,nick) VALUES (?,?)'
        );
        return $statement->execute([ $account,$nick ]);
    }

    /**
     * @param $nick
     * @return bool
     */
    public function addNewNonRegisteredUser($nick)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (nick) VALUES (?)'
        );
        return $statement->execute([ $nick ]);
    }

    /**
     * @param $account
     * @return bool|int
     */
    public function getUserIdByAccount($account)
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
                        FROM users
                       WHERE account = ?
                       LIMIT 1;'
        );
        if ($statement->execute([ $account ])) {
            return $statement->fetchColumn();
        }
        return false;
    }

    /**
     * @param $nick
     * @return bool|int
     */
    public function getUserIdByNick($nick)
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
                        FROM users
                       WHERE nick = ?
                       UNION 
                      SELECT user_id 
                        FROM aliases 
                       WHERE alias = ?
                       LIMIT 1'
        );
        if ($statement->execute([ $nick,$nick ])) {
            return $statement->fetchColumn();
        }
        return false;
    }

    /**
     * @param $user_id
     * @return bool|mixed
     */
    public function getUserInfoById($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT *
                        FROM users
                       WHERE user_id = ?
                       LIMIT 1;'
        );
        if ($statement->execute([ $user_id ])) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * @param $user_id
     * @return bool|mixed
     */
    public function getUserWorthById($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT worth
                        FROM users
                       WHERE user_id = ?
                       LIMIT 1;'
        );
        if ($statement->execute([ $user_id ])) {
            return $statement->fetchColumn();
        }
        return false;
    }

    /**
     * @param $user_id
     * @param $alias
     * @return bool
     */
    public function addUserAlias($account, $alias)
    {
        $user_id = $this->getUserIdByAccount($account);
        $statement = $this->connection->prepare(
            'INSERT INTO aliases (user_id,alias) VALUES (?,?)'
        );
        return $statement->execute([ $user_id,$alias ]);
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getUserAliases($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT alias
                        FROM aliases
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $result;
    }

    /**
     * @return bool|int
     */
    public function getTotalUsers(){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM users'
        );
        if ($statement->execute()) {
            return $statement->fetchColumn();
        }
        return false;
    }

    /**
     * @param $user_id
     * @param $amount
     * @return bool
     */
    public function addCoinsToUser($user_id, $amount){
        $statement = $this->connection->prepare(
            'UPDATE users SET worth = worth + ? WHERE user_id = ?'
        );
        return $statement->execute([$amount, $user_id ]);
    }

    /**
     * @param $user_id
     * @param $amount
     * @return bool
     */
    public function removeCoinsFromUser($user_id, $amount){
        $statement = $this->connection->prepare(
            'UPDATE users SET worth = worth - ? WHERE "user_id" = ?'
        );
        return $statement->execute([$amount, $user_id ]);
    }

    /**
     * @return array|bool
     */
    public function getAllUsedNicks(){
        $statement = $this->connection->prepare(
            'SELECT nick 
                      FROM users
                      UNION 
                      SELECT alias 
                      FROM aliases'
        );
        if ($statement->execute()) {
            return $statement->fetchAll(\PDO::FETCH_COLUMN);
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isNewUser(User $user){
        $user_id = $this->getUserIdByNick($user->nick);
        if (!$user_id && $user->accountName !== 'Not Registered'){
            $user_id = $this->getUserIdByAccount($user->accountName);
        }
        return ($user_id === false);
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function getUserCreationTime($user_id){
        $statement = $this->connection->prepare(
            'SELECT creation
                        FROM users
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    /**
     * @param $user_id
     * @return float
     */
    public function getUserAge($user_id){
        $creationDate = $this->getUserCreationTime($user_id);
        $date_time = date_create($creationDate);
        $diff = time() - $date_time->format('U');
        return floor($diff/ (60*60*24));
    }

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
     * @return int
     */
    public function getUserTotalBankAmount($user_id){
        $statement = $this->connection->prepare(
            'SELECT sum(amount)
                        FROM bank_transactions
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $amount =  $statement->fetchColumn();
        }
        if (!$amount) $amount = 0;
        return $amount;
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

    /**
     * @param $user_id
     * @return int
     */
    public function getUserTotalBankDeposits($user_id){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM bank_transactions
                       WHERE user_id = ? AND amount > 0'
        );
        if ($statement->execute([ $user_id ])) {
            $amount =  $statement->fetchColumn();
        }
        if (!$amount) $amount = 0;
        return $amount;
    }

    /**
     * @param $user_id
     * @return int
     */
    public function getUserTotalBankWithdrawals($user_id){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM bank_transactions
                       WHERE user_id = ? AND amount < 0'
        );
        if ($statement->execute([ $user_id ])) {
            $amount =  $statement->fetchColumn();
        }
        if (!$amount) $amount = 0;
        return $amount;
    }

    /**
     * @param $user_id
     * @return bool|int|mixed
     */
    public function getUserAvailableWorth($user_id)
    {
        $worth =$this->getUserWorthById($user_id);
        $banked = $this->getUserTotalBankAmount($user_id);

        return $worth-$banked;
    }
}