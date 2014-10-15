<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/8/14
 * Time: 12:19 PM
 */

namespace Main\CTL;
use Main\DB;


/**
 * @Restful
 * @uri /country
 */
class CountryCTL {
//    public function dump(){
//        $json = json_decode(file_get_contents("http://restcountries.eu/rest/v1"), true);
//        foreach($json as $key=> $item){
//            DB::getDB()->country->insert(['_id'=> $key+1, 'name'=> $item['name'], 'demonym'=> $item['demonym']]);
//        }
//
//        return $json;
//    }

    /**
     * @GET
     */
    public function gets(){
        $data = [];
        $cursor = DB::getDB()->country->find();
        foreach($cursor as $key=> $item){
            $item['id'] = $item['_id'];
            unset($item['_id']);
            $data[] = $item;
        }

        return $data;
    }
}