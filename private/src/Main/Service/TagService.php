<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/7/14
 * Time: 11:39 AM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ResponseHelper;
use Valitron\Validator;

class TagService extends BaseService {
    public function getUserCollection(){
        return DB::getDB()->users;
    }

    public function getCheckInCollection(){
        return DB::getDB()->checkin;
    }

    public function check($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['tag_id']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

//        $user = $ctx->getUser();
//        if(is_null($user)){
//            throw new ServiceException(ResponseHelper::notAuthorize());
//        }

        $checkin = $this->getCheckInCollection()->findOne(['tag_id'=> $params['tag_id']]);
        if(is_null($checkin)){
            throw new ServiceException(ResponseHelper::validateError('Not found tag id'));
        }

        return $checkin;
    }
}