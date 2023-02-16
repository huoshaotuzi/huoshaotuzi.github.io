---
title: 从零开始搭建自己的Swoole框架（六）为路由模块添加命名功能
date: 2021-02-10 11:08:08
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 为路由模块添加命名功能。

---
## 前言
参考 Laravel 的路由，希望我的框架可以跟 Laravel 一样给路由命名，

```
$router->get('/home', 'IndexController@index')->name('index');
```

然后提供一个全局函数 `route` 生成链接。

比如定义一个路由名 index，路由规则是：“/home”，

然后通过 `route('index')` 会生成 “/home” 链接。

其实就是从路由配置表里找到对应名字的路由然后取出路由规则而已。

对于需要解析路由参数的就不能只是简单的返回字符串了，

比如显示文章详情：路由命名为：`article.show`，对应的路由规则：`/article/{id}`，

通过全局函数生成文章详情的链接 `route('article.show', ['id'=>1])` 返回：“/article/1”。

原理与解析路由参数一样，即正则匹配进行替换。

## 链式调用
只要方法返回类本身就可以实现链式调用了。

```
<?php

class Test
{
    protected $word = '';

    public function say($word)
    {
        $this->word .= $word . PHP_EOL;

        return $this;
    }

    public function showResult()
    {
        echo $this->word;
    }
}

$test = new Test();

$test->say('hello')->say('world')->showResult();
```

## Router：添加命名功能
其实这个地方我有点疑惑，为什么 name 方法是写在最后面，

get 方法已经将路由参数写入到配置里面了，写在链式调用最后的方法如何修改前面设定的值？

```
$router->get('/home', 'IndexController@index')->name('index');
```

结果灵鸡一动！突然想到一种奇妙的方法来实现“后调改前值”，

就是加入一个 lastIndex，在插入路由配置的时候，计算这个插入值所在数组的索引并保存下来。

如果要修改最后一个调用的配置，就可以从 lastIndex 获取到了。

修改 Router 原来生成路由配置的方法，

为了链式调用必须返回类本身，同时加入 lastIndex 变量：

```
/**
 * 保存最后一个操作的路由对象索引
 * @var null
 */
private $lastHandleRouteIndex = null;

/**
 * 定义一个 GET 请求路由
 * @param $route
 * @param $controller
 * @return Router
 * @throws RouteParamException
 */
public function get($route, $controller)
{
    return $this->addRoute(RequestMethod::GET, $route, $controller);
}

/**
 * 定义一个 POST 请求路由
 * @param $route
 * @param $controller
 * @return Router
 * @throws RouteParamException
 */
public function post($route, $controller)
{
    return $this->addRoute(RequestMethod::POST, $route, $controller);
}

/**
 * 定义一个任意请求皆可的路由
 * @param $route
 * @param $controller
 * @return Router
 * @throws RouteParamException
 */
public function any($route, $controller)
{
    return $this->addRoute(RequestMethod::ANY, $route, $controller);
}

/**
 * 将路由加入配置数组
 * @param $method
 * @param $route
 * @param $controller
 * @return Router
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

    // 新加入行
    $this->lastHandleRouteIndex = count(self::$routes) - 1;

    return $this;
}
```

索引一开始是空的，所以赋值为 null，在插入路由配置的时候，

通过 `count(self::$routes) - 1` 计算当前新插入值的索引。

接下来新增一个 name 方法，路由名相当于唯一的 ID，因此不允许重复：

```
/**
 * 给路由命名
 * @param $routeName
 * @throws RouteParamException
 */
public function name($routeName)
{
    if ($this->lastHandleRouteIndex === null) {
        return;
    }

    // 判断路由是否存在同名
    foreach (self::$routes as $route) {
        if ($route->name == $routeName) {
            throw  new RouteParamException('路由名称重复[' . $routeName . ']');
        }
    }

    $route = self::$routes[$this->lastHandleRouteIndex];
    $route->name = $routeName;

    self::$routes[$this->lastHandleRouteIndex] = $route;
}
```

通过 lastIndex 找到最后一个修改的路由配置，然后给它赋值 name 属性。

