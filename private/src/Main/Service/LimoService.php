<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/25/14
 * Time: 11:23 AM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Valitron\Validator;

class LimoService extends BaseService {
    public function getCollection(){
        $db = DB::getDB();
        return $db->limo;
    }

    public function getCheckInCollection(){
        return DB::getDB()->checkin;
    }

    public function addNow($params, Context $ctx){
        $coll = $this->getCollection();
        $v = new Validator($params);
        $v->rule('required', ['lat', 'lng', 'note', 'tag_id']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $checkin = $this->getCheckInCollection()->findOne(['tag_id'=> $params['tag_id']]);
        if(is_null($checkin)){
            throw new ServiceException(ResponseHelper::validateError('Not found tag id'));
        }

        $insert = ArrayHelper::filterKey(['lat', 'lng', 'note'], $params);
        ArrayHelper::pictureToThumb($checkin['roomtype']);
        $insert['checkin'] = ArrayHelper::filterKey(['_id', 'tag_id', 'email', 'first_name', 'last_name', 'phone_number', 'roomtype'], $checkin);
//        $insert['app_id'] = $ctx->getAppId();
        MongoHelper::setCreatedAt($insert);
        $coll->insert($insert);

        return $insert;
    }

    public function addSchedule($params, Context $ctx){
        $coll = $this->getCollection();
        $v = new Validator($params);
        $v->rule('required', ['time', 'telephone', 'place_id', 'note']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $place = DB::getDB()->places->findOne(['_id'=> MongoHelper::mongoId($params['place_id'])], ['location', 'translate']);
        if(is_null($place)){
            throw new ServiceException(ResponseHelper::notFound('Not found place'));
        }

        $insert = ArrayHelper::filterKey(['time', 'telephone', 'note'], $params);
        $insert['place'] = $place;
//        $insert['app_id'] = $ctx->getAppId();
        MongoHelper::setCreatedAt($insert);
        $coll->insert($insert);

        return $insert;
    }
}