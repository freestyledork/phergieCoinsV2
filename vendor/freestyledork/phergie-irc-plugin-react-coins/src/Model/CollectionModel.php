<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */
namespace Freestyledork\Phergie\Plugin\Coins\Model;

class CollectionModel extends UserModel
{

    public function addNewCollection($user_id, $amount){
        $statement = $this->connection->prepare(
            'INSERT INTO collections (user_id, amount) VALUES (?,?)'
        );
        $statement->execute([ $user_id,$amount ]);
    }

    public function getUserTotalCollections($user_id){
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
                        FROM collections
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserAverageCollections($user_id){
        $statement = $this->connection->prepare(
            'SELECT AVG(amount)
                        FROM collections
                       WHERE user_id = ?'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserLastCollectionInfo($user_id){
        $statement = $this->connection->prepare(
            'SELECT *
                        FROM collections
                       WHERE user_id = ?
                   ORDER  BY time DESC
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function getUserLastCollectionTime($user_id){
        $statement = $this->connection->prepare(
            'SELECT time
                        FROM collections
                       WHERE user_id = ?
                   ORDER  BY time DESC
                       LIMIT 1'
        );
        if ($statement->execute([ $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }



}
