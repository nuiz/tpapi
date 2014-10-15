<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/21/14
 * Time: 3:46 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\UserHelper;
use Valitron\Validator;

class UserService extends BaseService {
    protected $fields = ["type", "display_name", "username", "email", "password", "gender", "birth_date", "picture", "mobile", "website", "fb_id", "fb_name"];

    public function getCollection(){
        $db = DB::getDB();
        return $db->users;
    }

    public function add($params, Context $ctx){
        $allow = ["username", "email", "password", "gender", "birth_date"];
        $entity = ArrayHelper::filterKey($allow, $params);

//        Add rule
//        Validator::addRule('ruleName', function($field, $value, $params = []){
//            if(true)
//                return true;
//            return false;
//        });

        $v = new Validator($entity);
        $v->rule('required', ["username", "email", "password", "gender", "birth_date"]);
//        $v->rule('required', ["username", "email", "password", "gender"]);
        $v->rule('email', ["email"]);
        $v->rule('lengthBetween', 'username', 4, 32);
        $v->rule('lengthBetween', 'password', 4, 32);
        $v->rule('in', 'gender', ['male', 'female']);
//        $v->rule('date', 'birth_date');

        if(!$v->validate()) {
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        if($this->getCollection()->count(['username'=> $entity['username']]) != 0){
            throw new ServiceException(ResponseHelper::validateError(['username'=> ['Duplicate username']]));
        }

        $entity['password'] = md5($entity['password']);
        $entity['display_name'] = $entity['username'];
        $entity['birth_date'] = new \MongoTimestamp(strtotime($entity['birth_date']));

        // set website,mobile to ''
        $entity['website'] = '';
        $entity['mobile'] = '';

        $entity['fb_id'] = '';
        $entity['fb_name'] = '';

        // set default setting
        $entity['setting'] = UserHelper::defaultSetting();

        // register time
        $entity['created_at'] = new \MongoTimestamp();

        $this->getCollection()->insert($entity);

        //add stat helper
//        StatHelper::add('register', time(), 1);

//        MongoHelper::standardIdEntity($entity);
        unset($entity['password']);

        return $entity;
    }

    public function edit($id, $params, Context $ctx){
        $allow = ["email", "gender", "birth_date", "website", "mobile", "display_name"];
        $set = ArrayHelper::filterKey($allow, $params);
        $v = new Validator($set);
        $v->rule('email', 'email');
        $v->rule('in', 'gender', ['male', 'female']);
//        $v->rule('date', 'birth_date');
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }
        if(isset($params['picture'])){
            $img = Image::upload($params['picture']);
            $set['picture'] = $img->toArray();
        }
        $set = ArrayHelper::ArrayGetPath($set);

        if(isset($set['birth_date'])){
            $set['birth_date'] = new \MongoTimestamp($set['birth_date']);
        }

        if(count($set)>0){
            $id = MongoHelper::mongoId($id);
            $this->getCollection()->update(['_id'=> $id], ['$set'=> $set]);
        }

        return $this->get($id, $ctx);
    }

    public function changePassword($id, $params, Context $ctx){
        $id = MongoHelper::mongoId($id);

        $v = new Validator($params);
        $v->rule('required', ['new_password', 'old_password']);
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $entity = $this->getCollection()->findOne(['_id'=> $id], ['password']);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        if((md5($params['old_password']) != $entity['password']) && isset($entity['password'])){
            throw new ServiceException(ResponseHelper::validateError(['old_password'=> ['Password not match']]));
        }

        $set = ['password'=> md5($params['new_password'])];
        $this->getCollection()->update(['_id'=> $id], ['$set'=> $set]);

        return ['success'=> true];
    }

    public function get($id, Context $ctx){
        $id = MongoHelper::mongoId($id);

        $fields = $this->fields;
        unset($fields['password']);

        $entity = $this->getCollection()->findOne(['_id'=> $id], $fields);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        MongoHelper::standardIdEntity($entity);
//        $entity['birth_date'] = date('Y-m-d H:i:s', MongoHelper::timeToInt($entity['birth_date']));

        if(isset($entity['picture'])){
            $entity['picture'] = Image::load($entity['picture'])->toArrayResponse();
        }
        else {
            $entity['picture'] = Image::load([
                'id'=> '54297c9390cc13a5048b4567png',
                'width'=> 200,
                'height'=> 200
            ])->toArrayResponse();
        }
        return $entity;
    }

    public function me($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', 'access_token');
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $tokenEntity = $this->getCollection()->findOne(['access_token'=> $params['access_token']]);
        if(is_null($tokenEntity)){
            throw new ServiceException(ResponseHelper::notAuthorize());
        }

        return $this->get($tokenEntity['_id'], $ctx);
    }
}