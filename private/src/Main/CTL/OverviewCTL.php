<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 3:42 PM
 */

namespace Main\CTL;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Service\OverviewService;

/**
 * @Restful
 * @uri /overview
 */
class OverviewCTL extends BaseCTL {
    /**
     * @GET
     */
    public function get(){
        $item = OverviewService::getInstance()->get($this->getCtx());
        MongoHelper::removeId($item);

        if($this->getCtx()->getTranslate()){
            ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
        }
        return $item;
    }

    /**
     * @PUT
     */
    public function edit(){
        $item = OverviewService::getInstance()->edit($this->reqInfo->inputs(), $this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @GET
     * @uri /picture
     */
    public function getPicture(){
        return OverviewService::getInstance()->getPictures($this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @POST
     * @uri /picture
     */
    public function postPicture(){
        return OverviewService::getInstance()->addPictures($this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @DELETE
     * @uri /picture
     */
    public function deletePicture(){
        return OverviewService::getInstance()->deletePictures($this->reqInfo->params(), $this->getCtx());
    }
}