<?php

class IndexController
{
    public function sayHello()
    {
        echo 'hello';
    }
}

$controller = 'IndexController';
$obj = new $controller;

$obj->sayHello();
