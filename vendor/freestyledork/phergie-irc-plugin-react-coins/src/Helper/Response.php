<?php
/**
 * Created by PhpStorm.
 * User: snow
 * Date: 3/17/18
 * Time: 4:03 PM
 */

namespace Freestyledork\Phergie\Plugin\Coins\Helper;

class Response
{
    /**
     * @var $errors Error[]
     */
    private $errors = array();
    /**
     * @var $value mixed
     */
    public $value;

    public function __construct($value, $error = null)
    {
        $this->value = $value;
        if ($error !== null){
            $this->errors[] = $error;
        }
    }

    public function setError($error){
        $this->errors = array($error);
    }

    public function addError($error){
        $this->errors[] = $error;
    }

    public function getErrors(){
        return $this->errors;
    }

}