<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 2:34 PM
 */

namespace Main\Helper;


class GenerateHelper {
    public static function tokenById($id){
        return md5(uniqid($id, true));
    }
} 