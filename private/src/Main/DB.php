<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/16/14
 * Time: 3:55 PM
 */

namespace Main;


class DB {
    /** @var  \MongoClient $mongo */
    private static $mongo, $db;
    public static function getMongo(){
        if(is_null(self::$mongo)){
            self::$mongo = new \MongoClient();
            //$connection = new MongoClient( "mongodb://example.com" );
            //$connection = new MongoClient( "mongodb://example.com:27017" );
        }
        return self::$mongo;
    }

    public static function getDB(){
        if(is_null(self::$db)){
            self::$db = self::getMongo()->demohotel;
        }
        return self::$db;
    }
}