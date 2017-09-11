<?php

namespace Framework;

require_once('framework/template_engine/templater.php');

class Response{
    public function send($data, $status = null){
        if(isset($status)){
            http_response_code($status);
        }
        return print($data);
    }

    public function json($data, $status = null){
        if(isset($status)){
            http_response_code($status);
        }
        return print(json_encode($data));
    }

    public function render($view, $data = null){
        return print((new Template($view, $data))->parse());
    }

    public static function status($status){
        if(isset($status)){
            http_response_code($status);
        }
        return print($status);
    }
}
