<?php

class Action
{

}

class Dog
{
    public $action;

    public function __construct(Action $action)
    {
        $this->action = $action;
    }
}