---
title: PHP中的魔术方法
date: 2020-03-10 13:42:44
tags:
 - PHP
 - 技术
 
categories: PHP

description: 魔术方法是 PHP 类中特殊的方法，以双下划线 `__` 开头，具有特殊用途，比如我们常用的 `__construct` 构造函数就属于魔术方法。
 
---

## 魔术方法是什么？

魔术方法是 PHP 类中特殊的方法，以双下划线 `__` 开头，具有特殊用途，比如我们常用的 `__construct` 构造函数就属于魔术方法，构造函数的作用是类实例化自动调用的方法。

魔术方法的名称都是系统预定义的，无法修改，我们在写 PHP 代码的时候，为了避免与系统预定义函数相同，不建议用双下划线 `__` 作为函数的前缀。

魔术方法的作用可以归纳为：**对象在 xxx 的时候，应该实现的功能。**

比如，上述构造函数可以理解为：对象在“实例化”的时候，应该实现的功能。

除此之外，与构造函数相对的 **析构函数** `__destruct` 可以在对象被回收时自动调用。

如果有 Java 基础，你可以发现类默认有一种 `toString` 方法，可以把对象转化成字符串。其实 PHP 中也存在同样的魔术方法 `__toString`，当类的对象被当做字符串调用时会自动执行此方法。

示例代码：

```
<?php
class Dog
{
    public function __toString()
    {
        return 'Just a dog.';
    }
}

$dog = new Dog();
echo $dog;
```

执行的结果会输出：Just a dog.

为什么在上述代码中，echo 可以输出一个对象呢？

这是因为我们设置了 `__toString` 方法，当对象被当做字符串调用时会自动触发 `__toString` 方法。

换而言之，**魔术方法是一类由系统预定义了函数名称，在某些情况下被动触发的函数**。

所有的魔术方法都不是用来主动调用的，如下错误示例：

```
// 错误示范
$obj = new MyClass();
$obj->__construct();
```

魔术方法也是类的方法，上述代码逻辑上没有问题而且可以运行且不会报错。不会报错不代表这么写没问题，魔术方法中**不应该**放入业务逻辑相关的代码。

## 应用场景

魔术方法大都用于框架且与设计模式关联紧密，日常业务除了构造方法之外几乎很少接触到其他魔术方法。
Laravel 框架将魔术方法用到了极致，被称为“优雅”的框架。

## PHP 中的魔术方法

下面介绍 PHP 常见的魔术方法以及应用场景和示例代码。

### __construct

俗称类的构造方法，当类被实例化为对象时自动调用。

示例：

```
<?php
class Dog
{
    public function __construct()
    {
        echo 'Just a dog.';
    }
}

$dog = new Dog();
```

输出：Just a dog.

> void __construct ([ mixed $args [, $... ]] )

构造函数的几大特征：

- 构造函数可以接受参数，能够在创建对象时赋值给对象属性
- 构造函数可以调用类方法或其他函数
- 构造函数可以调用其他类的构造函数
- 构造函数的权限可以被修改


示例代码：

```
<?php

class Animal
{
    public function __construct()
    {
        echo 'This is animal.';
    }
}

class Dog extends Animal
{
    protected $name;

    public function __construct($name)
    {
        // 父类构造函数不会自动调用，需要手动进行调用
        parent::__construct();
        
        // 对象赋值
        $this->name = $name;

        // 调用类中的方法
        $this->jump();
    }

    public function jump()
    {
        echo $this->name . ' jump.';
    }
}

$dog = new Dog('小白');
```

最后一条：**构造函数的权限可以被修改。**

在设计模式中会用到，例如单例模式，为了防止子类被实例化，会将构造函数限制为 `private` 私有化。

一个比较标准的单例模式示例代码：

