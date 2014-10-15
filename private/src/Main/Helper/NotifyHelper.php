<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/14/14
 * Time: 5:05 PM
 */

namespace Main\Helper;


use Main\DB;

class NotifyHelper {
    protected static $apnHelper = null;

    protected static function getApnHelper(){
        if(is_null(self::$apnHelper)){
            self::$apnHelper = new APNHelper(file_get_contents('private/apple/dev.pem'), 'gateway.sandbox.push.apple.com', 2195);
        }
        return self::$apnHelper;
    }

    public static function cutMessage($message){
        $pushMessage = $message;
        if(is_array($pushMessage)){
            $pushMessage = $message;
        }

        if(strlen(utf8_encode($pushMessage)) > 120){
            $pushMessage = mb_substr($pushMessage, 0, 18, 'utf-8').'...';
        }

        return $pushMessage;
    }

    public static function sendAll($objectId, $type, $header, $message){
        $db = DB::getDB();
        $users = $db->users->find([], ['setting', 'ios_device_token', 'android_token']);
        foreach($users as $item){
            $userId = MongoHelper::mongoId($item['_id']);
            $entity = self::create($objectId, $type, $header, $message, $userId);

            $entity['object']['id'] = MongoHelper::standardId($objectId);
            $entity['id'] = MongoHelper::standardId($entity['_id']);

//            $objectUrl = "";
//            if($type == 'news'){
//                $objectUrl = URL::absolute('/news/'.$entity['object']['id']);
//            }
//            else if($type == 'activity'){
//                $objectUrl = URL::absolute('/activity/'.$entity['object']['id']);
//            }
//            else if($type == 'message'){
//                $objectUrl = URL::absolute('/message/'.$entity['object']['id']);
//            }

            $pushMessage = self::cutMessage($message);

            $args = array(
                'id'=> $entity['id'],
                'object_id'=> $entity['object']['id'],
                'type'=> $type
            );

            if(!isset($item['setting']))
                continue;

            if(!$item['setting']['notify_update'] && $type != "message")
                continue;

            if(!$item['setting']['notify_message'] && $type == "message")
                continue;

            $res = null;
            if(isset($item['ios_device_token'])){
                foreach($item['ios_device_token'] as $token){
                    try {
                        self::getApnHelper()->send($token, $pushMessage, $args);
                    }
                    catch (\Exception $e){
                        error_log($e->getCode()." ".$e->getMessage()." *FILE:".$e->getFile()." ".$e->getLine());
                    }
                }
            }
            if(isset($item['android_token'])){
                if(count($item['android_token']) > 0){
                    $tokens = [];
                    foreach($item['android_token'] as $token){
                        $tokens[] = $token;
                    }

                    try {
                        GCMHerlper::send($tokens, [
                            'message'=> $pushMessage,
                            'object'=> $args
                        ]);
                    }
                    catch(\Exception $e){
                        error_log($e->getMessage());
                    }
                }
            }
        }
    }

    public static function create($objectId, $type, $header, $message, $userId){
        $db = DB::getDB();
        $objectId = MongoHelper::mongoId($objectId);
        $userId = MongoHelper::mongoId($userId);

        $now = new \MongoTimestamp();
        $entity = array(
            'preview_header'=> $header,
            'preview_content'=> $message,
            'object'=> array(
                'type'=> $type,
                'id'=> $objectId
            ),
            'user_id'=> $userId,
            'opened'=> false,
            'created_at'=> $now
        );

        $db->notify->insert($entity);
        return $entity;
    }
}