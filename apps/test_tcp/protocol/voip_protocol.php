<?php

class voip_protocol implements protocol{
    public static function input($serv, $fd, $data){
        return strlen($data);
    }
    
    public static function decode($serv, $fd, $data){
        return $data;
    }
    
    public static function request($serv, $fd, $data){
        return $data;
    }
    
    public static function encode($serv, $fd, $data){
        return $data;
    }
}