```
<?php
class Singleton {
    // 私有属性，用于保存实例
    private static $instance;
    
    // 构造方法私有化，防止外部创建实例
    private function __construct(){}
    
    // 公有方法，用于获取实例
    public static function getInstance(){
        // 没有的话创建实例并返回，有的话直接返回
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 克隆方法私有化，防止复制实例
    private function __clone(){}
}
```

### __destruct

俗称析构函数，当对象被回收时自动调用。

```
<?php
class Dog
{
    public function __destruct()
    {
        echo 'The dog is dead.';
    }
}

$dog = new Dog();
unset($dog);
```

输出：The dog is dead.

> void __destruct ( void )

析构函数的特征：

- 析构函数不能接受参数
- 析构函数不能抛出异常

由于析构函数在对象被回收时触发，因此如果抛出异常将无法被捕获，抛出异常情况下将报出致命错误。

和构造函数一样，父类的析构函数不会被隐式调用。要执行父类的析构函数，必须在子类的析构函数体中显式调用： `parent::__destruct();`

### __get

当调用对象中不存在的属性时，自动触发该方法。

Laravel 框架里几乎随处可见，如 Model 对象调用表的字段：

```
$user = User::find(1);
echo $user->name;
```

示例代码：

```
<?php

class Dog
{
    protected $attrs = [];

    public function __get($name)
    {
        if(!isset($this->attrs[$name])) {
            return null;
        }

        return $this->attrs[$name];
    }
}

$dog = new Dog();
var_dump($dog->name);
```

输出：NULL

通常情况下，如果直接调用对象中不存在的属性会产生报错，但是设置了 `__get` 方法后，如果调用了不存在的属性则会转而调用这个方法处理。通常 `__get` 要结合 `__set` 一起使用。

### __set

当设置对象中不存在的属性时，自动触发该方法。

示例代码：

```
<?php

class Dog
{
    protected $attrs = [];

    public function __get($name)
    {
        if(!isset($this->attrs[$name])) {
            return null;
        }

        return $this->attrs[$name];
    }

    public function __set($name, $value)
    {
        $this->attrs[$name] = $value;
    }
}

$dog = new Dog();
$dog->name = '小白';
var_dump($dog->name);
```

输出：string(6) "小白"

### __toString

当对象被当成字符串调用时，自动触发该方法。

```
<?php

class Dog
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return 'Dog name is ' . $this->name;
    }
}

$dog = new Dog('小黑');
echo $dog;
```

输出：Dog name is 小黑

这个魔术方法在调试的时候非常有用，可以把对象中的参数信息打印出来，记录到日志里。

### __call

当对象调用了一个类中不存在的方法或者没有权限调用的方法时，自动触发。

示例代码：

```
<?php

class Dog
{
    protected $name;

    public function __call($name, $arguments)
    {
        var_dump($name,$arguments);
    }

    private function aPrivateMethod()
    {
        echo 'Im private.';
    }
}

$dog = new Dog();
$dog->fly('666');
$dog->aPrivateMethod();
```

第一个 fly 方法，不存在 Dog 类中；第二个 aPrivateMethod 是私有方法，不能直接被对象调用，因而触发了 `__call` 方法。

在 Laravel 中也可以看到许多 `__call` 应用的场景，还是 Model 类的例子：

```
$user = User::whereName('xiaobai')->first();
dd($user);
```

这里的 where 后面接大驼峰方式的参数，相当于如下代码：

```
$user = User::where('name', 'xiaobai')->first();
```

通过 `__call` 方法实现简写的目的。

`__call` 方法接收两个参数，第一个参数是调用方法的名称，第二个参数是调用方法时传入的参数，数组格式。

### __callStatic

当对象调用了一个不存在的静态方法时，自动触发。

`__callStatic` 与 `__call` 的作用基本相似，只不过 `__callStatic` 针对的是静态方法。

示例代码：

```
<?php

class Dog
{
    protected $name;

    public static function __callStatic($name, $arguments)
    {
        var_dump($name,$arguments);
    }
}

$dog = new Dog();
$dog::whatsYourProblem();
```

