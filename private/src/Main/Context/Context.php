<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/17/14
 * Time: 3:06 PM
 */

namespace Main\Context;


use Main\DB;
use Main\Helper\GenerateHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;

class Context {
    protected $accessToken, $user, $app, $lang, $translate;

    public function __construct(){
    }

    public function loadAppKey($appKey){
        $db = DB::getDB();

        if(is_null($appKey)){
            $app = $db->apps->findOne(['default'=> true]);
            if(is_null($app)){
                $app = [
                    '_id'=> '00000',
                    'default'=> true,
                    'default_lang'=> 'en'
                ];
                MongoHelper::setCreatedAt($app);
                MongoHelper::setUpdatedAt($app);

                $db->apps->insert($app);
            }
            $this->app = $app;
        }
        else {
            $this->app = $db->apps->findOne(['_id'=> $appKey]);
            if(is_null($this->app)){
                header("Content-Type: application/json");
                echo json_encode(ResponseHelper::notFound("Not found application key"));
                exit();
            }
        }
    }

    public function loadAccessToken($accessToken){
        $db = DB::getDB();
        $this->user = $db->users->findOne(['access_token'=> $accessToken]);
    }

    public function loadLang($lang){
        $this->lang = $lang;
    }

    public function loadTranslate($translate){
        $this->translate = $translate=="no"? false: true;
    }

    public function getApp(){
        return $this->app;
    }

    public function getDefaultLang(){
        return $this->app['default_lang'];
    }

    public function getUser(){
        return $this->user;
    }

    public function getLang()
    {
        return is_null($this->lang)? $this->getDefaultLang(): $this->lang;
    }

    public function getTranslate()
    {
        return $this->translate;
    }

    public function getAppId(){
        return $this->getApp()['_id'];
    }
}