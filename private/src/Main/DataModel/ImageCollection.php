<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/20/14
 * Time: 4:18 PM
 */

namespace Main\DataModel;


/**
 * @property Image[] items
 */
class ImageCollection extends BaseCollection {
    public function toArrayResponse(){
        $res = [];
        foreach($this->items as $item){
            $res[] = $item->toArrayResponse();
        }
        return $res;
    }
}