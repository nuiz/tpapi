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

class PlaceService extends BaseService {
    public function getCollection(){
        $db = DB::getDB();
        return $db->places;
    }

    public function add($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['translate', 'location', 'pictures']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert = ArrayHelper::filterKey(['location'], $params);

        $v = new Validator($params['location']);
        $v->rule('required', ['lat', 'lng']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert['location'] = ArrayHelper::filterKey(['lat', 'lng'], $params['location']);

        // translate
        foreach($params['translate'] as $key=> $value){
            $v = new Validator($value);
            $v->rule('required', ['name', 'detail']);

            if(!$v->validate()){
                throw new ServiceException(ResponseHelper::validateError($v->errors()));
            }

            $insert['translate'][$key] = ArrayHelper::filterKey(['name', 'detail'], $value);
        }

        $insert['pictures'] = [];
        foreach($params['pictures'] as $key=> $value){
            $insert['pictures'][] = Image::upload($value)->toArray();
        }

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
            throw new ServiceException(ResponseHelper::notFound('Not found place'));
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

        if(isset($params['location'])){
            $set = ['location'=> []];
            if(isset($params['location']['lat'])){
                $set['location']['lat'] = $params['location']['lat'];
            }
            if(isset($params['location']['lng'])){
                $set['location']['lng'] = $params['location']['lng'];
            }

            if(count($set['location']) > 0){
                $this->getCollection()->update(['_id'=> MongoHelper::mongoId($id)], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
            }
        }

        return $this->get($id, $ctx);
    }

    public function getPictures($id, $params){
        $id = MongoHelper::mongoId($id);
        if($this->getCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

//        $this->collection->update(['_id'=> $id], ['$setOnInsert'=> ['history'=> []]], ['upsert'=> true]);

        $default = ["page"=> 1, "limit"=> 15];
        $options = array_merge($default, $params);
        $arg = $this->getCollection()->aggregate([
            ['$match'=> ['_id'=> $id]],
            ['$project'=> ['pictures'=> 1]],
            ['$unwind'=> '$pictures'],
            ['$group'=> ['_id'=> null, 'total'=> ['$sum'=> 1]]]
        ]);

        $total = (int)@$arg['result'][0]['total'];
        $limit = (int)$options['limit'];
        $page = (int)$options['page'];

//        $slice = MongoHelper::createSlice($page, $limit, $total);
        $slice = [($page-1)*$page, $limit];

        if($slice[1] == 0){
            $data = [];
        }
        else {
            $entity = $this->getCollection()->findOne(['_id'=> $id], ['pictures'=> ['$slice'=> $slice]]);
            $data = Image::loads($entity['pictures'])->toArrayResponse();
        }

        // reverse data
        // $data = array_reverse($data);

        return array(
            'length'=> count($data),
            'total'=> $total,
            'data'=> $data,
            'paging'=> array(
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            )
        );
    }

    public function addPictures($id, $params){
        $id = MongoHelper::mongoId($id);
        $v = new Validator($params);
        $v->rule('required', ['pictures']);
        if(!$v->validate()){
            return ResponseHelper::validateError($v->errors());
        }

        if($this->getCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

        $res = [];
        foreach($params['pictures'] as $value){
            $img = Image::upload($value);
            $this->getCollection()->update(['_id'=> $id], ['$push'=> ['pictures'=> $img->toArray()]]);
            $res[] = $img->toArrayResponse();
        }

        return $res;
    }

    public function deletePictures($id, $params){
        $id = MongoHelper::mongoId($id);
        $v = new Validator($params);
        $v->rule('required', ['id']);
        if(!$v->validate()){
            return ResponseHelper::validateError($v->errors());
        }

        if($this->getCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

        $res = [];
        foreach($params['id'] as $value){
            $arg = $this->getCollection()->aggregate([
                ['$match'=> ['_id'=> $id]],
                ['$project'=> ['pictures'=> 1]],
                ['$unwind'=> '$pictures'],
                ['$group'=> ['_id'=> null, 'total'=> ['$sum'=> 1]]]
            ]);

            $total = (int)@$arg['result'][0]['total'];
            if($total==1){
                break;
            }

            $this->getCollection()->update(['_id'=> $id], ['$pull'=> ['pictures'=> ['id'=> $value]]]);
            $res[] = $value;
        }

        return $res;
    }

    public function delete($id, Context $ctx){
        return $this->getCollection()->remove(['_id'=> MongoHelper::mongoId($id)]);
    }
}