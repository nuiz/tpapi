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
use Main\Helper\NodeHelper;
use Main\Service\LimoService;
use Main\Service\RoomTypeService;

/**
 * @Restful
 * @uri /roomtype
 */
class RoomTypeCTL extends BaseCTL {
    /**
     * @POST
     */
    public function add(){
        try {
            $item = RoomTypeService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
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
     * @uri /[h:id]
     */
    public function get(){
        try {
            $item = RoomTypeService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
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
     * @PUT
     * @uri /[h:id]
     */
    public function edit(){
        try {
            $item = RoomTypeService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
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
            RoomTypeService::getInstance()->delete($this->reqInfo->urlParam('id'), $this->getCtx());
            return ['success'=> true];
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
            $items = RoomTypeService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                ArrayHelper::pictureToThumb($item);

                // translate
                if($this->getCtx()->getTranslate()){
                    ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
                }

                $item['node'] = NodeHelper::roomtype($item['id']);

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
     * @uri /[h:id]/picture
     */
    public function getPicture(){
        return RoomTypeService::getInstance()->getPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @POST
     * @uri /[h:id]/picture
     */
    public function postPicture(){
        return RoomTypeService::getInstance()->addPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @DELETE
     * @uri /[h:id]/picture
     */
    public function deletePicture(){
        return RoomTypeService::getInstance()->deletePictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }
}