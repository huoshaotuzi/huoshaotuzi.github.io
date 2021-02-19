---
title: 从零开始搭建自己的Swoole框架（四）路由模块
date: 2021-02-09 11:25:34
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 实现简单路由功能。
---
## 前言
路由是一个框架最基本的功能，

虽然没研究过框架的路由是怎么加载的，

这里就凭直觉和使用 Laravel 的经验来自己写一个。

## REQUEST_URI
用户请求网页输入的网址叫做 URL，URL 在网上已经被统称为 URI 了，下文也采用 URI 的说法。

（注意区分：一个是i，一个是l）

> 其实 URL 跟 URI 有区别，如果你有兴趣可以了解一下这个小知识：URL 是 URI 的子集，URI 是一种抽象的概念，只要是任何可以可以找到某个资源的方法就叫做 URI，它还包括：URN，就是通过资源名字+身份证的方式找资源，但是 URN 几乎没人用，导致 URI 几乎全部是 URL，所以现在把 URL 当做 URI 也没什么问题，实际上它们算是抽象父类和子类的关系

swoole 可以通过 `$request->server['request_uri']` 获取到请求的资源路径。

## 路由模块原理
用户访问网站的本质就是请求服务器上面的一个文件。

比如请求一张图片：

```
# 名称为 1.jpg 的文件
http://www.huotublog.com/img/1.jpg
```

不仅是图片，js、css 都是文件，包括 php 也是一种文件。

我们在 nginx 的配置：

```
server {
    listen 80;
    server_name firerabbit-engine.ht;

    location ~* \.(gif|jpg|jpeg|png|css|js|ico|ttf|woff|woff2|svg|map)$ {
        root /www/firerabbit-engine/public;
    }

    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        
        if (!-e $request_filename){
            proxy_pass http://php-fpm72:9527; # 注意
        }
    }
}
```

这里把图片等静态文件全部指向 /www/firerabbit-engine/public 目录。

如果没有找到文件，就转发给 `http://php-fpm72:9527` 来处理。

而监听这个端口的，就是 swoole 程序。

例如用户访问网页地址为：http://www.huotublog.com/article/1

这个地址的后缀不符合 nginx 配置的设定，属于“找不到文件”的情况，

因此 nginx 就会把请求转发给我们设置的 swoole 监听的端口，

> nginx 在转发时会将请求的参数、cookie 等也一并转发

swoole 接收到的 URI 即：/article/1

"/article/1" 只是一个字符串，

究竟是怎么变成 Controller 里的方法被执行的？

这就是路由解释器的作用了：**将字符串解析成对应的控制器和方法。**

### PHP 实例化方法
在 PHP 中实例化对象是通过：`new 类名` 的方法，但是我们这里只能拿到字符串。

那应该怎么实例化出对象呢？

下面这样明显是错的：

```
<?php

class IndexController
{
    public function sayHello()
    {
        echo 'hello';
    }
}

// 想要实例化一个字符串的错误方法
$obj = new 'IndexController';
```

这里就要用到 PHP 中的一个概念：**可变变量**。

这里的“可变”不是指变量的类型，而是变量本身就是可变的。

直接看示例代码：

```
// 定义两个变量
$dog = '狗狗';
$cat = '猫猫';

// 再定义一个变量，取名为 $dog 变量的名字 
$test = 'dog';
// 会输出什么？
echo $$test;

// 接着，改变 $test 的值为 $cat 变量的名字
$test = 'cat';
// 会输出什么？
echo $$test;
```

结果是：狗狗猫猫

可变变量也就是可以“改变”的变量，我们利用这个特性就可以实例化对象和调用方法：

```
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
```

可变变量是实现路由的基础。

### 匹配规则
最后只需要将 URI 解析成对应的控制器和方法的字符串就可以了，

至少有三种方法可以实现。

#### 方法一：Query 参数

简单的粗暴的 query 参数。

query 参数就是 GET 方法的查询参数：

