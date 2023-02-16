---
title: FireRabbit-Engine 实战 从零搭建个人博客（三）登录与注册
date: 2021-02-19 23:16:35
tags:
- FireRabbitEngine

categories: 项目实战

description: 实现登录与注册功能。

---
## 视图文件
在 app 下新建一个文件夹 view 用来保存视图模板，

再创建一个 storage，并继续在 storage 下创建 view_cache 用来保存编译后的视图文件。

然后修改 app.php：

```
Constant::VIEW_CONFIG => [
    'path' => __DIR__ . '/../view',
    'cache_path' => __DIR__ . '/../storage/view_cache',
],
```

此处的文件路径即上述创建的文件夹。

## 视图母版
母版即所有页面共用的代码，比如每个页面都有顶部导航栏跟底部栏，

只是中间的部分不同，因此只要把内容单独提取出来，顶部和底部的结构可以复用。

在 view 下新建 layout 用来保存母版，同时创建 app.blade.php：

```
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>火兔博客</title>
</head>
<body>
    <table>
        <tr>
            <td><a href="#">导航栏</a></td>
            <td><a href="#">导航栏</a></td>
            <td><a href="#">导航栏</a></td>
            <td><a href="#">导航栏</a></td>
        </tr>
    </table>
    
    @yield('content')

    <hr/>
    <div>
        <p align="center">火兔博客©2021</p>
    </div>
</body>
</html>
```

中间的 `yield('content')` 即抽取出来的内容页。

## 登录/注册页面
添加登录注册的路由：

```
<?php

$router = new \FireRabbit\Engine\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Http\\Controller\\',

])->group(function () use ($router) {

    $router->get('/login', 'IndexController@login')->name('login');
    $router->get('/register', 'IndexController@register')->name('register');

});

return $router;
```

接着修改控制器：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/19
 * Time：12:39
 **/

namespace App\Http\Controller;

use FireRabbit\Engine\Controller\Controller;

class IndexController extends Controller
{
    public function login()
    {
        $this->show('login');
    }

    public function register()
    {
        $this->show('register');
    }
}
```

接着创建对应的 blade 模板，login.blade.php：

……