---
title: 从零开始搭建自己的Swoole框架（九）视图blade模板
date: 2021-02-13 21:50:05
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 视图模块使用 blade 模板。

---

## 前言
路由模块终于告一段落了，虽然完成了但还没有经过严格测试，

因此可能会存在一些问题，具体问题就等接下来的开发过程发现就好了。

## 视图模块
由于我的框架不是专门做 API 的，也不是微服务架构，而是单体应用，

也就是说会出现 HTML 代码跟 PHP 代码混合在一块的视图文件，

直接用原生的 PHP 来写 HTML 页面肯定不是好方法，而模板引擎比较好用的就是 blade 模板了。

## 安装 blade 模板
模板引擎的开发成本太高了，因此我打算直接用别人写好的。

使用 composer 命令 `composer require xiaoler/blade`，

安装完成后 composer.json 的 require 字段即可看到刚才的安装包：

```
{
  "require": {
    "xiaoler/blade": "^5.4"
  },
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

## 模板引擎的配置
模板引擎在第一次运行时，会根据模板创建出编译后的 php 文件，

也就是说，它需要将模板语言转化成 PHP 语言，生成对应解析后的文件。

在 app 下创建 view 文件夹，用来存放视图模板文件。

在 app 下创建 storage 文件夹，用来保存上传的文件或者缓存文件。

在 storage 目录下继续创建 cache，在 cache 目录下创建 view_cache 用来保存视图缓存文件。

> view_cache 要加入到 .gitignore 忽略的目录，缓存文件不需要同步上传

视图缓存文件即经过模板引擎编译后生成的 PHP 文件。

为了方便管理全局配置，在博客目录下创建 app/config/app.php：

```
<?php
$config = [
    'view_path' => __DIR__ . '/../view',
    'view_cache_path' => __DIR__ . '/../storage/cache/view_cache',
];

return $config;
```

app.php 是博客系统全局的配置文件。

## 视图模块
接下来在框架的 module 目录创建文件夹 View 用来保存视图相关的功能类。

创建 Blade 调用 composer 引入的 blade 模板引擎插件：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/13
 * Time：22:06
 **/


namespace FireRabbitEngine\Module\View;

use Xiaoler\Blade\Compilers\BladeCompiler;
use Xiaoler\Blade\Engines\CompilerEngine;
use Xiaoler\Blade\Engines\EngineResolver;
use Xiaoler\Blade\Factory;
use Xiaoler\Blade\Filesystem;
use Xiaoler\Blade\FileViewFinder;

class Blade
{
    protected static $viewPath, $cachePath;

    /**
     * 设置模板文件目录
     * @param $viewPath
     * @param $cachePath
     */
    public static function setConfig($viewPath, $cachePath)
    {
        self::$viewPath = $viewPath;
        self::$cachePath = $cachePath;
    }

    /**
     * 获取模板引擎返回的html代码
     * @param $blade
     * @param $params
     * @return string
     */
    public static function view($blade, $params)
    {
        $file = new Filesystem;
        $compiler = new BladeCompiler($file, self::$cachePath);

        $resolver = new EngineResolver;
        $resolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });

        $factory = new Factory($resolver, new FileViewFinder($file, [self::$viewPath]));

        try {
            return $factory->make($blade, $params)->render();
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
```

`setConfig` 方法可以用来设置视图和缓存文件的目录。

修改 http_server.php，引入上面创建的 app.php 全局配置文件，同时视图模板加载对应的配置：

```
<?php
require './vendor/autoload.php';
require_once './app/route/web.php';
require_once './app/config/app.php';

\FireRabbitEngine\Module\Http\Middleware\Kernel::setConfig(require './app/config/middleware.php');
\FireRabbitEngine\Module\View\Blade::setConfig($config['view_path'], $config['view_cache_path']);

$http = new Swoole\Http\Server('0.0.0.0', 9527);

$http->on('request', function ($request, $response) use ($router) {

    var_dump('请求URI：' . $request->server['request_uri']);

    $router->handle($request, $response);
});

$http->start();
```

现在这个启动文件看起来乱七八糟的，后面再慢慢优化吧。

通过上面的配置，已经可以调用 Blade 类来生成视图文件了。

## 视图文件
在 app/view 下创建 layout，layout 是视图共用的模板，

比如顶部导航栏，底部 footer 之类的，也就是说 HTML 的母版。

在 layout 目录下面创建 app.blade.php：

```
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>测试</title>
</head>
<body>
    @yield('content')
</body>
</html>
```

> blade 模板文件的命名规则是：视图名称.blade，当然也可以通过配置取消 blade 后缀

这是一个简单的 HTML 代码，`@yield('content')` 即子页需要编写的内容。

> 关于 blade 模板的使用方法可以网上自行了解

接着在 app/view 目录下创建首页 index.php：

```
@extends('layout.app')

@section('content')
    <h1>index 首页</h1>
    <p>这是一个参数：{{ $name }}</p>
@endsection
```

index 继承了 layout/app.blade.php，只需要编写 'content' 部分即可，

这里输出了一个 $name 参数，用来测试模板传参。

## 调用 blade 模板
编辑 IndexController 的 index 方法：

```
<?php

namespace App\Controller\Home;

use FireRabbitEngine\Module\Controller\Controller;
use FireRabbitEngine\Module\View\Blade;

class IndexController extends Controller
{
    public function index()
    {
        $html = Blade::view('index', ['name' => '花花']);

        $this->showMessage($html);
    }
}
```

调用视图的方法为：`Blade::view(视图文件名, [参数])`

视图文件名即去掉 blade 的名字，如：index.blade.php，即 index。

`showMessage` 方法即调用 swoole 的 response 输出字符串：

```
public function showMessage($message)
{
    $this->httpKernel->getResponse()->header("Content-Type", "text/html; charset=utf-8");
    $this->httpKernel->getResponse()->end($message);
}
```

然后打开浏览器，访问首页，即可看到：

```
index 首页
这是一个参数：花花
```

这样，框架的视图模块就完成了！