这样的设计在 Laravel 框架中也能看到，依然是 Model 的例子：

```
$users = User::all();
$items = Item::where('price', '>', 100)->paginate(20);
```

Laravel 中的 Model 并不是把方法真的当做静态方法，而是利用 `__callStatic` 让你产生“静态调用”的错觉。

### __invoke

当尝试以调用方法的形式来调用一个对象时，自动触发该方法。

示例代码：

```
<?php

class Dog
{
    protected $name;

    public function __invoke($parm1, $parm2)
    {
        var_dump($parm1,$parm2);
    }
}

$dog = new Dog();
$dog('小白','小黑');
```

输出：

```
string(6) "小白"
string(6) "小黑"
```

`__invoke` 可以接收自定义的任意参数，与函数的形参规则一致。

我们知道这种方法有点奇怪，谁也不会把一个对象当成方法来用吧？

让我们来康康 Laravel 框架是怎么利用 `__invoke` 让代码变得更“优雅”：

```
// 1、指定路由及对应的方法
Route::get('/user', 'UserController@index');

// 2、不指定对应的方法，自动调用 __invoke
Route::get('/user/default', 'UserController');
```

```
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    // 1、/user 调用 index 方法
    public function index()
    {
        
    }
    
    // 2、/user/default 没有指定方法，自动调用 __invoke
    public function __invoke()
    {
        
    }
}
```

不指定具体的方法时，Laravel 的路由会把对象当做方法来执行，从而调用 `__invoke` 方法，简化了路由部分的代码。

### __clone

当对象被克隆时，自动调用。

PHP 中存在一个关键词 clone 可以复制对象，并且复制出来的对象为独立的个体，与原对象不存在互相影响。

```
<?php

class Dog
{
    public $name;

    public function __clone()
    {
        echo 'new dog birth.' . PHP_EOL;
    }
}

$dog = new Dog();
$dog->name = '小白';

// 克隆出来的对象属性与原对象一模一样
$cloneDog = clone $dog;
var_dump($cloneDog->name);

// 修改克隆对象的属性，不会影响原对象
$cloneDog->name = '小黑';
var_dump($dog, $cloneDog);

```

输出：

```
new dog birth.
string(6) "小白"
object(Dog)#1 (1) {
  ["name"]=>
  string(6) "小白"
}
object(Dog)#2 (1) {
  ["name"]=>
  string(6) "小黑"
}

```

可以发现，克隆出来的对象修改了属性，但是原来对象的属性保持不变，它们是互相独立的个体，也就是说并非引用关系，clone 会开辟一块新的内存来存储复制出来的新对象。

`__clone` 方法在 clone 出新对象时自动调用。

clone 业务中用得比较少，应用场景能想到的一个是重构代码，我们需要增加一个新的接口来应对新的需求，但同时又不希望破坏旧接口的内部结构，也不希望直接在旧接口的代码上修改，此时可以使用 clone，既可以向下兼容作用，又能在旧接口上添加新功能。

```
<?php

class NewDogAction
{
    // 新接口代码
}

class OldDogAction
{
    // 旧版接口代码
}

class Dog
{
    public $name;

    // 动作类对象
    private $action;

    public function __construct()
    {
        $this->action = new OldDogAction();
    }

    public function __clone()
    {
        $this->action = new NewDogAction();
    }
}
```

当 clone 出来的时候，action 被替换成新的接口代码。

换成比较形象的例子：

鸣人使用多重影分身之术，可以看成是 clone 出很多个分身，但是这些分身并不能 100% 继承本体的能力，本体的能力可以看成上面的 `oldDogAction`，而分身的能力则是 `newDogAction`，分身除了 action 属性之外其他的地方与本体并无差异。

## 更多的魔术方法

如果有兴趣了解全部的魔术方法，请访问 [PHP：魔术方法](https://www.php.net/manual/zh/language.oop5.magic.php)