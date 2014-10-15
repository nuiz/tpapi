<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/7/14
 * Time: 4:24 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Service\TagService;

/**
 * @Restful
 * @uri /tag
 */
class TagCTL extends BaseCTL {
    /**
     * @POST
     * @uri /check
     */
    public function check(){
        try {
            $item = TagService::getInstance()->check($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['check_in'] = MongoHelper::timeToInt($item['check_in']);
            $item['check_out'] = MongoHelper::timeToInt($item['check_out']);
            MongoHelper::standardIdEntity($item['roomtype']);
            $item['roomtype']['created_at'] = MongoHelper::timeToInt($item['roomtype']['created_at']);
            $item['roomtype']['updated_at'] = MongoHelper::timeToInt($item['roomtype']['updated_at']);
            ArrayHelper::pictureToThumb($item['roomtype']);

            // translate roomtype
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item['roomtype'], $this->getCtx()->getLang());
            }
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
} 