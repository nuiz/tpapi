<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/11/14
 * Time: 4:47 PM
 */

namespace Main\Service;


use Main\DB;

class FeedService extends BaseService {
    public function getCollection(){
        return DB::getDB()->feed;
    }
}