```
# a 就是 query 参数
http://www.huotublog.com?a=1
```

我们可以直接指定请求的控制器和方法：

```
# c 是控制器名称，a 是方法，其他的是参数
http://www.huotublog.com?c=article&a=show&id=1
```

上面的例子即请求 ArticleController 的 Show 方法，显示 ID=1 的文章。

这种方法简单粗暴，并且十分无脑，**最大的问题是太丑了**，所以直接舍弃这个方案。

好看的路由应该是这样的：

```
# 显示文章详情
http://www.huotublog.com/article/1

# 编辑文章页面
http://www.huotublog.com/article/edit

# 文章列表 ，可适当加入 query 参数
http://www.huotublog.com/article_list?classify=1
```

#### 方法二：文件映射
建立文件映射关系，即路由映射到指定目录下的文件。

比如有一个路由：

```
http://www.huotublog.com/article/show/1
```

我们拿到 "/article/show/1" 这个字符串，以 "/" 为分割符，可以得到对应的值。

```
$uri = '/article/show/1';

$route = explode('/', $uri);
```

然后将 $route[0] 作为控制器的名字，

$route[1] 作为方法的名字，

如果有 $route[2] 则视为路由参数。

遇到特殊的情况：

```
# 网站首页
http://www.huotublog.com

# 列表页
http://www.huotublog.com/article_list
```

则进行特殊处理，当访问首页时，接收到的字符串是 “/”,

即 $route[0] 为空，这种情况就默认请求 IndexController，

如果 $route[1] 是空的（即方法名为空），

则默认请求 index 这个方法。

实现如下：

```
$uri = '/';

$route = array_filter(explode('/', $uri));

// 控制器名称
$controllerName = 'Index';

if(isset($route[0])) {
    $controllerName = ucfirst(strtolower($route[0]));
}

// 方法名称
$method = 'index';

if(isset($route[1])) {
    $method = $route[1];
}

// 路由参数
$param = isset($route[2]) ?? null;
```

这样就解决了特殊路由无法匹配的问题，

这种方法的好处是直接将请求的路由解析成对应的控制器和方法名称，

但是局限性也比较大，所有的路由都是相同规格的，而且不支持路由命名。

如果你要生成一个链接就只能写硬编码了。

最好的方法是给路由命名，然后通过一个函数来生成对应的路由。

#### 方法三：映射关系表

配置映射关系表关联数组)，

键名即路由的名字，值保存了路由的配置，

这样我们就可以实现路由命名的功能了：

```
$routes = [
    'home' => [
        'route' => '/',
        'namespace' => 'App\\Controller\\',
        'controller' => 'IndexController',
        'method' => 'index',
    ],
    'user.home' => [
        'route' => '/user',
        'namespace' => 'App\\Controller\\',
        'controller' => 'UserController',
        'method' => 'index',
    ],
];
```

其中路由配置里包含了 namespace（命名空间），controller（对应控制器的名称），

method（控制器方法名称），route 是匹配的路由规则。

如果我们要引用一个路由地址，例如首页：`$routes['home']['route']`。

如果要匹配一个路由，例如请求为：“/”，则可以遍历 $routes，然后匹配 route 字段是否符合要求。

到这里，思路已经很清晰了，我们现在就可以创建一个 `routes.php` 路由配置表，

但是这样属于硬编码，我们不应该直接用一个数组文件来配置路由，

（这样是面向编程开发了，而框架应该是面向对象开发。）

接下来开始封装类，通过类来执行路由的初始化。

## 路由对象
在框架 module 目录下，创建 Route 文件夹，作为路由模块相关代码存放点。

### Router：路由解释器

接着创建类文件 `Router`，如下：

```
|-- app
|   `-- public
|-- composer.json
|-- firerabbit-engine
|   `-- module
|       `-- Route
|           `-- Router.php
|-- http_server.php
`-- vendor
```

给 Router 加上命名空间：

```
<?php

namespace FireRabbitEngine\Module\Route;

