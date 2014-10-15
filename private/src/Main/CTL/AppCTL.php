<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 3:42 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\ApplicationService;

/**
 * @Restful
 * @uri /app
 */
class AppCTL extends BaseCTL {
    /**
     * @POST
     * @uri /request
     */
    public function request(){
        try {
            $item = ApplicationService::getInstance()->request($this->reqInfo->inputs(), $this->getCtx());
            MongoHelper::removeId($item);
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /info
     */
    public function info(){
        try {
            $item = ApplicationService::getInstance()->info($this->reqInfo->inputs(), $this->getCtx());
            MongoHelper::removeId($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}