<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/24/14
 * Time: 5:37 PM
 */

namespace Main\Helper;


use Main\DataModel\Image;

class ArrayHelper {
    public static function ArrayGetPath($arr, $oldKey = null){
        $data = array();
        foreach($arr as $key => $value){
            $newKey = $key;
            if(!is_null($oldKey)){
                $newKey = $oldKey.".".$newKey;
            }

            if(is_array($value)){
                $data = array_merge($data, self::ArrayGetPath($value, $newKey));
            }
            else {
                $data[$newKey] = $value;
            }
        }
        return $data;
    }

    public static function filterKey($keys, $params){
//        $allowed = array("phone", "website", "email", "info", "location");
        return array_intersect_key($params, array_flip($keys));
    }

    public static function translateEntity(&$entity, $lang){
        $translateAll = $entity['translate'];
//        $translate = isset($translateAll[$lang])? $translateAll[$lang]:
//            (isset($translateAll[$defaultLang])? $translateAll[$defaultLang]: array_shift($translateAll));
        $translate = isset($translateAll[$lang])? $translateAll[$lang]: array_shift($translateAll);

        $entity = array_merge($entity, $translate);
        unset($entity['translate']);
    }

    public static function pictureToThumb(&$entity){
        $entity['thumb'] = Image::load($entity['pictures'][0])->toArrayResponse();
        unset($entity['pictures']);
    }
}