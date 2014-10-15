<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/27/14
 * Time: 2:42 PM
 */

namespace Main\Service;

use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphUser;
use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\UserHelper;
use Valitron\Validator;

class OAuthService extends BaseService {
    public function getUsersCollection(){
        $db = DB::getDB();
        return $db->users;
    }

    public function facebook($params, Context $ctx){
        FacebookSession::setDefaultApplication('294514757403051','79233e7296a295dd6e6508385edcd4fc');

        $v = new Validator($params);
        $v->rule('required', ['facebook_token']);
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $session = new FacebookSession($params['facebook_token']);
        try {
            /**
             * @var GraphUser $me;
             */
            $me = (new FacebookRequest(
                $session, 'GET', '/me'
            ))->execute()->getGraphObject(GraphUser::className());
            $me->getName();
            $fbId = $me->getId();
            $item = $this->getUsersCollection()->findOne(['fb_id'=> $fbId], ['access_token']);

            if(is_null($item)){
                $now = new \MongoTimestamp();
                $birth_date = new \MongoTimestamp($me->getBirthday()->getTimestamp());
                $item = [
                    '_id'=> new \MongoId(),
                    'fb_id'=> $fbId,
                    'fb_name'=> $me->getName(),
                    'display_name'=> $me->getName(),
                    'email'=> $me->getProperty('email'),
                    'username'=> $me->getId(),
                    'gender'=> $me->getProperty('gender'),
                    'birth_date'=> $birth_date,
                    'website'=> '',
                    'mobile'=> '',
                    'created_at'=> $now,
                    'updated_at'=> $now,
                    'setting'=> UserHelper::defaultSetting()
                ];
                $item['access_token'] = $this->generateToken(MongoHelper::standardId($item['_id']));
//                $item['app_id'] = $ctx->getAppId();

                // get picture from facebook
                $pictureSource = file_get_contents('http://graph.facebook.com/'.$fbId.'/picture');
                $pic = Image::upload(base64_encode($pictureSource));
                $item['picture'] = $pic->toArray();

                $this->getUsersCollection()->insert($item);
                $this->getUsersCollection()->ensureIndex(['access_token'=> 1, 'app_id'=> 1]);
            }
            else if(!isset($item['access_token'])){
                $item['access_token'] = $this->generateToken(MongoHelper::standardId($item['_id']));
                $this->getUsersCollection()->update(['_id'=> $item['_id']], ['$set'=> ['access_token'=> $item['access_token']]]);
                $this->getUsersCollection()->ensureIndex(['access_token'=> 1, 'app_id'=> 1]);
            }

            // remember device token
            if(isset($params['ios_device_token'])){
                $this->getUsersCollection()->update(['_id'=> $item['_id']], ['$addToSet'=> ['ios_device_token'=> $params['ios_device_token'] ]]);
            }
            if(isset($params['android_token'])){
                $this->getUsersCollection()->update(['_id'=> $item['_id']], ['$addToSet'=> ['android_token'=> $params['android_token'] ]]);
            }

            return ['user_id'=> MongoHelper::standardId($item['_id']), 'access_token'=> $item['access_token']];

        } catch (FacebookRequestException $e) {
            throw new ServiceException(ResponseHelper::error($e->getMessage()));
        } catch (\Exception $e) {
            throw new ServiceException(ResponseHelper::error($e->getMessage()));
        }
    }

    public function password($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['username', 'password']);
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $item = $this->getUsersCollection()->findOne(['username'=> $params['username']]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound('Not found user'));
        }
        if(!isset($item['password']) || $item['password']!=md5($params['password'])){
            throw new ServiceException(ResponseHelper::error('Wrong password'));
        }
        if(!isset($item['access_token'])){
            $item['access_token'] = $this->generateToken(MongoHelper::standardId($item['_id']));
            $this->getUsersCollection()->update(['_id'=> $item['_id']], ['$set'=> ['access_token'=> $item['access_token']]]);
            $this->getUsersCollection()->ensureIndex(['access_token'=> 1, 'app_id'=> 1]);
        }

        // remember device token
        if(isset($params['ios_device_token'])){
            $this->getUsersCollection()->update(['_id'=> $item['_id']], ['$addToSet'=> ['ios_device_token'=> $params['ios_device_token'] ]]);
        }
        if(isset($params['android_token'])){
            $this->getUsersCollection()->update(['_id'=> $item['_id']], ['$addToSet'=> ['android_token'=> $params['android_token'] ]]);
        }

        return ['user_id'=> MongoHelper::standardId($item['_id']), 'access_token'=> $item['access_token']];
    }

    public function generateToken($id){
        return md5(uniqid($id, true));
    }

    public function logout($params, Context $ctx){
        $user = $ctx->getUser();
        if(is_null($user)){
            return ResponseHelper::notAuthorize();
        }
        if(isset($params['ios_device_token'])){
            $this->getUsersCollection()->update(['_id'=> $user['_id']], ['$pull'=> ['ios_device_token'=> $params['ios_device_token']]]);
        }
        if(isset($params['android_token'])){
            $this->getUsersCollection()->update(['_id'=> $user['_id']], ['$pull'=> ['android_token'=> $params['android_token']]]);
        }

        return array('success'=> true);
    }
}