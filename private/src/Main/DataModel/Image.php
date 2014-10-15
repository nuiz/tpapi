<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/19/14
 * Time: 2:49 PM
 */

namespace Main\DataModel;


use Main\DB;
use Main\Helper\URL;

class Image {
    const BASE_URL = "http://110.164.70.60";
    protected $id, $width, $height;
    protected function __construct($id, $width, $height){
        $this->id = $id;
        $this->width = $width;
        $this->height = $height;
    }

    public static function absoluteUrl($url){
        return self::BASE_URL.'/'.$url;
    }

    public function toArray(){
        return [
            'id'=> $this->id,
            'width'=> $this->width,
            'height'=> $this->height
        ];
    }

    public function toArrayResponse(){
        return [
            'id'=> $this->id,
            'width'=> $this->width,
            'height'=> $this->height,
            'url'=> $this->absoluteUrl('get/'.$this->id.'/')
        ];
    }

    public static function upload($b64){
        $url = self::absoluteUrl('post');
        $response = @\Unirest::post($url, ["Accept" => "application/json"], ["img"=> $b64]);
//        $response->code; // HTTP Status code
//        $response->headers; // Headers
//        $response->raw_body; // Unparsed body

        $data = $response->body; // Parsed body
        return new self($data->objectid, $data->original_size->original_width, $data->original_size->original_height);
    }

    public static function load($params){
        return new self($params['id'], $params['width'], $params['height']);
    }

    public static function loads($loads){
        $data = new ImageCollection();
        foreach($loads as $load){
            $data[] = self::load($load);
        }

        return $data;
    }
}