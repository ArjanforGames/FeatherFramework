<?php

namespace Framework;

require_once('response.php');
require_once('middleware.php');

use Framework\response;
use Exception;

class Router{
    private $method = null;
    private $routes = null;
    private $errorFunction = null;
    private $request = null;
    private $currentPath = null;
    private $response = null;
    private $charsAllowed = '[a-zA-Z0-9\_\-]+';
    private $hasMiddleware = false;

    public function __construct(){
        $this->request = new \stdClass();

        if(isset($_SERVER)){
            if(isset($_SERVER['REQUEST_METHOD'])){
                $this->method = $_SERVER['REQUEST_METHOD'];
                $this->request->method = $_SERVER['REQUEST_METHOD'];
            }
            $this->request->header = $this->getHTTPHeaders();
            if(isset($_SERVER['PATH_INFO'])){
                $this->currentPath = $_SERVER['PATH_INFO'];
            }
        }
        if (isset($_POST)) {
          $this->request->body = $_POST;
          $this->request->raw = file_get_contents('php://input');
        }
        if (isset($_GET)) {
            $this->request->params = $_GET;
        }
        if (isset($_FILES)) {
            $this->request->files = $_FILES;
        }
        if (isset($_COOKIE)) {
            $this->request->cookies = $_COOKIE;
        }
        $this->response = new Response();
        $this->routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => [], 'PATCH' => [], 'ANY' => []];
    }

    private function getHTTPHeaders(){
        $header = new \stdClass();
        foreach($_SERVER as $name => $value){
            if(preg_match('/^HTTP_/', $name) || preg_match('/^PHP_AUTH_/', $name) || preg_match('/^REQUEST_/', $name)){
                $header->{$name} = $value;
            }
        }
        return $header;
    }

    private function getRegexRepresentation($path){
        if(preg_match('/[^-:\/_{}()a-zA-Z\d]/', $path)) return false;
        $path = preg_replace('#\(/\)#', '/?', $path);
        $path = preg_replace('/:(' . $this->charsAllowed . ')/', '(?<$1>' . $this->charsAllowed . ')', $path);
        $path = preg_replace('/{(' . $this->charsAllowed . ')}/', '(?<$1>' . $this->charsAllowed . ')', $path);
        $patternAsRegex = "@^" . $path . "$@D";
        return $patternAsRegex;
    }

    public function get($path, $callback){
        $this->routes['GET'][$this->getRegexRepresentation($path)] = $callback;
        return $this;
    }

    public function post($path, $callback){
        $this->routes['POST'][$this->getRegexRepresentation($path)] = $callback;
        return $this;
    }

    public function put($path, $callback){
        $this->routes['PUT'][$this->getRegexRepresentation($path)] = $callback;
        return $this;
    }

    public function patch($path, $callback){
        $this->routes['PATCH'][$this->getRegexRepresentation($path)] = $callback;
        return $this;
    }

    public function delete($path, $callback){
        $this->routes['DELETE'][$this->getRegexRepresentation($path)] = $callback;
        return $this;
    }

    public function any($path, $callback){
        $this->routes['ANY'][$this->getRegexRepresentation($path)] = $callback;
        return $this;
    }

    public function error($function){
        $this->errorFunction = $function;
        return $this;
    }

    public function getResponse(){
        return $this->response;
    }

    public function getCallback($method){
        if(!isset($this->routes[$method])) return null;
        foreach($this->routes[$method] as $name => $value){
            if(preg_match($name, $this->currentPath, $matches) || preg_match($name, $this->currentPath . '/', $matches)){
                $params = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string')));
                $paramsObj = new \stdClass();
                foreach($params as $key => $value){
                    $paramsObj->{$key} = $value;
                }
                $this->request->params = $paramsObj;
                return $this->routes[$method][$name];
            }
        }
    }

    public function start($override = false){
        if(!$this->hasMiddleware || $override){
            $callback = $this->getCallback('ANY');
            if($callback) return $callback($this->request, $this->response);
            $callback = $this->getCallback($this->method);
            if($callback) return $callback($this->request, $this->response);
            if(isset($this->errorFunction)) return ($this->errorFunction)(new Exception("Path not found!", 404), $this->response);
        }
    }

    public function middleware($middleware, $callback){
        $this->hasMiddleware = true;
        (new Middleware($middleware, $this, $callback))->start();
    }
    
}
