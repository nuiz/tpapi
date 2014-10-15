<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/23/14
 * Time: 2:01 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;

class UserSettingService extends BaseService {
    protected $fields = [
        'show_facebook',
        'show_email',
        'show_birth_date',
        'show_gender',
        'show_website',
        'show_mobile',

        'notify_update',
        'notify_message'
    ];

    public function getCollection(){
        $this->db = DB::getDB();
        return $this->db->users;
    }

    public function get($id, Context $ctx){
        $id = MongoHelper::mongoId($id);
        $entity = $this->getCollection()->findOne(['_id'=> $id], ['setting']);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }
        $setting = $this->_getSetting($entity);

        return $setting;
    }

    public function edit($id, $params, Context $ctx){
        $id = MongoHelper::mongoId($id);
        $entity = $this->getCollection()->findOne(['_id'=> $id], ['setting']);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }
        $this->_getSetting($entity);

        $setting = ArrayHelper::filterKey($this->fields, $params);

        if(count($setting) > 0){
            foreach($setting as $key=> $value){
                $setting[$key] = (bool)$value;
            }
            $set = ['setting'=> $setting];
            $set = ArrayHelper::ArrayGetPath($set);
            $this->getCollection()->update(['_id'=> $entity['_id']], ['$set'=> $set]);
        }

        return $this->get($id, $ctx);
    }

    public function _getSetting($entity){
        if(isset($entity['setting'])){
            return $entity['setting'];
        }

        $setting = [
            'show_facebook'=> true,
            'show_email'=> true,
            'show_birth_date'=> true,
            'show_gender'=> true,
            'show_website'=> true,
            'show_mobile'=> true,

            'notify_update'=> true,
            'notify_message'=> true
        ];
        $set = ['setting'=> $setting];
        $set = ArrayHelper::ArrayGetPath($set);
        $this->getCollection()->update(['_id'=> $entity['_id']], ['$set'=> $set]);

        return $setting;
    }
}