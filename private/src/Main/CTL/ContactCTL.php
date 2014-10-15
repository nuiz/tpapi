<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 3:42 PM
 */

namespace Main\CTL;
use Main\DataModel\Image;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\ContactService;

/**
 * @Restful
 * @uri /contact
 */
class ContactCTL extends BaseCTL {
    /**
     * @GET
     */
    public function get(){
        $item = ContactService::getInstance()->get($this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @PUT
     */
    public function edit(){
        $item = ContactService::getInstance()->edit($this->reqInfo->inputs(), $this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @POST
     * @uri /comment
     */
    public function addComment(){
        try {
            $comment = ContactService::getInstance()->addComment($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($comment);
            MongoHelper::standardIdEntity($comment['user']);
            $comment['user']['picture'] = Image::load($comment['user']['picture'])->toArrayResponse();
            $comment['created_at'] = MongoHelper::timeToInt($comment['created_at']);
            return $comment;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }
}