### 避坑指南：诡异的 null 值

> 这里有个神坑！

**PHP 对 null 值的判定很诡异**。

在 PHP 中，`0 == null` 的结果为 true。

因此必须使用三等号：`===` 来判定包括类型也必须完全一致。

```
if ($this->lastHandleRouteIndex === null) {
    return;
}
```

上面的 if 本来是为了判定是否有路由配置，如果还没配置路由就调用 name 就直接跳过，

第一次操作时，lastIndex 的索引是 0，如果不使用三等号第一个配置的路由就会被跳过了。

> 在使用判空操作，如：empty、isset 时必须注意 0、null、false 这几个值

再来个有趣的小测试：

```
var_dump(0 == false);
var_dump(0 == null);
var_dump(0 == '');
```

以上三个，全部输出：`bool(true)`，是不是惊到了！

在某些场合双等号判定会造成失误，应该改用三等号。

## 测试路由名称
编辑 web.php 修改路由配置：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Controller\\Home\\',

])->group(function () use ($router) {

    $router->get('/', 'IndexController@index')->name('index');
    $router->get('/login', 'IndexController@login')->name('login');
    $router->get('/user', 'UserController@index')->name('user.index');
    $router->post('/user/loginSubmit', 'UserController@loginSubmit')->name('login.submit');
    $router->get('/article/{id}', 'ArticleController@show')->name('article.show');
});

```

然后在 Router 执行 `var_dump(self::$routes)` 即可看到所有路由，name 字段已经变成设置的值了。

## RouteParams：生成链接
RouteParams 保存了路由的所有配置，接下来要让它能根据路由规则生成对应的链接。

PHP 提供了一个正则替换函数：

```
<?php

$uri = '/article/{id}/classify/{classify}';

$pattern = '/{id}/';

$res = preg_replace($pattern, '123', $uri);

var_dump($res);
```

上面的示例代码通过正则替换将自定义规则的 id 替换成数字 123。

接着，继续看：

```
<?php

$uri = '/article/{id}/classify/{classify}';

$patterns = ['/{id}/', '/{classify}/', ];
$replacements = [123, 456];

$res = preg_replace($patterns, $replacements, $uri);

var_dump($res);
```

输出结果：

```
string(25) "/article/123/classify/456"
```

通过传入数组参数即可实现批量替换，输出的字符串即链接地址。

原理已经搞清楚了，开始实际上手，修改 RouteParams，创建一个空方法：

```
public function createLink($params)
{

}
```

接着创建一个用来生成 query 参数的私有方法：

```
 /**
 * 构建query参数的地址
 * @param $route
 * @param $query
 * @return string
 */
private function buildQuery($route, $query)
{
    if (empty($query)) {
        return $route;
    }

    return $route . '?' . http_build_query($query);
}
```

如果没有参数就直接返回，不然就返回包含 query 参数的路由地址。

现在可以开始编写 createLink 的方法体了，

因为斜杠是正则符号，所以首页的路由“/”要单独返回：

```
public function createLink($params)
{
    if ($this->route == '/') {
        return $this->buildQuery($this->route, $params);
    }
}
```

接着判断是否包含路由参数，没有参数的也直接返回：

```
public function createLink($params)
{
    if ($this->route == '/') {
        return $this->buildQuery($this->route, $params);
    }
    
    // 取出自定义规则
    $pattern = '/.*?\/(\{.*?\})/';
    preg_match_all($pattern, $this->route, $result);

    // 如果匹配不到自定义参数则直接返回路由规则
    if (count($result[0]) == 0) {
        return $this->buildQuery($this->route, $params);
    }
}
```

我希望实现全局函数 `route` 传入指定的参数就可以替换掉路由自定义的参数，生成替换了值的地址：

```
// 通过调用全局函数生成路由
route('article.show', ['id' => 1])    => 返回结果：/article/1

// 也就是说，路由规则需要实现如下变换效果
/article/{id}   => 从 route 函数传入的数组中取出 id 变量，然后再替换掉 {id}
```

这一步比较复杂，要先获取到匹配的规则：`{id}`，以及花括号里面的参数名 `id`：

```
// 获取自定义匹配规则
$patterns = [];
$paramNames = [];

