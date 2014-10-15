<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 6/28/14
 * Time: 11:30 AM
 */

namespace Main\Service;

use Main\Context\Context;

abstract class BaseService {
    protected $_version = 1;
    /**
     * @var Context $ctx;
     */
    protected $ctx;

    protected function __construct()
    {
    }

    final public static function getInstance()
    {
        static $instances = array();

        $calledClass = get_called_class();

        if (!isset($instances[$calledClass]))
        {
            $instances[$calledClass] = new $calledClass();
        }

        return $instances[$calledClass];
    }

    public function test(){}

    final private function __clone()
    {
    }
}