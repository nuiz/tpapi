<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/8/14
 * Time: 2:40 PM
 */

namespace Main\Event;


class Event {
    protected static $events = [];

    public static function add($event, \Closure $func){
        self::$events[$event][] = $func;
    }

    public static function trigger($event, $args = array())
    {
        if(isset(self::$events[$event]))
        {
            foreach(self::$events[$event] as $func)
            {
                call_user_func($func, $args);
            }
        }

    }
}