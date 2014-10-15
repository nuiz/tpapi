<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/19/14
 * Time: 4:47 PM
 */

namespace Main\Helper;


class URL {
    public static function absolute($url){
        return "http://demohotel-api.pla2app.com".$url;
    }

    public static function share($url){
        return "http://pla2app.com/demohotel/share".$url;
    }
}