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
use Main\Service\ReservationServiceService;

/**
 * @Restful
 * @uri /reservation/service
 */
class ReservationServiceCTL extends BaseCTL {
    /**
     * @POST
     */
    public function add(){
        try {
            $item = ReservationServiceService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            MongoHelper::standardIdEntity($item['service']);
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item['service'], $this->getCtx()->getLang());
            }
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
            $items = ReservationServiceService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                MongoHelper::standardIdEntity($item['service']);
                if($this->getCtx()->getTranslate()){
                    ArrayHelper::translateEntity($item['service'], $this->getCtx()->getLang());
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