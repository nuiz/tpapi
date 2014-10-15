<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/30/14
 * Time: 11:54 AM
 */

namespace Main\Helper;


class ResponseHelper {
    public static function notFound($message = "Object not found"){
        return [
            'error'=> [
                'code'=> 404,
                'message'=> $message,
                'type'=> 'NotFound'
            ]
        ];
    }

    public static function error($message, $code = 500, $type = 'Error'){
        return [
            'error'=> [
                'code'=> $code,
                'message'=> $message,
                'type'=> $type
            ]
        ];
    }

    public static function validateError($invalid, $code = 500, $type = 'Error'){
        return [
            'error'=> [
                'code'=> $code,
                'message'=> 'Invalid parameter',
                'type'=> $type,
                'fields'=> $invalid
            ]
        ];
    }

    public static function notAuthorize($message = 'Not authorized'){
        return [
            'error'=> [
                'code'=> 401,
                'message'=> $message,
                'type'=> 'NotAuthorized'
            ]
        ];
    }

    public static function requireAuthorize($message = 'Require authorized'){
        return [
            'error'=> [
                'code'=> 402,
                'message'=> $message,
                'type'=> 'RequireAuthorized'
            ]
        ];
    }
}