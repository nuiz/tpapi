<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/14/14
 * Time: 3:24 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\ContactCommentService;

/**
 * @Restful
 * @uri /contact/comment
 */
class ContactCommentCTL extends BaseCTL {
    /**
     * @POST
     */
    public function post(){
        try {
            $comment = ContactCommentService::instance()->add($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($comment);
            $comment['created_at'] = MongoHelper::timeToInt($comment['created_at']);
            MongoHelper::standardIdEntity($comment['user']);
            return $comment;
        }
        catch (ServiceException $ex) {
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     */
    public function gets(){
        try {
            $comments = ContactCommentService::instance()->gets($this->reqInfo->inputs(), $this->getCtx());

            foreach($comments as $key=> $comment){
                MongoHelper::standardIdEntity($comment);
                $comment['created_at'] = MongoHelper::timeToInt($comment['created_at']);
                MongoHelper::standardIdEntity($comment['user']);
                $comments[$key] = $comment;
            }

            return $comments;
        }
        catch (ServiceException $ex) {
            return $ex->getResponse();
        }

    }
}