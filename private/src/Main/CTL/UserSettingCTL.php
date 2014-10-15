<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/23/14
 * Time: 2:16 PM
 */

namespace Main\CTL;


use Main\Exception\Service\ServiceException;
use Main\Service\UserSettingService;

/**
 * @Restful
 * @uri /user/setting
 */
class UserSettingCTL extends BaseCTL {
    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try{
            $item = UserSettingService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
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
        try{
            $item = UserSettingService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}