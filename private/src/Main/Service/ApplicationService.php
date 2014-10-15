<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/24/14
 * Time: 12:34 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Valitron\Validator;

class ApplicationService extends BaseService {
    public function getCollection(){
        $db = DB::getDB();
        return $db->apps;
    }

    public function request($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['default_lang']);
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        // generate key
        $key = strtotime(date('Y-m-d 00:00:00'));
        $key = time() - $key;
        $key = sprintf('%05s', $key);

        $collection = $this->getCollection();

        $insert = ['_id'=> $key, 'default_lang'=> $params['default_lang']];
        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);

        $collection->insert($insert);

        return $insert;
    }

    public function info($params, Context $ctx){
        return $ctx->getApp();
    }
}