<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 6/28/14
 * Time: 4:41 PM
 */

namespace Main;

use Mandango\Cache\FilesystemCache;
use Mandango\Connection;
use Mandango\Mandango;
use Pla2\Entity\Mapping\MetadataFactory;

class App {
    public static function start(){
        date_default_timezone_set('Asia/Bangkok');
        \Main\AutoRoute::dispatch();
    }
}