class Router
{
    
}
```

Laravel 的路由配置是比较优雅的，

我打算直接参考 Laravel 路由的调用方法再自己实现一个。

Laravel 的路由配置示例：

```
Route::get('/', 'IndexController@index')->name('index');
```

简单地说就是在 Route 类里面以数组存储路由配置，也就是上面的数组格式，

然后通过调用方法将路由规则写入到数组里面。

### RequestMethod：请求方法常量

我们知道请求方法主要有 GET、POST，

请求方法也是路由的一部分，可以通过限制请求方法来阻止一些不符合规范的请求。

创建一个专门用来保存常量的类 `/module/Route/RequestMethod.php`：

```
<?php
namespace FireRabbitEngine\Module\Route;

class RequestMethod
{
    const GET = 'GET';
    const POST = 'POST';
    const ANY = 'ANY';
}

```

其中，any 意思是两种方法都允许。

### RouteParams：路由对象

每个路由配置都当成一个对象来处理，因此同样封装一个类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：12:48 PM
 **/

namespace FireRabbitEngine\Module\Route;

class RouteParams
{
    /**
     * 路由名称
     * @var string
     */
    public $name;

    /**
     * 路由匹配规则
     * @var string
     */
    public $route;

    /**
     * 命名空间
     * @var string
     */
    public $namespace;

    /**
     * 控制器名称
     * @var string
     */
    public $controller;

    /**
     * 调用的控制器方法名称
     * @var string
     */
    public $action;

    /**
     * 请求方法
     * @var string
     */
    public $method;
    
    public function createResponse($request, $response)
    {
        // 待实现
    }
    
    public function getFullControllerName()
    {
        return $this->namespace . $this->controller;
    }
}
```

这个类里的 createResponse 方法暂时放空，待会实现。

### Controller：控制器

现在还没有控制器，因此在 module 文件夹里面创建一个 Controller，

并创建一个 Controller 基类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:16 PM
 **/


namespace FireRabbitEngine\Module\Controller;


class Controller
{
    protected $request, $response;

    protected $route;

    public function __construct($request, $response, $route)
    {
        $this->request = $request;
        $this->response = $response;
        $this->route = $route;
    }

    public function showMessage($message)
    {
        $this->response->header("Content-Type", "text/html; charset=utf-8");
        $this->response->end($message);
    }
}
```

以后博客的控制器全部继承框架的基类，目前里面只有一个简单的显示消息的方法。

然后为了测试路由，在 app/controller 下面创建两个控制器：IndexController 和 UserController：

```
<?php
namespace App\Controller\Home;

use FireRabbitEngine\Module\Controller\Controller;

class IndexController extends Controller
{
    public function index()
    {
        $this->showMessage('网站首页');
    }

    public function login()
    {
        $this->showMessage('登录页面');
    }
}

<?php

namespace App\controller\Home;

use FireRabbitEngine\Module\Controller\Controller;

class UserController extends Controller
{
    public function index()
    {
        $this->showMessage('用户中心');
    }

    public function loginSubmit()
    {
        $this->showMessage('注册成功！');
    }
}

```

目前的文件结构如下：

```
|-- app
|   |-- controller
|      `-- Home
|          |--- IndexController.php
|          `--- UserController.php
|-- composer.json
|-- firerabbit-engine
|   `-- module
|       |-- Controller
|       |   `-- Controller.php
|       `-- Route
|           |-- RouteParams.php
|           `-- Router.php
`-- http_server.php

```

### Router：生成路由配置

接下来实现路由解释器的配置功能，编辑 Router.php，添加方法：

