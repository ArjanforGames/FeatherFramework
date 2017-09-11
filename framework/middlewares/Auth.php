<?php

namespace Framework;

require_once('framework/interfaces/Middleware.php');

session_start();

class Auth implements MiddleWareInterface{

    private $type = 'before';
    private $data = null;

    public function getType(){
        return $this->type;
    }

    public function getData(){
        return $this->data;
    }

    public function do(){
        if(isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) return true;
        $this->data = 'You do not appear to be logged in!';
        return false;
    }

}
