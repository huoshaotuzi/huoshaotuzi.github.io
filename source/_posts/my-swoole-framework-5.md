---
title: 从零开始搭建自己的Swoole框架（五）为路由模块添加解析路径参数功能
date: 2021-02-09 18:46:43
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 为路由模块添加解析路径参数功能。

---

## 前言
上文已经实现简单路由，但还没办法解析路由参数。

例如：

```
http://www.huotublog.com/article/1
```

包含了路径参数，即 article 后面的 1。

本章将为路由模块添加解析路径参数的功能。

另外，Query 参数不叫路由参数，例如：

```
http://www.huotublog.com/article/1?a=222
```

这里的 a 是 query 参数，不是路径参数。


## 匹配原理
假设路径参数可以有无限多个，在这样无法确定数量情况，

无法使用一般的 if-else 来获取。

这个时候就需要用到**正则表达式**了。

正则表达式可以按照某种规则来匹配特殊的字符串，包括替换字符串。

```
<?php

$url = '/article/123';
$pattern = '/article\/(\d+)/';
preg_match_all($pattern, $url, $res);

var_dump($res);
```

以上示例输出结果：123

正则表达式不仅可以匹配特定的规则，而且只要加上括号就可以把匹配规则视为一个变量单独取出来。

如果不了解正则表达式可以自行搜索，这里不扩展。

因为我的路由配置参考 Laravel，所以要实现 Laravel 一样的效果：

```
/article/{id}
/article/{id}/edit
```

用花括号包起来的部分视为变量，即有如下匹配规则：

```
/article/{id}       =>  /article/1
/article/{id}/edit  =>  /article/1/edit
```

还要获取路径上面的变量，也就是说 `{id}` 部分视为一个路径参数。

既要匹配路由规则，又要获得路径参数，一共需要两步才能实现这样的效果。

第一步是把 `/article/{id}` 转化为一个正则表达式，

第二步是拿上一步得到的正则表达式去匹配 URI。

路径参数不只是数字，也有可能是别的什么，甚至是中文都可以当做路径参数。

```
# 这是一个正确的 URI
http://www.huotublog.com/article/我的swoole框架
```

只不过你在浏览器输入上面的地址，然后再复制下来，会被 urlencode。

结果就会变成这样：

```
https://huotublog.com/article/%E6%88%91%E7%9A%84swoole%E6%A1%86%E6%9E%B6
```

所以如果我们只匹配数字是不行的，而是要用全匹配 `.`。

> .（点）在正则表达式里是匹配除了换行之外所有字符串

例如有一个包括两个路径参数的路由：

```
$url = '/article/{id}/show/{classify}';
$pattern = '/.*?\/(\{.*?\})/';

preg_match_all($pattern, $url, $result);

$transform = str_replace($result[1], '(.*?)', $url);

var_dump($transform);
```

这里的规则 `.*?` 是三个正则表达式符号，意思是说尽可能多的匹配字符串。

加上括号就可以取出匹配的字符串了，匹配到的结果存入 $result 变量，

也就是说一共会得到两个值：`{id}`、`{classify}`

然后用 PHP 的 `str_replace` 函数进行简单的替换：

```
string(25) "/article/(.*?)/show/(.*?)"
```

> 如果不懂正则表达式，直接套用 (.*?) 即可，既简单又粗暴

替换后的字符串还不是表达式，只是加入了正则表达式符号而已。

> 就跟 json 字符串不是 json 对象一个道理，还需要转换一下才能变成表达式

斜杠是正则表达式里比较敏感的字符，过滤掉干扰字符串，修改为完整的正则表达式：

```
$transform = '/article/(.*?)/show/(.*?)'
$pattern = '/' . str_replace('/', '\/', $transform) . '$/';

var_dump($pattern);
```

> rtrim 去掉右侧的斜杠，防止匹配不到

输出结果：

```
string(32) "/\/article\/(.*?)\/show\/(.*?)$/"
```

转换得到的正则表达式，用它就可以匹配出路由规则中的变量：

```
// 用户请求的 URI
$requestUri = '/article/123/show/abc';

// 通过匹配得到参数
preg_match_all('/\/article\/(.*?)\/show\/(.*?)$/', $requestUri, $params);

var_dump($params);

```

输出结果：

```
array(3) {
  [0]=>
  array(1) {
    [0]=>
    string(21) "/article/123/show/abc"
  }
  [1]=>
  array(1) {
    [0]=>
    string(3) "123"
  }
  [2]=>
  array(1) {
    [0]=>
    string(3) "abc"
  }
}
```

路由参数解析原理已经搞清楚了，接下来就在路由解释器里实现这个功能。

## 实现解析路由功能

有一个特殊的路由，即 `/`（首页的地址），

所有的路由都带有 `/`，导致无法正确匹配，因此这个路由需要单独判断。

