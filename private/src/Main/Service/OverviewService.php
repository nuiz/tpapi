<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/1/14
 * Time: 10:24 AM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Helper\ArrayHelper;
use Main\Helper\ResponseHelper;
use Valitron\Validator;

class OverviewService extends BaseService {
    public function getCollection(){
        return DB::getDB()->overviews;
    }

    public function get(Context $ctx){
        $entity = $this->getCollection()->findOne(['_id'=> $ctx->getAppId()], ['translate']);
        if(is_null($entity)){
            $entity = [
                '_id'=> $ctx->getAppId(),
                'translate'=> [
                    'en'=> [
                        'name'=> 'Demo Hotel',
                        'detail'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum."
                    ]
                ],
                'pictures'=> [
                    [
                        'id'=> '542bed8690cc13a3048b4569png',
                        'width'=> 640,
                        'height'=> 387
                    ]
                ]
            ];
            $this->getCollection()->insert($entity);
            unset($entity['pictures']);
        }
        return $entity;
    }

    public function edit($params, Context $ctx){
        $set = [];
        $entity = $this->get($ctx);
        if(isset($params['translate'])){
            $set['translate'] = [];
            foreach($params['translate'] as $key => $value){
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
            $this->getCollection()->update(['_id'=> $ctx->getAppId()], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
        }

        if(isset($params['translate_remove'])){
            foreach($params['translate_remove'] as $key => $value){
                $entity = $this->get($ctx);
                if(count($entity['translate'])<=1){
                    break;
                }
                $this->getCollection()->update(['_id'=> $ctx->getAppId()],
                    ['$unset'=> ArrayHelper::ArrayGetPath([
                        'translate'=> [$value=> 1]
                    ])]);
            }
        }

        return $this->get($ctx);
    }

    public function getPictures($params, Context $ctx){
        $id = $ctx->getAppId();
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

    public function addPictures($params, Context $ctx){
        $id = $ctx->getAppId();
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

    public function deletePictures($params, Context $ctx){
        $id = $ctx->getAppId();
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
}