for ($i = 1, $count = count($result[1]); $i <= $count; $i++) {

    // 此处得到自定义规则的参数，如：{id}
    $rule = $result[1][$i - 1];

    /**
     * 花括号是正则表达式的符号，必须加上反斜杠转转义
     * 最后，在前后加上斜杠才是一个完整的正则表达式
     */
    $patterns[] = '/' . str_replace(['{', '}'], ['\{', '\}'], $rule) . '/';

    /**
     * 截取中间的变量名
     */
    $paramNames[] = substr($rule, 1, strlen($rule) - 2);
}
```

一共得到了两个数组，`$patterns` 是正则替换表达式，而 `$paramNames` 是自定义路由参数的名字。

接下来把自定义路由中的 `{id}` 替换成  `$params` 传入的变量值，

因为传入的 `$params` 是一个关联数组，而 PHP 的正则替换函数是一维数组，

所以要把 `$params` 中对应的参数提取出来，

从 `$paramNames` 获取到的路径参名称作为键，取 `$params` 传进来的值：

```
/**
 * 生成要替换的数组结构，根据规则与传入的参数一一对应
 * 假设路由规则是 /article/{id}
 * 那么$params传入的参数就应该是：['id'=>1]
 */
$replacements = [];
foreach ($paramNames as $key) {

    if (!isset($params[$key])) {
        throw new RouteParamException('路由缺失参数[' . $key . ']');
    }

    $replacements[] = $params[$key];

    // 移除路径参数
    unset($params[$key]);
}
```

如果传入的数组不符合规则就抛出一个 `RouteParamException` 异常（这个异常是之前定义的）。

生成替换的值后，就可以把这个键从 `$params` 里面移除了，因为最后我们要生成 query 参数，

而路径参数已经被使用了，如果不去掉，最后就会变成这样：

```
/article/1?id=1
```

所以这一步顺便使用 `unset` 方法把已经用过的数组元素去掉。

接下来就可以使用正则替换将规则中的 `{id}` 替换成对应的值了：

```
// 然后将替换值根据规则进行置换
$res = preg_replace($patterns, $replacements, $this->route);
```

最后再构建 query 参数就大功告成，完整代码：

```
/**
 * 生成链接
 * @param $params
 * @return string
 * @throws RouteParamException
 */
public function createLink($params)
{
    if ($this->route == '/') {
        return $this->buildQuery($this->route, $params);
    }

    // 取出自定义规则
    $pattern = '/.*?\/(\{.*?\})/';
    preg_match_all($pattern, $this->route, $result);

    // 如果匹配不到自定义参数则直接返回路由规则
    if (count($result) == 0) {
        return $this->buildQuery($this->route, $params);
    }

    // 获取自定义匹配规则
    $patterns = [];
    $paramNames = [];

    for ($i = 1, $count = count($result[1]); $i <= $count; $i++) {

        // 此处得到自定义规则的参数，如：{id}
        $rule = $result[1][$i - 1];

        /**
         * 花括号是正则表达式的符号，必须加上反斜杠转转义
         * 最后，在前后加上斜杠才是一个完整的正则表达式
         */
        $patterns[] = '/' . str_replace(['{', '}'], ['\{', '\}'], $rule) . '/';

        /**
         * 截取中间的变量名
         */
        $paramNames[] = substr($rule, 1, strlen($rule) - 2);
    }

    /**
     * 生成要替换的数组结构，根据规则与传入的参数一一对应
     * 假设路由规则是 /article/{id}
     * 那么$params传入的参数就应该是：['id'=>1]
     */
    $replacements = [];
    foreach ($paramNames as $key) {

        if (!isset($params[$key])) {
            throw new RouteParamException('路由缺失参数[' . $key . ']');
        }

        $replacements[] = $params[$key];

        // 移除路径参数
        unset($params[$key]);
    }

    // 然后将替换值根据规则进行置换
    $res = preg_replace($patterns, $replacements, $this->route);

    return $this->buildQuery($res, $params);
}

/**
 * 构建query参数的地址
 * @param $route
 * @param $query
 * @return string
 */
