---
title: 从零开始搭建自己的Swoole框架（七）路由动态注入参数
date: 2021-02-11 22:33:44
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 路由动态传参给控制器方法。

---

## 前言
已经写到第七章了，竟然还是在写路由 = =

今天就来实现路由给方法动态传参的功能。

动态传参就是说路由定义的规则：`/article/{id}`，会自动注入到 ArticleController 的 show 方法。

示例：

```
// 定义一个路由
$router->get('/article/{id}', 'ArticleController@show')->name('article.show');

// 有了上面的路由，用户访问地址：/article/1 就会自动调用ArticleController的show方法
// 在前面Controller定义了一个setRouteParams方法把路由参数传给控制器
// 控制器内部就存储了一个一维数组：[1]
// 但是这样调用起来很麻烦，尤其是参数比较多的时候容易造成混乱
// 最优雅的方式就是Laravel的路由参数自动注入
// 只要在ArticleController定义一个show方法，接收一个id参数，而路由参数会自动注入到这个方法

public function show($id) {
    var_dump($id);
}

// 如果是多个参数的呢？也是一样的。
$router->get('/article/{id}/edit/{classify}', 'ArticleController@test')->name('article.test');

public function test($id, $classify) {
    var_dump($id, $classify);
}

```

## 原理解析
这里涉及到一个函数动态传参的问题，

“如何将数组元素的值，依次作为参数传给函数？”

### 可变参数

PHP 支持函数不定参数，就是用三个点加上参数名即视为可变参数：

```
// 支持可变参数的函数
function test(...$args) {
    var_dump($args);
}

// 测试传入不同的参数
test('a');
test('a', 'b');
test('a', 'b', 'c');
```

输出结果：

```
array(1) {
  [0]=>
  string(1) "a"
}
array(2) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
}
array(3) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
  [2]=>
  string(1) "c"
}
```

也就是说，在函数中可以将可变参数当成数组来使用，

那是不是说明我们传一个数组进去，就会被当成多个参数了呢？

```
// 支持可变参数的函数
function test(...$args) {
    var_dump($args);
}

// 测试传入不同的参数
$params = ['aa', 'bb', 'cc'];
test($params);

```

上面的代码，我们传入一个数组，按照设想的情况，

数组中的三个值应该会作为三个参数传入 test 方法，

假设的情况是这样：

```
$params = ['aa', 'bb', 'cc'];

// 想象中的样子
test($params); => test('aa', 'bb', 'cc');
```

但实际的打印结果却是：

```
array(1) {
  [0]=>
  array(3) {
    [0]=>
    string(2) "aa"
    [1]=>
    string(2) "bb"
    [2]=>
    string(2) "cc"
  }
}
```

也就是说，数组只是被当成了一个参数传给 test 方法，

其实不难想像，如果数组会被解析成多个参数，

那可变参数不是不能传入数组作为参数了吗？

### 函数的动态调用
通常情况下，没办法实现将数组依次当做函数的参数。

而要用到 PHP 内置的一个方法：`call_user_func_array`

> 注意！有一个类似的方法：call_user_func，不要输错！

这个方法可以动态调用函数，它可以接收两个数组作为参数：

```
call_user_func_array([调用对象，方法名称]，[参数1，参数2，参数3...]);
```

第一个数组，第一个元素是调用的对象，即类的实例化，第二个参数是一个字符串即要调用对象的方法名称。

第二个数组即是要依次传入方法的参数。

示例代码：

```
class  Test
{
    public function show($name)
    {
        var_dump($name);
    }

    public function playGame($name, $game)
    {
        $text = $name . '在玩' . $game;
        var_dump($text);
    }
}

$test = new Test();

call_user_func_array([$test, 'show'], ['小白']);
call_user_func_array([$test, 'playGame'], ['小白', '俄罗斯方块']);

```

输出结果：

```
string(6) "小白"
string(27) "小白在玩俄罗斯方块"
```

## RouteParams：动态传参

动态传参的原理已经弄明白了，接下来只要改造原来的解析方法就可以：

```
/**
 * 执行路由
 * @param $request
 * @param $response
 */
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
    $this->uri = rtrim($request->server['request_uri'], '/');

    $params = $this->getRouteParams();

    // ... 以后的中间件写在这里

    // 执行方法时，路径参数作为方法的参数
    call_user_func_array([$controllerObject, $action], $params);
}

/**
 * 获取路由参数
 * @return array
 */
public function getRouteParams()
{
    if ($this->uri == '') {
        return [];
    }

    preg_match_all($this->pattern, $this->uri, $result);

    if (count($result[0]) == 0) {
        return [];
    }

    $params = [];

    for ($i = 1; $i < count($result); $i++) {
        $params[] = $result[$i][0];
    }

    return $params;
}
```

RouteParams 将获取路由参数的方法抽离出来，

并且移除了 Controller 的 setRouteParams 方法，改用动态注入参数。

这样路由的参数注入也完成了！

## 测试结果
编辑 web.php 添加路由：

```
$router->get('/article/{id}/edit/{classify}', 'ArticleController@test')->name('article.test');
$router->get('/article/{id}', 'ArticleController@show')->name('article.show');
```

编辑 ArticleController 添加方法：

```
<?php

namespace App\controller\Home;

use FireRabbitEngine\Module\Controller\Controller;

class ArticleController extends Controller
{
    public function show($id)
    {
        var_dump($id);
        $this->showMessage('ok');
    }

    public function test($id, $classify)
    {
        var_dump($id,$classify);

        $this->showMessage('ok');
    }
}
```

测试结果均能正确打印出注入的参数。

另外，发现到一个新问题就是路由的顺序，由于是使用正则匹配的，只要修改声明路由的顺序：

```
/article/{id}
/article/{id}/edit/{classify}
```

结果访问：http://firerabbit-engine.ht/article/1/edit/aa

就会优先匹配到上面的正则，而 id 参数则是：1/edit/aa

只能人为避免因为书写顺序而产生奇奇怪怪的问题了，在编辑路由的时候优先将匹配规则较多的写在上面就不会弄错了。
