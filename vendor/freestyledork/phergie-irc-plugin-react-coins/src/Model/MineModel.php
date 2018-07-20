<?php
/**
 * Handles database communication in Freestyledork\Phergie\Plugin\Coins Mine Plugin
 * using a PDO database as storage method.
 *
 * @category Phergie
 * @package Freestyledork\Phergie\Plugin\Coins\Model
 */

namespace Freestyledork\Phergie\Plugin\Coins\Model;

use Freestyledork\Phergie\Plugin\Coins\Utils\Format;
use Freestyledork\Phergie\Plugin\Coins\Utils\Time;
use Freestyledork\Phergie\Plugin\Coins\Utils\Roll;

class MineModel extends UserModel
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function getLevel($exp){
        $statement = $this->connection->prepare(
            'SELECT level
                        FROM levels
                       WHERE required_exp >= :exp
                    ORDER BY required_exp ASC
                       LIMIT 1'
        );
        if ($statement->execute([ ':exp' => $exp ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserMineExp($user_id)
    {
        $statement = $this->connection->prepare(
            'SELECT mine_exp
                        FROM users
                       WHERE user_id >= :user_id
                       LIMIT 1'
        );
        if ($statement->execute([ ':user_id' => $user_id ])) {
            $result = $statement->fetchColumn();
        }
        return $result;
    }

    public function getUserMineLevel($user_id){
        $exp = $this->getUserMineExp($user_id);
        return $this->getLevel($exp);
    }

    /**
     * get an array of [gem_id] & [name] for all gems
     *
     * @return array
     */
    public function getAllGems(){
        $statement = $this->connection->prepare(
            'SELECT gem_id, name
                        FROM gems'
        );
        if ($statement->execute()) {
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $result;
    }

}