```
// 保存路由配置的数组
protected static $routes = [];

/**
 * 定义一个 GET 请求路由
 * @param $route
 * @param $controller
 * @throws RouteParamException
 */
public function get($route, $controller)
{
    $this->addRoute(RequestMethod::GET, $route, $controller);
}

/**
 * 定义一个 POST 请求路由
 * @param $route
 * @param $controller
 * @throws RouteParamException
 */
public function post($route, $controller)
{
    $this->addRoute(RequestMethod::POST, $route, $controller);
}

/**
 * 定义一个任意请求皆可的路由
 * @param $route
 * @param $controller
 * @throws RouteParamException
 */
public function any($route, $controller)
{
    $this->addRoute(RequestMethod::ANY, $route, $controller);
}

/**
 * 将路由加入配置数组
 * @param $method
 * @param $route
 * @param $controller
 * @throws RouteParamException
 */
protected function addRoute($method, $route, $controller)
{
    $param = new RouteParams();

    $param->method = $method;
    $param->route = $route;

    // 格式为：控制器@方法名
    $actions = explode('@', $controller);

    // 如果不按照规则设置控制器和方法名则抛出异常
    if (count($actions) != 2) {
        throw new RouteParamException('控制器和方法名称错误，应该为：控制器名称@方法名称');
    }

    $param->controller = $actions[0];
    $param->action = $actions[1];

    self::$routes[] = $param;
}
```

自定义了 get、post、any 方法实现路由配置，

控制器和方法的格式就模仿 Laravel，如：`IndexController@index`，

就是请求 IndexController 控制器的 index 方法。

### RouteParamException：自定义异常

这里我还抛出了一个自定义异常，因此需要在 module/Route 下再创建一个 Exception 文件夹用来保存自定义异常类：

```
<?php

namespace FireRabbitEngine\Module\Route\Exception;

use Exception;

class RouteParamException extends Exception
{

}
```

异常类只要有一个壳就好了。

### web.php：路由配置文件

现在路由添加参数的功能也做好了，接下来就要一个用来配置路由的文件。

在 app 目录下新建一个 route 文件夹，再创建一个 web.php 用来保存页面路由。

> 以后还可以创建 api.php 用来实现接口路由

web.php 内容如下：

```
$router = new \FireRabbitEngine\Module\Route\Router();
$router->get('/', 'App\\Controller\\Home\\IndexController@index');
```

这样显然很不美观，命名空间应该被提取出来。

### Router：增加分组功能

重新编辑 Router.php，添加以下内容：

````
// 分组命名空间
protected $namespace = '';

/**
 * 设置配置参数外部调用方法
 * @param $configs
 * @return $this
 */
public function setConfig($configs)
{
    foreach ($configs as $key => $value) {
        $this->createConfig($key, $value);
    }

    return $this;
}

/**
 * 设置参数
 * @param $key
 * @param $value
 */
protected function createConfig($key, $value)
{
    switch ($key) {
        case 'namespace':
            $this->namespace = $value;
            break;
    }
}

/**
 * 路由分组
 * @param $func
 */
public function group($func)
{
    $func();

    // 执行完成后将参数初始化
    $this->namespace = '';
}

/**
 * 将路由加入配置数组
 * @param $method
 * @param $route
 * @param $controller
 * @throws RouteParamException
 */
protected function addRoute($method, $route, $controller)
{
    $param = new RouteParams();

    $param->method = $method;
    $param->route = $route;

    // 格式为：控制器@方法名
    $actions = explode('@', $controller);

    // 如果不按照规则设置控制器和方法名则抛出异常
    if (count($actions) != 2) {
        throw new RouteParamException('控制器和方法名称错误，应该为：控制器名称@方法名称');
    }

    $param->controller = $actions[0];
    $param->action = $actions[1];
    
    $param->namespace = $this->namespace; // 将命名空间赋值给路由对象

    self::$routes[] = $param;
}
````

增加了一个 group 分组方法，现在就可以把路由按照分组进行配置了，

createConfig 方法以后可以支持更多的分组配置参数。

重新编辑 web.php：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([
    'namespace' => 'App\\Controller\\Home\\',
])->group(function () use ($router) {

    $router->get('/', 'IndexController@index');
    $router->get('/login', 'IndexController@login');
    $router->get('/user', 'UserController@index');
    $router->post('/user/loginSubmit', 'UserController@loginSubmit');

});

