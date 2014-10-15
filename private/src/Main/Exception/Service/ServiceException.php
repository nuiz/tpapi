<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/24/14
 * Time: 3:16 PM
 */

namespace Main\Exception\Service;


class ServiceException extends \Exception {
    protected $response = [];

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function __construct($response){
        $this->response = $response;
    }
} 