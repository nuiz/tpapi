<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/6/14
 * Time: 11:24 AM
 */

namespace Main\Helper;


class UserHelper {
    public static function defaultSetting(){
        return [
            'show_facebook'=> true,
            'show_email'=> true,
            'show_birth_date'=> true,
            'show_gender'=> true,
            'show_website'=> true,
            'show_mobile'=> true,

            'notify_update'=> true,
            'notify_message'=> true
        ];
    }
}