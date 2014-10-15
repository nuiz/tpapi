<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/16/14
 * Time: 3:58 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ResponseHelper;

class ContactCommentService extends BaseService {
    public function getCollection(){
        return DB::getDB()->contacts_comments;
    }

    public function gets($options = array(), Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($default['page']-1)*$default['limit'];
        //$select = array("name", "detail", "feature", "price", "pictures");
        $condition = array();
        $cursor = $this->getCollection()
            ->find($condition)
            ->sort(array('_id'=> 1))
            ->limit($default['limit'])
            ->skip($skip);

        $total = $this->getCollection()->count($condition);
        $length = $cursor->count();

        $data = array();
        foreach($cursor as $item){
            $data[] = $item;
        }

        return array(
            'length'=> $length,
            'total'=> $total,
            'data'=> $data,
            'paging'=> array(
                'page'=> $options['page'],
                'limit'=> $options['limit']
            )
        );
    }

    public function add($params, Context $ctx){
        $now = new \MongoTimestamp();
        $user = $ctx->getUser();
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::requireAuthorize());
        }
        $entity = array(
            "message"=> $params['message'],
            "created_at"=> $now,
            "user"=> [
                "_id"=> $user['_id'],
                "display_name"=> $user['display_name']
            ]
        );

        $this->getCollection()->insert($entity);

        return $entity;
    }
}