```

看起来好多了，配置文件也弄好了。

### RouteParams：执行路由

Router 里只负责解析路由和生成路由配置，执行路由不应该在 Router，

上面定义了 RouteParams 类（路由对象），这个类才是实际的执行者：

```
public function createResponse($request, $response)
{
    // 判断请求方法是否正确
    if ($this->method != RequestMethod::ANY && $request->server['request_method'] != $this->method) {
        (new MethodErrorResponse())->response($request, $response, $this);
        return;
    }

    // 判断方法是否存在
    $controllerName = $this->getFullControllerName();
    if (!class_exists($controllerName)) {
        (new ClassNotFoundResponse())->response($request, $response, $this);
    }

    $action = $this->action;
    // 不存在方法则返回404
    if (!method_exists($controllerName, $action)) {
        (new ActionNotFoundResponse())->response($request, $response, $this);
        return;
    }

    // 实例化类
    $controllerObject = new $controllerName($request, $response, $this->name);

    // ... 以后的中间件写在这里

    // 执行方法
    $controllerObject->$action();
}
```

按照面向对象的编程思想，RouteParams 类即一个实际的路由，这个类暴露 createResponse （创建响应）方法供外部调用。

### RouteResponse：路由响应

这个类里还有一些返回响应的方法，比如找不到方法或者类文件，

因此需要创建一个 module/Route/Response 文件夹用来保存响应相关的类。

创建一个响应类的基类：

```
<?php

namespace FireRabbitEngine\Module\Route\Response;

abstract class RouteResponse
{
    public abstract function response($request, $response, $route);
}

```

然后再创建几个继承该类的响应，例如 NotFoundResponse 响应：

```
<?php

namespace FireRabbitEngine\Module\Route\Response;

class NotFoundResponse extends RouteResponse
{
    public function response($request, $response, $route)
    {
        $response->header("Content-Type", "text/html; charset=utf-8");
        $response->end('不存在页面，404');
    }
}

```

其他的响应类都一样，这里是临时用的，以后要让用户可以自定义错误页面。

### Router：解析路由

路由的执行方法也弄好了，但是现在还没办法匹配路由，

Router 做的事情是匹配用户的请求，判断是否在 $routes 的配置里，

如果找到对应的配置就去执行路由，现在还缺少匹配路由的方法。

回到 Router.php 新增一个解析路由的方法：

```
/**
 * 处理路由
 * @param $request
 * @param $response
 */
public function handle($request, $response)
{
    $uri = $request->server['request_uri'];
    $route = $this->findRoute($uri);

    if ($route == null) {
        (new NotFoundResponse)->response($request, $response, $route);
        return;
    }

    $route->createResponse($request, $response);
}

/**
 * 寻找路由
 * @param $uri
 * @return mixed|null
 */
public function findRoute($request)
{
    $uri = $request->server['request_uri'];

    // 查找规则和方法都匹配的路由
    foreach (self::$routes as $route) {
        if ($route->route == $uri) {
            return $route;
        }
    }

    return null;
}
```

这里的 findRoute 即路由解释器的核心功能，通过循环逐一与配置文件进行匹配，

如果符合要求就返回路由对象，然后执行路由。

现在先简单的实现，如果一个请求路径完全匹配 route 字段即认为匹配。

### 引入路由配置

路由解析功能也完成了，但是路由配置文件 web.php 还没引入，

配置文件应该是在程序启动时就加载到内存中的，直接用 require 引入就可以了，

我把 $routes 定义成静态变量了，它的作用范围是全局的。

编辑根目录下面的 http_server.php，修改如下：

```
<?php
require './vendor/autoload.php';
require './app/route/web.php';

$http = new Swoole\Http\Server('0.0.0.0', 9527);

$router = new \FireRabbitEngine\Module\Route\Router();

$http->on('request', function ($request, $response) use ($router) {

    var_dump('请求URI：' . $request->server['request_uri']);

    $router->handle($request, $response);
});

