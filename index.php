<?php

include_once('framework/routing_engine/router.php');

$router = new Framework\Router();

$router->get('/', function($req, $res){
    $res->render('index', array(
        'title' => 'Welcome',
        'text' => 'Welcome back to the website!'
    ));
})->middleware('Auth', function($res, $data){
    $res->render('login', array(
        'title' => 'Login',
        'errorMsg' => $data
    ));
});

$router->error(function(Exception $e, $res){
    $res->send('404 - We could not find what you were looking for :/', 404);
});

$router->start();