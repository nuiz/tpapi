<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/2/14
 * Time: 8:28 AM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Event\Event;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Valitron\Validator;

class OrderService extends BaseService {
    public function getOrderCollection(){
        return DB::getDB()->orders;
    }

    public function getServiceCollection(){
        return DB::getDB()->services;
    }

    public function add($params, Context $ctx){
        // validate
        $v = new Validator($params);
        $v->rule('required', ['orders', 'name', 'phone']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        if(!is_array($params['orders'])){
            return ResponseHelper::validateError(['orders'=> ['orders must be array']]);
        }

        $user = $ctx->getUser();
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::requireAuthorize());
        }

        $insert = [];
        $insert['user'] = ArrayHelper::filterKey(['_id', 'display_name', 'picture'], $user);


        $insert['orders'] = [];
        foreach($params['orders'] as $key=> $value){
            $v = new Validator($value);
            $v->rule('required', ['id', 'amount', 'note']);

            if(!$v->validate()){
                return ResponseHelper::validateError($v->errors());
            }

            $service = $this->getServiceCollection()->findOne(['_id'=> $value['id'], 'price'=> ['$exists'=> true]]
                ,['translate', 'price', 'pictures']);

            $insert['orders'][] = [
                'service'=> $service,
                'amount'=> $value['amount'],
                'total'=> $service['price']*$value['amount']
            ];
        }

        //end validate
        $insert['created_at'] = new \MongoTimestamp();
        $insert['opened'] = false;

        $this->getServiceCollection()->insert($insert);
        MongoHelper::standardIdEntity($insert);

        //convert mongo timestamp to string time
        $insert['created_at'] = MongoHelper::timeToStr($insert['created_at']);

        $self = $this;
        Event::add('after_response', function() use($insert, $self){
            $send = [
                'key'=> 'rtsms/admin/notify',
                'action'=> 'send',
                'data'=> ['unopened'=> $self->unopened(), "order"=> $insert]
            ];
            @\Unirest::post('http://pla2app.com:8901', ['Content-Type'=> 'application/json'], json_encode($send));
        });

        return $insert;
    }

    public function get($id){
        $id = MongoHelper::mongoId($id);
        $item = $this->collection->findOne(['_id'=> $id]);

        if(isset($item['user']['picture'])){
            $item['user']['picture'] = Image::load($item['thumb'])->toArrayResponse();
        }
        $item['created_at'] = MongoHelper::timeToStr($item['created_at']);
        MongoHelper::standardIdEntity($item);

        return $item;
    }

    public function gets($options = array()){
        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($options['page']-1)*$options['limit'];
        $condition = [];

        $cursor = $this->collection
            ->find($condition)
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['created_at'=> -1]);

        $data = [];
        foreach($cursor as $key=> $item){
            if(isset($item['user']['picture'])){
                $item['user']['picture'] = Image::load($item['thumb'])->toArrayResponse();
            }
            $item['created_at'] = MongoHelper::timeToStr($item['created_at']);
            MongoHelper::standardIdEntity($item);
            $data[] = $item;
        }

        $total = $this->collection->count($condition);
        $length = $cursor->count(true);

        return [
            'length'=> $length,
            'total'=> $total,
            'data'=> $data,
            'paging'=> [
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            ]
        ];
    }

    public function unopened(){
        $c = $this->collection->count(['opened'=> false]);
        return ['length'=> $c];
    }

    public function read($id){
        $id = new \MongoId($id);
        $this->collection->update(['_id'=> $id], ['$set'=> ['opened'=> true]]);

        $self = $this;
        Event::add('after_response', function() use($self){
            $send = [
                'key'=> 'rtsms/admin/notify',
                'action'=> 'send',
                'data'=> ['unopened'=> $self->unopened()]
            ];
            @\Unirest::post('http://pla2app.com:8901', ['Content-Type'=> 'application/json'], json_encode($send));
        });

        return ['success'=> true];
    }
}