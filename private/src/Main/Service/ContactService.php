<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 3:10 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Valitron\Validator;

class ContactService extends BaseService {
    public function getCollection(){
        $db = DB::getDB();
        return $db->contacts;
    }

    public function get(Context $ctx){
        $app = $ctx->getApp();

        $contact = $this->getCollection()->findOne(['_id'=> $app['_id']], ["phone", "website", "email", "location"]);
        if(is_null($contact)){
            $contact = [
                '_id'=> $app['_id'],
                'phone'=> '053-3333333',
                'website'=> 'http://example.com',
                'email'=> 'example@example.com',
                'location'=> [
                    'lat'=> "1.23044454",
                    'lng'=> "1.12315643"
                ],
                'comments'=> []
            ];
            $this->getCollection()->insert($contact);
        }
        return $contact;
    }

    public function edit($params, Context $ctx){
        $allowed = ["phone", "website", "email", "location"];
        $set = ArrayHelper::filterKey($allowed, $params);
        $entity = $this->get($ctx);
        if(count($set)==0){
            return $entity;
        }
        if(isset($set['location'])){
            $set['location'] = ArrayHelper::filterKey(['lat', 'lng'], $set['location']);
        }
        $set = ArrayHelper::ArrayGetPath($set);
        $this->getCollection()->update(['_id'=> $entity['_id']], ['$set'=> $set]);

        return $this->get($ctx);
    }

    // ***************** comments ***********************

    public function addComment($params, Context $ctx){
        $id = $ctx->getAppId();
        $v = new Validator($params);
        $v->rule('required', ['message']);

        $user = $ctx->getUser();
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::requireAuthorize());
        }

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $entity = $this->getCollection()->findOne(['_id'=> $id], ['comments']);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }
        if(!isset($entity['comments'])){
            $this->getCollection()->update(['_id'=> $id], ['$set'=>['comments'=> []]]);
        }

        $comment = [
            '_id'=> new \MongoId(),
            'user'=> [
                '_id'=> $user['_id'],
                'display_name'=> $user['display_name'],
                'picture'=> $user['picture']
            ],
            'message'=> $params['message'],
            'created_at'=> new \MongoTimestamp()
        ];

        $this->getCollection()->update(['_id'=> $id], ['$push'=> ['comments'=> $comment]]);
        return $comment;
    }

    public function getComments($params, Context $ctx){
        $id = $ctx->getAppId();
        if($this->getCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

        $default = ["page"=> 1, "limit"=> 15];
        $options = array_merge($default, $params);
        $arg = $this->getCollection()->aggregate([
            ['$match'=> ['_id'=> $id]],
            ['$project'=> ['comments'=> 1]],
            ['$unwind'=> '$comments'],
            ['$group'=> ['_id'=> null, 'total'=> ['$sum'=> 1]]]
        ]);

        $total = (int)@$arg['result'][0]['total'];
        $limit = (int)$options['limit'];
        $page = (int)$options['page'];

        $slice = MongoHelper::createSlice($page, $limit, $total);

        if($slice[1] == 0){
            $data = [];
        }
        else {
            $entity = $this->getCollection()->findOne(['_id'=> $id], ['comments'=> ['$slice'=> $slice]]);
            $data = [];
            foreach($entity['comments'] as $key=> $value){
                $comment = $value;
                $comment['id'] = MongoHelper::standardId($comment['id']);
//                $comment['user_id'] = MongoHelper::standardId($comment['user_id']);

                $comment['user'] = $this->db->users->findOne(['_id'=> $comment['user_id']], ['display_name', 'picture']);
                $comment['user']['picture'] = Image::load($comment['user']['picture'])->toArrayResponse();
                MongoHelper::standardIdEntity($comment['user']);
                unset($comment['user_id']);

                $comment['created_at'] = MongoHelper::timeToStr($comment['created_at']);
                $data[] = $comment;
            }
        }

        // reverse data
        $data = array_reverse($data);

        $res = [
            'length'=> count($data),
            'total'=> $total,
            'data'=> $data,
            'paging'=> [
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            ]
        ];
        $pagingLength = $total/(int)$options['limit'];
        $pagingLength = floor($pagingLength) + 1;
        $res['paging']['length'] = $pagingLength;
        $res['paging']['current'] = (int)$options['page'];
        if(((int)$options['page'] * (int)$options['limit']) < $total){
            $nextQueryString = http_build_query(['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $res['paging']['next'] = URL::absolute('/news/'.MongoHelper::standardId($id).'/comment?'.$nextQueryString);
        }

        return $res;
    }

    public function deleteComment($commentId, Context $ctx){
        $id = $ctx->getAppId();
        $commentId = MongoHelper::mongoId($commentId);
        $this->getCollection()->update(['_id'=> $id], ['$pull'=> ['comments'=> ['id'=> $commentId]]]);

        return ['success'=> true];
    }
}