<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/15/14
 * Time: 11:27 AM
 */

namespace Main\CTL;


use Main\Context\Context;
use Main\Http\RequestInfo;

class BaseCTL {
    /**
     * @var Context $ctx;
     */
    public $reqInfo, $ctx;
    public function __construct(RequestInfo $reqInfo){
        $this->reqInfo = $reqInfo;
        $this->ctx = new Context();

        $token = isset($_SERVER['HTTP_X_AUTH_TOKEN'])? $_SERVER['HTTP_X_AUTH_TOKEN']: $reqInfo->input('access_token', null);
        $appKey = $reqInfo->input('app_key', null);
        $this->ctx->loadAppKey($appKey);
        $this->ctx->loadAccessToken($token);
        $lang = $reqInfo->input('lang', $this->ctx->getApp()['default_lang']);
        $this->ctx->loadLang($lang);
        $this->ctx->loadTranslate($reqInfo->input('translate_output', 'yes'));
    }

    public function getCtx(){
        return $this->ctx;
    }
}