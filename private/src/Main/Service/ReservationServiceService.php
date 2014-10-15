<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/14/14
 * Time: 2:40 PM
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

class ReservationServiceService extends BaseService {
    public function getServiceCollection(){
        return DB::getDB()->services;
    }

    public function getReservationServiceCollection(){
        return DB::getDB()->reservation_services;
    }

    public function add($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['service_id', 'name', 'email', 'time', 'people', 'telephone', 'note']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $service = $this->getServiceCollection()->findOne(['_id'=> MongoHelper::mongoId($params['service_id']), 'type'=> 'item', 'price'=> ['$exists'=> false]]);
        if(is_null($service)){
            throw new ServiceException(ResponseHelper::notFound('Not found service for reservation'));
        }

        ArrayHelper::pictureToThumb($service);
        $insert = ArrayHelper::filterKey(['name', 'email', 'time', 'people', 'telephone', 'note'], $params);
        $insert['service'] = [
            '_id'=> $service['_id'],
            'translate'=> $service['translate'],
            'thumb'=> $service['thumb']
        ];
//        $insert['app_id'] = $ctx->getAppId();
        MongoHelper::setCreatedAt($insert);
        $this->getReservationServiceCollection()->insert($insert);

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

        $cursor = $this->getReservationServiceCollection()
            ->find($condition)
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['created_at'=> -1]);

        $data = [];

        foreach($cursor as $item){
            $data[] = $item;
        }

        $total = $this->getReservationServiceCollection()->count($condition);
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
            $res['paging']['next'] = URL::absolute('/service/reservation'.'?'.$nextQueryString);
        }

        return $res;
    }
} 