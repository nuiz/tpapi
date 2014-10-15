<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/2/14
 * Time: 12:20 PM
 */

namespace Main\Helper;

class NodeHelper {

    public static function news($id){
        $id = MongoHelper::standardId($id);
        return [
            'share'=> URL::share('/news.php?id='.$id)
        ];
    }

    public static function roomtype($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/roomtype/'.$id.'/picture'),
            'share'=> URL::share('/roomtype/'.$id)
        ];
    }

    public static function place($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/place/'.$id.'/picture')
        ];
    }

    public static function overviewPromotion($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/overview/promotion/'.$id.'/picture')
        ];
    }

    public static function serviceItem($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/service/'.$id.'/picture'),
        ];
    }

    public static function serviceFolder($id){
        $id = MongoHelper::standardId($id);
        return [
            'children'=> URL::absolute('/service/'.$id.'/children')
        ];
    }
}