$http->start();

```

加入 var_dump 方便调试，每次刷新网页的时候都可以在终端看到输出结果，

实际上线的时候要删掉这一句。

这里通过 require 引入 web.php，然后在 `$http->on` 里调用解释器解析路由。

路由模块仅仅只是暴露一个简单的 handle 方法：`$router->handle($request, $response)`

代码十分整洁干净，符合自己的预期要求。

到这一步已经完成了，但还有一个地方要做！

### 类的自动加载

最开始创建的 Controller 并没有被加入 psr-4 自动加载配置里，

编辑根目录下面的 composer.json，修改如下：

```
{
  "require": {},
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "FireRabbitEngine\\Module\\": "firerabbit-engine/module/"
    }
  },
  "repositories": {
    "packagist": {
      "type": "composer",
      "url": "https://mirrors.aliyun.com/composer/"
    }
  }
}
```

新增 App 自动加载路径，这样以后我们创建的控制器或者模型都会被自动加载了。

由于修改了配置，因此需要执行 `composer dump-autoload` 重新生成自动加载文件。

> 每次新创建用来保存类的文件夹时都要重新加载一下

然后就可以打开浏览器，测试路由功能了！

最终文件目录结构：

```
|-- app
|   |-- config
|   |-- controller
|   |   `-- Home
|   |       |-- IndexController.php
|   |       `-- UserController.php
|   |-- public
|   `-- route
|       `-- web.php
|-- composer.json
|-- firerabbit-engine
|   `-- module
|       |-- Controller
|       |   `-- Controller.php
|       `-- Route
|           |-- Exception
|           |   `-- RouteParamException.php
|           |-- RequestMethod.php
|           |-- Response
|           |   |-- ActionNotFoundResponse.php
|           |   |-- ClassNotFoundResponse.php
|           |   |-- MethodErrorResponse.php
|           |   |-- NotFoundResponse.php
|           |   `-- RouteResponse.php
|           |-- RouteParams.php
|           `-- Router.php
|-- http_server.php
|-- test.php
`-- vendor
    |-- autoload.php
    `-- composer
        |-- ClassLoader.php
        |-- LICENSE
        |-- autoload_classmap.php
        |-- autoload_namespaces.php
        |-- autoload_psr4.php
        |-- autoload_real.php
        |-- autoload_static.php
        `-- installed.json
```

简单的路由功能这样就算完成了，但是现在还没办法实现路径参数，下文再补充。

> 每次修改代码都记得要 Ctrl+C 结束 swoole 进程再重新启动，不然修改不会生效。

## 总结
最后是贴上完整的代码：

Router.php 文件是作为“解析/分发”和“配置”路由的类：

