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
use Main\Service\ReservationService;

/**
 * @Restful
 * @uri /reservation
 */
class ReservationCTL extends BaseCTL {
    /**
     * @POST
     */
    public function add(){
        try {
            $item = ReservationService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['check_in'] = MongoHelper::timeToInt($item['check_in']);
            $item['check_out'] = MongoHelper::timeToInt($item['check_out']);
            MongoHelper::standardIdEntity($item['roomtype']);
            $item['roomtype']['created_at'] = MongoHelper::timeToInt($item['roomtype']['created_at']);
            $item['roomtype']['updated_at'] = MongoHelper::timeToInt($item['roomtype']['updated_at']);
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     */
    public function gets(){
        try {
            $items = ReservationService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['check_in'] = MongoHelper::timeToInt($item['check_in']);
                $item['check_out'] = MongoHelper::timeToInt($item['check_out']);
                MongoHelper::standardIdEntity($item['roomtype']);
                $item['roomtype']['created_at'] = MongoHelper::timeToInt($item['roomtype']['created_at']);
                $item['roomtype']['updated_at'] = MongoHelper::timeToInt($item['roomtype']['updated_at']);

                // translate roomtype
                if($this->getCtx()->getTranslate()){
                    ArrayHelper::translateEntity($item['roomtype'], $this->getCtx()->getLang());
                }

                $items['data'][$key] = $item;
            }
            return $items;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}