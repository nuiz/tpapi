<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/15/14
 * Time: 11:32 AM
 */

namespace Main;


use DocBlock\Parser;
use Main\Event\Event;
use Main\Http\RequestInfo;


class AutoRoute {
    public static function dispatch(){
        $route = self::mapAllCTL();
        $match = $route->match();

//        ob_end_clean();
//        header("Connection: close");
//        ignore_user_abort(); // optional
//        ob_start();

        if($match['target']){
            $reqInfo = RequestInfo::loadFromGlobal(array("url_params"=> $match['params']));
            $ctl = new $match['target']['c']($reqInfo);
            $response = $ctl->{$match['target']['a']}();
            header("Content-type: application/json");
            echo json_encode($response);
        }
        else {
            header("HTTP/1.0 404 Not Found");
        }
//
//        $size = ob_get_length();
//        header("Content-Length: $size");
//        ob_end_flush(); // Strange behaviour, will not work
//        flush();            // Unless both are called !

        // fire event after_response
        Event::trigger('after_response');

        exit();
    }

    public static function mapAllCTL(){
        $router = new \AltoRouter();

        if($_SERVER['HTTP_HOST']== 'localhost' || $_SERVER['HTTP_HOST']== '192.168.0.111'){
            $router->setBasePath('/rtsms');
        }
        $ctls = self::readCTL();
        foreach($ctls as $ctl){
            $router->map(implode('|', $ctl['methods']), $ctl['uri'], array(
                'c'=> $ctl['controller'],
                'a'=> $ctl['action']
            ));
        }

        return $router;
    }

    public static function readCTL(){
        $routes = array();

        $parse = new Parser();
        foreach(self::allCTL() as $className){
            $parse->analyze($className);
        }

        $parse->setAllowInherited(true);
        //$parse->setMethodFilter(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
        $classes = $parse->getClasses();
        foreach($classes as $class)
        {
            // is web restful
            if(!$class->hasAnnotation("Restful"))
                continue;

            // is has uri annotation
            $classUriAnns = $class->getAnnotations("uri");
            if (empty($classUriAnns))
                continue;

            $className = $class->getName();
            $classUri = $classUriAnns[0]->getValue();

            $methods = $class->getMethods();
            foreach ($methods as $method)
            {
                $HttpMethods = array();
                if($method->hasAnnotation('GET')){
                    $HttpMethods[] = 'GET';
                }
                if($method->hasAnnotation('POST')){
                    $HttpMethods[] = 'POST';
                }
                if($method->hasAnnotation('PUT')){
                    $HttpMethods[] = 'PUT';
                }
                if($method->hasAnnotation('DELETE')){
                    $HttpMethods[] = 'DELETE';
                }

                $uriParamAnns = $method->getAnnotations("uri");

                if (count($uriParamAnns) == 0) {
                    $uri = $classUri;
                }
                else {
                    $uri = $classUri.$uriParamAnns[0]->getValue();
                }

                $route = array('controller'=> $className, 'action'=> $method->getName(),'methods'=> $HttpMethods, 'uri'=> $uri);
                $routes[] = $route;
            }
        }

        return $routes;
    }

    public static function allCTL(){
        $names = array();
        foreach (self::glob_recursive(dirname(__FILE__).'/CTL/*.php') as $filename)
        {
            $name = "Main\\".str_replace(dirname(__FILE__).'/', "", $filename);
            $name = str_replace("/", "\\", $name);
            $name = str_replace(".php", "", $name);
            $names[] = $name;
        }

        return $names;
    }

    public static function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, self::glob_recursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }
}