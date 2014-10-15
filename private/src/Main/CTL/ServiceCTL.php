<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/9/14
 * Time: 12:05 PM
 */

namespace Main\CTL;
use Main\DataModel\Image;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\NodeHelper;
use Main\Helper\URL;
use Main\Service\ServiceService;


/**
 * @Restful
 * @uri /service
 */
class ServiceCTL extends BaseCTL {
    /**
     * @POST
     * @uri /folder
     */
    public function addFolder(){
        try {
            $item = ServiceService::getInstance()->addFolder($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
            }
            unset($item['parent_id']);
            $item['node'] = NodeHelper::serviceFolder($item['_id']);
            return $item;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @POST
     * @uri /item
     */
    public function addItem(){
        try {
            $item = ServiceService::getInstance()->addItem($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
            }
            ArrayHelper::pictureToThumb($item);
            unset($item['parent_id']);
            $item['node'] = NodeHelper::serviceItem($item['_id']);
            return $item;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     */
    public function gets(){
        try {
            $items = ServiceService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                if($item['type']=='folder'){
                    $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
                    $item['node'] = NodeHelper::serviceFolder($item['_id']);
                }
                else if($item['type']=='item'){
                    ArrayHelper::pictureToThumb($item);
                    $item['node'] = NodeHelper::serviceItem($item['_id']);
                }
                if($this->getCtx()->getTranslate()){
                    ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
                }
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                MongoHelper::standardIdEntity($item);
                unset($item['parent_id']);
                $items['data'][$key] = $item;
            }
            return $items;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]/children
     */
    public function getsByParent(){
        try {
            $params = array_merge($this->reqInfo->params(), ['parent_id'=> $this->reqInfo->urlParam('id')]);
            $items = ServiceService::getInstance()->gets($params, $this->getCtx());
            foreach($items['data'] as $key=> $item){
                if($item['type']=='folder'){
                    $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
                    $item['node'] = NodeHelper::serviceFolder($item['_id']);
                }
                else if($item['type']=='item'){
                    ArrayHelper::pictureToThumb($item);
                    $item['node'] = NodeHelper::serviceItem($item['_id']);
                }
                if($this->getCtx()->getTranslate()){
                    ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
                }
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                MongoHelper::standardIdEntity($item);
                unset($item['parent_id']);
                $items['data'][$key] = $item;
            }
            if($items['paging']['length'] > $items['paging']['current']){
                $nextQueryString = http_build_query(['page'=> $items['paging']['current']+1, 'limit'=> $items['limit']]);
                $items['paging']['next'] = URL::absolute('/service/'.$params['parent_id'].'/children?'.$nextQueryString);
            }
            return $items;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }
    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try {
            $item = ServiceService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
            if($item['type']=='folder'){
                $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
                $item['node'] = NodeHelper::serviceFolder($item['_id']);
            }
            else if($item['type']=='item'){
                ArrayHelper::pictureToThumb($item);
                $item['node'] = NodeHelper::serviceItem($item['_id']);
            }
            if($this->getCtx()->getTranslate()){
                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
            }
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            MongoHelper::standardIdEntity($item);
            unset($item['parent_id']);

            return $item;
        }
        catch (ServiceException $ex) {
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]/picture
     */
    public function getPicture(){
        return ServiceService::getInstance()->getPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @POST
     * @uri /[h:id]/picture
     */
    public function postPicture(){
        return ServiceService::getInstance()->addPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @DELETE
     * @uri /[h:id]/picture
     */
    public function deletePicture(){
        return ServiceService::getInstance()->deletePictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }
}