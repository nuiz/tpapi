<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 3:42 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Service\LimoService;

/**
 * @Restful
 * @uri /limo
 */
class LimoCTL extends BaseCTL {
    /**
     * @POST
     * @uri /now
     */
    public function addNow(){
        try {
            $item = LimoService::getInstance()->addNow($this->reqInfo->params(), $this->getCtx());

            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            MongoHelper::standardIdEntity($item['checkin']);
            $item['checkin']['created_at'] = MongoHelper::timeToInt($item['checkin']['created_at']);
            $item['checkin']['roomtype']['created_at'] = MongoHelper::timeToInt($item['checkin']['roomtype']['created_at']);
            $item['checkin']['roomtype']['updated_at'] = MongoHelper::timeToInt($item['checkin']['roomtype']['updated_at']);

            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item['checkin']['roomtype'], $this->getCtx()->getLang());
            }
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @POST
     * @uri /schedule
     */
    public function addSchedule(){
        try {
            $item = LimoService::getInstance()->addSchedule($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            MongoHelper::standardIdEntity($item['place']);
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}