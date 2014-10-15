<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/30/14
 * Time: 2:43 PM
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

class PromotionService extends BaseService {
    public function getCollection(){
        return DB::getDB()->promotions;
    }

    public function add($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['translate', 'thumb', 'discount_percentage']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert = ArrayHelper::filterKey(['thumb', 'discount_percentage'], $params);

        // translate
        foreach($params['translate'] as $key=> $value){
            $v = new Validator($value);
            $v->rule('required', ['name', 'detail']);

            if(!$v->validate()){
                throw new ServiceException(ResponseHelper::validateError($v->errors()));
            }

            $insert['translate'][$key] = ArrayHelper::filterKey(['name', 'detail'], $value);
        }

        $insert['thumb'] = Image::upload($insert['thumb'])->toArray();
        $insert['discount_percentage'] = (int)$insert['discount_percentage'];

//        $insert['app_id'] = $ctx->getAppId();
        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);

        $coll = $this->getCollection();
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

    public function get($id, Context $ctx){
        $item = $this->getCollection()->findOne(['_id'=> MongoHelper::mongoId($id)]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound('Not found promotion'));
        }
        return $item;
    }

    public function edit($id, $params, Context $ctx){
        $set = [];
        $entity = $this->get($id, $ctx);
        if(isset($params['translate'])){
            foreach($params['translate'] as $key => $value){
                $set['translate'] = [];
                if(isset($entity['translate'][$key])){
                    $set['translate'][$key] = array_merge($entity['translate'][$key], $value);
                }
                else {
                    $set['translate'][$key] = array_merge([
                        'name'=> '',
                        'detail'=> ''
                    ], $value);
                }
            }

//            $set = ArrayHelper::ArrayGetPath($set);
            $this->getCollection()->update(['_id'=> MongoHelper::mongoId($id)], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
        }

        if(isset($params['translate_remove'])){
            foreach($params['translate_remove'] as $key => $value){
                $entity = $this->get($id, $ctx);
                if(count($entity['translate'])<=1){
                    break;
                }
                $this->getCollection()->update(
                    ['_id'=> MongoHelper::mongoId($id)],
                    ['$unset'=> ArrayHelper::ArrayGetPath([
                            'translate'=> [$value=> 1]
                        ])]
                );
            }
        }

        $set = ArrayHelper::filterKey(['thumb', 'discount_percentage'], $params);
        if(isset($set['thumb'])){
            $set['thumb'] = Image::upload($set['thumb'])->toArray();
        }
        if(isset($set['discount_percentage'])){
            $set['discount_percentage'] = (int)$set['discount_percentage'];
        }
        if(count($set) > 0){
            $this->getCollection()->update(['_id'=> MongoHelper::mongoId($id)], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
        }

        return $this->get($id, $ctx);
    }

    public function delete($id, Context $ctx){
        return $this->getCollection()->remove(['_id'=> MongoHelper::mongoId($id)]);
    }
}