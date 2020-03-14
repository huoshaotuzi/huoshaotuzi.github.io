<?php

trait A
{
    public function hello()
    {
        echo 'A:hello' . PHP_EOL;
    }

    public function world()
    {
        echo 'A:world' . PHP_EOL;
    }
}

trait B
{
    public function hello()
    {
        echo 'B:hello' . PHP_EOL;
    }

    public function world()
    {
        echo 'B:world' . PHP_EOL;
    }
}

class Test
{
    use A, B {
        A::hello as ahello;
        A::world as aworld;
        B:: hello insteadof A;
        B:: world insteadof A;
    }
}

$test = new Test();
$test->hello();
$test->world();
$test->ahello();
$test->aworld();