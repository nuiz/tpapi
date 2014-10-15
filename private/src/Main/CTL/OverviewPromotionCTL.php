<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/11/14
 * Time: 12:00 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\NodeHelper;
use Main\Service\OverviewPromotionService;

/**
 * @Restful
 * @uri /overview/promotion
 */
class OverviewPromotionCTL extends BaseCTL {
    /**
     * @POST
     */
    public function add(){
        try {
            $item = OverviewPromotionService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
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
            $items = OverviewPromotionService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                ArrayHelper::pictureToThumb($item);

                // translate
                if($this->getCtx()->getTranslate()){
                    ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
                }

                // make node
                $item['node'] = NodeHelper::overviewPromotion($item['id']);

                $items['data'][$key] = $item;
            }
            return $items;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try {
            $item = OverviewPromotionService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());

            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            ArrayHelper::pictureToThumb($item);

            // translate
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
            }

            // make node
            $item['node'] = NodeHelper::overviewPromotion($item['id']);

            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @PUT
     * @uri /[h:id]
     */
    public function edit(){
        try {
            $item = OverviewPromotionService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            ArrayHelper::pictureToThumb($item);

            // translate
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
            }

            $item['node'] = NodeHelper::roomtype($item['id']);

            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @DELETE
     * @uri /[h:id]
     */
    public function delete(){
        try {
            OverviewPromotionService::getInstance()->delete($this->reqInfo->urlParam('id'), $this->getCtx());
            return ['success'=> true];
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]/picture
     */
    public function getPicture(){
        return OverviewPromotionService::getInstance()->getPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @POST
     * @uri /[h:id]/picture
     */
    public function postPicture(){
        return OverviewPromotionService::getInstance()->addPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @DELETE
     * @uri /[h:id]/picture
     */
    public function deletePicture(){
        return OverviewPromotionService::getInstance()->deletePictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }
} 