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
use Main\Helper\URL;
use Valitron\Validator;

class ReservationService extends BaseService {
    public function getCollection(){
        $db = DB::getDB();
        return $db->reservation;
    }

    public function add($params, Context $ctx){
        $coll = $this->getCollection();
        $v = new Validator($params);
        $key = ['first_name', 'last_name', 'nationality', 'email', 'phone_number', 'adults', 'child', 'check_in', 'check_out', 'roomtype_id', 'note'];
        $v->rule('required', $key);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $roomtype = DB::getDB()->roomtypes->findOne(['_id'=> MongoHelper::mongoId($params['roomtype_id'])]);
        if(is_null($roomtype)){
            throw new ServiceException(ResponseHelper::notFound('Not found roomtype'));
        }

        $insert = ArrayHelper::filterKey($key, $params);
        unset($insert['roomtype_id']);
        $insert['roomtype'] = $roomtype;
        $insert['check_in'] = new \MongoTimestamp($params['check_in']);
        $insert['check_out'] = new \MongoTimestamp($params['check_out']);
//        $insert['app_id'] = $ctx->getAppId();
        MongoHelper::setCreatedAt($insert);
        $coll->insert($insert);

        return $insert;
    }

    public function gets($params, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15,
        );
        $options = array_merge($default, $params);

        $skip = ($options['page']-1)*$options['limit'];
        $condition = [];

        $cursor = $this->getCollection()
            ->find($condition)
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['created_at'=> -1]);

        $data = [];

        foreach($cursor as $item){
            $data[] = $item;
        }

        $total = $this->getCollection()->count($condition);
        $length = $cursor->count(true);

        $res = [
            'length'=> $length,
            'total'=> $total,
            'data'=> $data,
            'paging'=> [
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            ]
        ];

        $pagingLength = $total/(int)$options['limit'];
        $pagingLength = floor($pagingLength)==$pagingLength? floor($pagingLength): floor($pagingLength) + 1;
        $res['paging']['length'] = $pagingLength;
        $res['paging']['current'] = (int)$options['page'];
        if(((int)$options['page'] * (int)$options['limit']) < $total){
            $nextQueryString = http_build_query(['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $res['paging']['next'] = URL::absolute('/feed'.'?'.$nextQueryString);
        }

        return $res;
    }
}