```
<?php

namespace FireRabbitEngine\Module\Route;

use FireRabbitEngine\Module\Route\Exception\RouteParamException;
use FireRabbitEngine\Module\Route\Response\NotFoundResponse;

class Router
{
    protected static $routes = [];

    protected $namespace = '';

    /**
     * 处理路由
     * @param $request
     * @param $response
     */
    public function handle($request, $response)
    {
        $uri = $request->server['request_uri'];
        $route = $this->findRoute($uri);

        if ($route == null) {
            (new NotFoundResponse)->response($request, $response, $route);
            return;
        }

        $route->createResponse($request, $response);
    }

    /**
     * 寻找路由
     * @param $uri
     * @return mixed|null
     */
    public function findRoute($request)
    {
        $uri = $request->server['request_uri'];

        // 查找规则和方法都匹配的路由
        foreach (self::$routes as $route) {
            if ($route->route == $uri) {
                return $route;
            }
        }

        return null;
    }

    /**
     * 定义一个 GET 请求路由
     * @param $route
     * @param $controller
     * @throws RouteParamException
     */
    public function get($route, $controller)
    {
        $this->addRoute(RequestMethod::GET, $route, $controller);
    }

    /**
     * 定义一个 POST 请求路由
     * @param $route
     * @param $controller
     * @throws RouteParamException
     */
    public function post($route, $controller)
    {
        $this->addRoute(RequestMethod::POST, $route, $controller);
    }

    /**
     * 定义一个任意请求皆可的路由
     * @param $route
     * @param $controller
     * @throws RouteParamException
     */
    public function any($route, $controller)
    {
        $this->addRoute(RequestMethod::ANY, $route, $controller);
    }

    /**
     * 设置配置参数外部调用方法
     * @param $configs
     * @return $this
     */
    public function setConfig($configs)
    {
        foreach ($configs as $key => $value) {
            $this->createConfig($key, $value);
        }

        return $this;
    }

    /**
     * 设置参数
     * @param $key
     * @param $value
     */
    protected function createConfig($key, $value)
    {
        switch ($key) {
            case 'namespace':
                $this->namespace = $value;
                break;
        }
    }

    /**
     * 路由分组
     * @param $func
     */
    public function group($func)
    {
        $func();

        // 执行完成后将参数初始化
        $this->namespace = '';
    }

    /**
     * 将路由加入配置数组
     * @param $method
     * @param $route
     * @param $controller
     * @throws RouteParamException
     */
    protected function addRoute($method, $route, $controller)
    {
        $param = new RouteParams();

        $param->method = $method;
        $param->route = $route;

        // 格式为：控制器@方法名
        $actions = explode('@', $controller);

        // 如果不按照规则设置控制器和方法名则抛出异常
        if (count($actions) != 2) {
            throw new RouteParamException('控制器和方法名称错误，应该为：控制器名称@方法名称');
        }

        $param->controller = $actions[0];
        $param->action = $actions[1];
        $param->namespace = $this->namespace;

        self::$routes[] = $param;
    }
}
```

RouteParam.php 是作为路由对象，在这里实例化控制器并且执行：

```
<?php

namespace FireRabbitEngine\Module\Route;

use FireRabbitEngine\Module\Route\Response\ActionNotFoundResponse;
use FireRabbitEngine\Module\Route\Response\MethodErrorResponse;
use FireRabbitEngine\Module\Route\Response\ClassNotFoundResponse;

class RouteParams
{
    /**
     * 路由名称
     * @var string
     */
    public $name;

    /**
     * 路由匹配规则
     * @var string
     */
    public $route;

    /**
     * 命名空间
     * @var string
     */
    public $namespace;

    /**
     * 控制器名称
     * @var string
     */
    public $controller;

    /**
     * 调用的控制器方法名称
     * @var string
     */
    public $action;

    /**
     * 请求方法
     * @var string
     */
    public $method;

    public function createResponse($request, $response)
    {
        // 判断请求方法是否正确
        if ($this->method != RequestMethod::ANY && $request->server['request_method'] != $this->method) {
            (new MethodErrorResponse())->response($request, $response, $this);
            return;
        }

        // 判断方法是否存在
        $controllerName = $this->getFullControllerName();
        if (!class_exists($controllerName)) {
            (new ClassNotFoundResponse())->response($request, $response, $this);
        }


        $action = $this->action;
        // 不存在方法则返回404
        if (!method_exists($controllerName, $action)) {
            (new ActionNotFoundResponse())->response($request, $response, $this);
            return;
        }

        // 实例化类
        $controllerObject = new $controllerName($request, $response, $this->name);

        // ... 以后的中间件写在这里

        // 执行方法
        $controllerObject->$action();
    }

    public function getFullControllerName()
    {
        return $this->namespace . $this->controller;
    }
}

```

而 web.php 则是全局的路由配置文件，实现了用类配置路由，而不是用纯数组：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([
    'namespace' => 'App\\Controller\\Home\\',
])->group(function () use ($router) {

    $router->get('/', 'IndexController@index');
    $router->get('/login', 'IndexController@login');
    $router->get('/user', 'UserController@index');
    $router->post('/user/loginSubmit', 'UserController@loginSubmit');

});

```

以上，简单路由就完成了。
