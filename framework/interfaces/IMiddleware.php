<?php

namespace Framework;

interface IMiddleware{
    public function getType();
    public function getData();
    public function do();
}