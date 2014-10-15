<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/22/14
 * Time: 3:09 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\UserService;


/**
 * @Restful
 * @uri /me
 */
class MeCTL extends BaseCTL {
    /**
     * @GET
     */
    public function me(){
        try{
            $item = UserService::getInstance()->me($this->reqInfo->inputs(), $this->getCtx());
            MongoHelper::standardId($item);
            if(isset($item['birth_date'])){
                $item['birth_date'] = MongoHelper::timeToInt($item['birth_date']);
            }
            if(isset($item['password'])){
                unset($item['password']);
            }
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}