<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/21/14
 * Time: 1:26 PM
 */

namespace Main\Helper;


class MongoHelper {
    public static function createSlice($page, $limit, $total = null){
        $skip = $page*$limit;
        if(is_null($total) || ($skip+$limit) < $total){
            return [-$skip, $limit];
        }

        if($skip-$limit > $total){
            return [-$skip, 0];
        }

        else if($skip > $total){
            $skip = $total;
            $limit = $total%$limit;
        }
        return [-$skip, $limit];
    }

    public static function standardId($id){
        if($id instanceof \MongoId){
            return $id->{'$id'};
        }
        return $id;
    }

    public static function standardIdEntity(&$entity){
        if(isset($entity['_id'])){
            $entity['id'] = self::standardId($entity['_id']);
            unset($entity['_id']);
        }
    }

    public static function timeToInt($time){
        if($time instanceof \MongoTimestamp){
            $time = $time->sec;
        }
        return (int)$time;
    }

    public static function timeToStr($time){
        $time = MongoHelper::timeToInt($time);
        return date("Y-m-d H:i:s", $time);
    }

    public static function removeId(&$entity){
        unset($entity['_id']);
    }

    public static function mongoId($id){
        if(!($id instanceof \MongoId)){
            $id = new \MongoId($id);
        }
        return $id;
    }

    public static function setCreatedAt(&$item){
        $now = new \MongoTimestamp();
        $item['created_at'] = $now;
    }

    public static function setUpdatedAt(&$item){
        $now = new \MongoTimestamp();
        $item['updated_at'] = $now;
    }
}