private function buildQuery($route, $query)
{
    if (empty($query)) {
        return $route;
    }

    return $route . '?' . http_build_query($query);
}
```

## Router：查找路由名
路由的名称是唯一的，每一个名称对应一个路由，相当于路由的唯一 ID。

只要根据这个 ID 就可以找到对应的路由。

Router 方法应该暴露一个可供外部调用的查找路由名方法，修改 Router 添加如下内容：

```
/**
 * 根据路由名称寻找路由
 * @param $routeName
 * @return mixed|null
 */
public function findRouteFromName($routeName)
{
    foreach (self::$routes as $route) {
        if ($route->name == $routeName) {
            return $route;
        }
    }

    return null;
}
```

方法倒是很简单，只要循环找到对应名字的路由配置就行了。

> 查找数组效率最高的方法不是遍历而是通过数组的下标，我考虑过将路由分为已命名路由和未命名路由，未命名路由就是一个普通的索引数组，而已命名路由则是关联数组，键即路由的名字，这样查找路由时优先从已命名路由通过下标查询，如果没有再去遍历未命名路由，但是如果改成这样，Router 匹配路由规则时就要再进行一次数组合并，反而会降低路由解析的性能，因此舍弃了这种想法

## route：全局函数
只要 `new` 一个 Router 对象就可以调用 `findRouteFromName` 找到对应的路由，

然后再调用路由的 `createLink` 生成路由链接。

现在缺少一个全局函数：`route`。

同样借助 composer 的自动加载功能，修改 composer.jsp，添加加载规则：

```
{
  "require": {},
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "FireRabbitEngine\\Module\\": "firerabbit-engine/module/"
    },
    "files": [
      "firerabbit-engine/common/function.php"
    ]
  },
  "repositories": {
    "packagist": {
      "type": "composer",
      "url": "https://mirrors.aliyun.com/composer/"
    }
  }
}
```

在 psr-4 下面增加了一个字段 files，而这个文件即框架的通用函数库。

创建 `firerabbit-engine/common/function.php` 文件：

```
<?php
/**
 * 根据路由名称生成对应路由
 * @param $routeName
 * @param array $params
 * @return mixed|null
 * @throws \FireRabbitEngine\Module\Route\Exception\RouteNotFoundException
 */
function route($routeName, $params = [])
{
    $router = new \FireRabbitEngine\Module\Route\Router();

    $route = $router->findRouteFromName($routeName);

    if ($route == null) {
        throw new \FireRabbitEngine\Module\Route\Exception\RouteNotFoundException('不存在路由[' . $routeName . ']');
    }

    return $route->createLink($params);
}
```

这里我不使用 `function_exists` 来判断方法是否存在，即使真的冲突了就直接报错，

实际上使用了这个方法来判断也没任何意义，重名了就不定义这个函数？

那连错在哪都不知道，为何要屏蔽可能报错的信息？我是百思不得其解。

如果遇到重名的函数，在我们执行 `php http_server.php` 启动程序的时候就会报错了，完全不用担心。

如果我们引入了第三方的包，其他人也定义了 route 函数那样才会产生麻烦。

一般的第三方包也不会去定义全局函数，而是封装成类进行调用，

函数是面向过程开发，也不符合开发包的理念，所以这一点完全不用担心。

因为我开发的是框架，只有框架才会定义全局函数，我的框架里也不可能引入其他框架。

## 测试结果
三种不同类型的路由：

```
$router->get('/login', 'IndexController@login')->name('login');
$router->get('/article/{id}', 'ArticleController@show')->name('article.show');
$router->get('/test/{id}/user/{name}/goods/{qq}', 'TestController@test')->name('test');
```

测试代码：

```
$link = route('test', ['id' => 1, 'name' => '哈哈怪', 'qq' => 'okok']);
$link2 = route('article.show', ['id' => 123]);
$link3 = route('login');

var_dump($link, $link2, $link3);
```

输出结果：

```
string(33) "/test/1/user/哈哈怪/goods/okok"
string(12) "/article/123"
string(6) "/login"
```

看上去没有问题了！