### RouteParams：解析参数传给控制器

RouteParams 增加一个新的变量用来保存替换后的正则表达式：

```
/**
 * 正则表达式匹配规则
 * @var string
 */
public $pattern;
```

修改

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

    $uri = rtrim($request->server['request_uri'], '/');

    if ($uri != '') {
        // 匹配路径参数
        preg_match_all($this->pattern, $uri, $result);

        if (count($result[0]) != 0) {

            $params = [];

            for ($i = 1; $i < count($result); $i++) {
                $params[] = $result[$i][0];
            }

            $controllerObject->setRouteParams($params);
        }
    }

    // ... 以后的中间件写在这里

    // 执行方法
    $controllerObject->$action();
}
```

在 RouteParams 创建出控制器对象的时候解析出路径参数，把路径参数传给控制器。

### Controller：接收路径参数

控制器还没有 setRouteParams 方法，修改控制器的基类：

```
<?php

namespace FireRabbitEngine\Module\Controller;

class Controller
{
    protected $request, $response, $route, $routeParams;

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

    public function setRouteParams($params)
    {
        $this->routeParams = $params;
    }
}
```

如此一来控制器也能够获取到路径参数了。

### Router：赋值匹配规则
Router 在生成配置的时候，可以同时生成路由的正则匹配规则。

修改生成路由配置的方法：

```
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
    $param->pattern = $this->getPattern($route);

    self::$routes[] = $param;
}

protected function getPattern($route)
{
    if ($route == '/') {
        return '';
    }

    $pattern = '/.*?\/(\{.*?\})/';

    preg_match_all($pattern, $route, $result);

    // 如果第一个数组的个数为0，表示没有匹配到路径参数
    if (count($result[0]) == 0) {
        return '/' . str_replace('/', '\/', $route) . '/';
    }

    $transform = str_replace($result[1], '(.*?)', $route);
    $transform = '/' . str_replace('/', '\/', $transform) . '$/';

    return $transform;
}
```

getPattern 方法将路由中自定义的规则解析成对应的正则表达式，

然后赋值给 RouteParams 对象。

### Router：修改匹配规则

现在已经可以获取到正则表达式了，接下来修改路由匹配规则。

将之前 Router 直接匹配的方法改成正则匹配，

PHP 有一个用来测试正则匹配结果的函数：`preg_match`。

没有匹配到正则表达式的字符，这个函数会返回 0，以此来判断是否符合路由规则。

修改 findRoute 方法：

```
 /**
 * 寻找路由
 * @param $request
 * @return mixed|null
 */
public function findRoute($request)
{
    $uri = rtrim($request->server['request_uri'], '/');

    foreach (self::$routes as $route) {

        if (empty($uri)) {

            if ($route->route != '/') {
                continue;
            }

            return $route;

        } else if ($route->pattern != '' && preg_match($route->pattern, $uri) != 0) {

            return $route;
        }
    }

    return null;
}
```

路由解释器的解析功能也改完了，接下来创建一个新的控制器来测试是否能获取到参数。

### 测试结果

在 app/controller/Home 创建 ArticleController：

```
<?php

namespace App\controller\Home;

use FireRabbitEngine\Module\Controller\Controller;

class ArticleController extends Controller
{
    public function show()
    {
        $this->showMessage(json_encode($this->routeParams));
    }
}
```

然后编辑 web.php 添加一条路由配置：

```
$router->get('/article/{id}', 'ArticleController@show');
```

然后访问地址：http://firerabbit-engine.ht/article/1

可以看到输出了一个数组，且只有一个值：1。

## 防止路由冲突
由于匹配规则具有先后级，就是书写的顺序。

定义具有歧义的路由时要注意先后顺序：

```
$router->get('/user/{id}', 'UserController@test');
$router->get('/user/home', 'UserController@home');
```

上面将 `/` 后面的参数视为 id 变量，

所以会匹配到第一个路由，第二个路由就被忽略了。

在配置路由的时候要注意先后顺序，最好不要定义具有歧义的路由。

## 防止路由重名
在 Laravel 里面可以有相同匹配规则但是请求方法不同的路由，

例如：

```
$router->get('/user/home', 'UserController@homeGet');
$router->post('/user/home', 'UserController@homePost');
```

我设计的路由模块不支持这个功能，不能定义同名路由。

## 可能存在的问题
路由解析是框架里面最核心且使用频率最高的一个部分，

正则表达式的性能可能会比较低。

有一个优化的方法就是增加路由缓存，将匹配成功的路由记录下来，

下次访问先检测这条记录是不是在缓存里，如果是的话就不去正则匹配而是直接从缓存取出记录过的路由对象。

现在还不需要考虑到性能优化，如果到时候真的有问题了再优化也不迟。
