<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/7/14
 * Time: 4:59 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\URL;
use Valitron\Validator;

class AdminService extends BaseService {
    public function getUserCollection(){
        return DB::getDB()->users;
    }

    public function add($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['email', 'username', 'password', 'picture', 'type']);
        $v->rule('in', ['type'], ['admin', 'reception']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        if($this->getUserCollection()->count(['username'=> $params['username']]) > 0){
            throw new ServiceException(ResponseHelper::error('Duplicate username'));
        }

        $insert = ArrayHelper::filterKey(['email', 'username', 'password', 'picture', 'type'], $params);
        $insert['picture'] = Image::upload($insert['picture'])->toArray();
        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);

        $this->getUserCollection()->insert($insert);

        return $insert;
    }

    public function edit($id, $params, Context $ctx){
        $set = ArrayHelper::filterKey(['email', 'username', 'password', 'picture'], $params);
        if(isset($set['picture'])){
            $set['picture'] = Image::upload($set['picture'])->toArray();
        }

        // set update time
        MongoHelper::setUpdatedAt($set);

        $this->getUserCollection()->insert(['_id'=> MongoHelper::mongoId($id)], ['$set'=> ArrayHelper::ArrayGetPath($set)]);

        return $this->get($id, $ctx);
    }

    public function get($id, Context $ctx){
        $item = $this->getUserCollection()->findOne(['_id'=> MongoHelper::mongoId($id)]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound('Not found admin id'));
        }

        return $item;
    }

    public function gets($params, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15,
        );
        $options = array_merge($default, $params);

        $skip = ($options['page']-1)*$options['limit'];
        $condition = ['type'=> ['$in'=> ['admin', 'reception']]];

        $cursor = $this->getUserCollection()
            ->find($condition)
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['created_at'=> -1]);

        $data = [];

        foreach($cursor as $item){
            $data[] = $item;
        }

        $total = $this->getUserCollection()->count($condition);
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
            $res['paging']['next'] = URL::absolute('/admin'.'?'.$nextQueryString);
        }

        return $res;
    }

    public function delete($id, Context $ctx){
        $this->getUserCollection()->remove(['_id'=> MongoHelper::mongoId($id), 'type'=> ['$in'=> ['admin', 'reception']]]);

        return ['success'=> true];
    }
}