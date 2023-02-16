---
title: PHP中的反射
date: 2020-03-10 17:55:28
tags:
 - PHP
 - 技术
 
categories: PHP

description: 反射原本指的是一种光学现象，光在传播时照射在物体上会产生返回原物体的现象。在 PHP 中，反射的作用类似光的传播，PHP 可以通过反射机制拿到代码本身，也就是通过代码得到代码。

---
## 反射是什么？
反射原本指的是一种光学现象，光在传播时照射在物体上会产生返回原物体的现象。在 PHP 中，反射的作用类似光的传播，PHP 可以通过反射机制拿到代码本身，也就是通过代码得到代码，反射一词十分形象。

通过反射机制可以获取类中的变量、方法名称甚至是注释等等，在正常的开发环境中几乎不会用到，一般都是在框架设计时使用，目的是增加框架的扩展性。

Laravel、Swoft 框架都用到了反射机制，Swoft 注解的实现原理就是使用反射机制来实现的。

一些 API 文档插件可以通过注释来编译生成 API 文档，其原理同样是使用了 PHP 的反射机制。

## 示例
定义一个类，类里面有常量、私有属性（private 声明的变量）、类的注释和方法的注释等等。

思考下面几个业务中几乎不会用到的问题：

如果我们要获取类里面的所有常量，应该怎么做？

如果我们要获取方法的注释，或者类的注释，应该怎么做？

如果我们要获得类的命名空间，又该怎么做？

此时习惯了做业务的我们肯定一脸懵逼，PHP 中的反射就是为了解决这一类的问题，通过反射提供的 API 可以拿到一个类的所有信息。

通过下面的代码举例，你马上就会弄懂什么是反射了！

```
<?php
/**
 * 类的注释
 */
class User
{
    const BOY = 1;
    const GIRL = 2;

    private $name;

    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * 我是方法注释
     */
    public function sayHello() {
        echo 'hello!';
    }
}

$class = new ReflectionClass('User');  // 将类名User作为参数，即可建立User类的反射类
$properties = $class->getProperties();  // 获取User类的所有属性，返回ReflectionProperty的数组
$property = $class->getProperty('name'); // 获取User类的属性ReflectionProperty
$methods = $class->getMethods();   // 获取User类的所有方法，返回ReflectionMethod数组
$method = $class->getMethod('sayHello');  // 获取User类的方法的ReflectionMethod
$constants = $class->getConstants();   // 获取所有常量，返回常量定义数组
$constant = $class->getConstant('BOY');   // 获取常量
$namespace = $class->getNamespaceName();  // 获取类的命名空间
$comment_class = $class->getDocComment();  // 获取User类的注释文档，即定义在类之前的注释
$comment_method = $class->getMethod('sayHello')->getDocComment();  // 获取User类中方法的注释文档

var_dump($comment_method);
```

上面的代码会输出：

```
string(39) "/**
     * 我是方法注释
     */"
```

## 反射 API
PHP 官方手册：[https://www.php.net/reflection](https://www.php.net/reflection)

## 应用场景

反射机制会打破类的封装性，日常业务也不需要获取代码的注释。

因此在日常开发中几乎不会直接用到，但是在框架或者插件的设计上却能发挥很大的作用。

### 生成 API 文档
由于反射可以拿到类的属性、方法，就可以自动生成类的文档。

典型例子：[API DOC](https://apidocjs.com/)

通过在方法名称上添加注释：

```
/**
 * @api {get} /user/:id Request User information
 * @apiName GetUser
 * @apiGroup User
 *
 * @apiParam {Number} id Users unique ID.
 *
 * @apiSuccess {String} firstname Firstname of the User.
 * @apiSuccess {String} lastname  Lastname of the User.
 */
```

然后运行编译程序就可以直接生成一个美观、排版整齐的 API 文档。

![APIDOC](https://apidocjs.com/img/example.png)

一些 IDE 提示工具也利用反射获取类的注释，然后实现提示的功能，注释时需要根据一定的规范。

注释示例：

```
/**
 * 测试方法
 * @param $a
 * @param $b
 */
function test($a,$b){
    
}
```

### 批量复刻文件
既然可以拿到类的所有成员，那么以类为母版，克隆出子类文件轻而易举，在一些框架或插件中经常用到。

Laravel 框架可以使用 `php artisan make:controller UserController` 命令创建一个控制器类的模板，还可以加上参数 `-r` 生成一个 RESTful 风格的 API 控制器类。

还有数据库迁移工具（Laravel 内置了此插件），可以通过命令：

```
php vendor/bin/phinx create MyMigration
```

直接生成一个数据库迁移文件。

直接用命令的方式生成文件，可以少写很多重复的代码。

### 依赖注入

先不需要知道依赖注入是什么，看下面的例子，Laravel 很普通的控制器类的写法：

```
<?php

namespace App\Http\Controllers;

use App\Service\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }
    
    public function index()
    {
        $users = $this->service->getAllUsers();
        
        dd($users);
    }
}
```

在这个例子中，我们通过构造函数赋予了属性 `$service`，但问题是——控制器类并没有被实例化！

一般情况下，我们需要这样把参数传给构造方法：

```
$service = new UserService();
$user = new UserController($service);
```

上面的例子并没有 `UserController` 的实例化操作，而且在 PHP 中参数前面加上类名称，只是起到变量类型限制的作用。

到底是哪里传来实例化的 `UserService` 呢？

其实是通过反射机制实现的，通过反射获取到了控制器类的构造方法，然后将这个控制器所需要**依赖**的类实例化后生成的对象**注入**到控制器里，所以这个叫做依赖注入。

依赖注入这个概念是从 Java 中传过来的，并非 Laravel 特有。

假设不使用反射机制注入依赖，那么我们的控制器是这样的：

```
<?php

namespace App\Http\Controllers;

use App\Service\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function index()
    {
        $users = $this->service->getAllUsers();

        dd($users);
    }
}
```

嗯……？代码量好像差不多！

依赖注入是一种设计模式，运行的结果没有差别。

其实在学 Laravel 的时候，我发现了一个很奇怪的地方。

比如存在路由：

```
Route::get('/users', 'UserController@index')->name('users.index');
Route::get('/users/{id}', 'UserController@show')->name('users.show');
```

然后控制器的方法：

```
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        dd($request);
    }
    
    public function show(Request $request, $id)
    {
        dd($request, $id);
    }
}
```

index 方法的第一个`Illuminate\Http\Request` 类型的参数，我们在路由中没有任何参数，既然不是通过匹配路由得到的参数，这个参数又是怎么来的呢？

更不可思议的是第二个路由的 show 方法，我们在声明路由的时候只指明了一个参数 `/users/{id}`，但我们现在却在方法中写了两个参数，又是怎么精确地匹配到 ID 值的？

其实同样是用了依赖注入的方法实现的，在学习了反射之后，它们的原理就大概知道了。

首先通过反射得到一个方法的参数，如果这个参数定义了某些类型，就将其实例化后再传递给该方法，在 Laravel 中有专门的解析类在处理这些参数。

### 通过注释生成路由

Swoft 框架把注释当做定义路由的方法，称为“注解”。

```
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;

/**
 * Class Home
 *
 * @Controller(prefix="home")
 */
class Home
{
    /**
     * 该方法路由地址为 /home/index
     *
     * @RequestMapping(route="/index", method="post")
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // TODO:
    }
}
```

用 PHP 的反射机制可以做一些奇奇怪怪的事，这也算是 Swoft 独特的风格吧。