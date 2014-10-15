<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/17/14
 * Time: 1:01 PM
 */

namespace Main\Http;


class RequestInfo {
    private $inputs = array(),
        $params = array(),
        $queries = array(),
        $files = array(),
        $method = 'GET',
        $url_params = array();

    public function __construct($method, $queries, $params, $files, $url_params){
        $this->method = $method;
        $this->queries = $queries;
        $this->params = $params;
        $this->files = $files;
        $this->url_params = $url_params;

        $this->inputs = array_merge($this->queries, $this->params);
    }

    public static function loadFromGlobal(array $options = null)
    {
        $ctType = isset($_SERVER['CONTENT_TYPE'])? $_SERVER['CONTENT_TYPE']: null;
        $method = isset($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']: 'GET';

        if($ctType=='application/json'){
            $jsonText = file_get_contents('php://input');
            $params = json_decode($jsonText, true);
            $params = array_merge($_GET, $params);
        }
        else if($method=='POST'){
            $params = $_POST;
        }
        else if($method=='PUT' || $method == 'DELETE'){
            $put = array();
            parse_str(file_get_contents("php://input"), $put);
            $params = $put;
        }
        else {
            $params = $_GET;
        }

        if(isset($options['url_params'])){
            $url_params = $options['url_params'];
        }
        else {
            $url_params = array();
        }

        return new self($method, $_GET, $params, $_FILES, $url_params);
    }

    public function params()
    {
        return $this->params;
    }

    public function param($name, $default = null){
        return isset($this->params[$name])? $this->params[$name]: $default;
    }

    public function hasParam($name){
        return isset($this->params[$name]);
    }

    public function inputs()
    {
        return $this->inputs;
    }

    public function input($name, $default = null)
    {
        return isset($this->inputs[$name])? $this->inputs[$name]: $default;
    }

    public function hasInput($name){
        return isset($this->inputs[$name]);
    }

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function urlParams()
    {
        return $this->url_params;
    }

    public function urlParam($name)
    {
        return $this->url_params[$name];
    }
}