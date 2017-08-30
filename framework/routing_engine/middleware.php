<?php

namespace Framework;

define('MIDDLEWARES_DIRECTORY', 'framework/middlewares/');

class Middleware{

    private $middleware = null;
    private $router = null;
    private $callback = null;

    public function __construct($middleware, $router, $callback){
        $this->middleware = $middleware;
        $this->router = $router;
        $this->callback = $callback;
    }

    public function start(){
        include_once(MIDDLEWARES_DIRECTORY . $this->middleware . '.php');
        $class = '\Framework\\' . $this->middleware;
        $mw = new $class;
        if($mw->getType() == 'before'){
            if($mw->do()){
                $this->router->start(true);
            }else{
                call_user_func($this->callback, $this->router->getResponse(), $mw->getData());
            }
        }else if($mw->getType() == 'after'){
            $this->router->start(true);
            if(!$mw->do()){
                call_user_func($this->callback, $this->router->getResponse(), $mw->getData());
            }
        }
    }

}