<?php

namespace Framework;

interface MiddlewareInterface{
    public function getType();
    public function getData();
    public function do();
}
