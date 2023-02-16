---
title: 从零开始搭建自己的Swoole框架（三）类的自动加载
date: 2021-02-06 18:49:48
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 类的自动加载。

---
## 准备工作
创建文件目录如下：

```
blog
|-- app
|   `-- public
|-- firerabbit-engine
|   `-- module
`-- http_server.php
```

其中，根目录 blog 为项目根目录，app 文件夹是项目所在目录，

public 用来存放 web 的静态资源如图片、js 文件等。

firerabbit-engine 是框架目录，module 存放模块代码，

目前框架代码跟是项目代码放在一起的，

为了以后方便分离，所以把项目的代码放在 app 里面处理。

> 文件夹的大小写规范自己定义即可

## psr-4 自动加载
在根目录创建文件 `composer.json`，并输入如下内容：

```
{
  "require": {},
  "autoload": {
    "psr-4": {
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

这里我们指定了框架的命名空间以及将镜像源修改为阿里云。

接着使用命令 `composer install` 执行安装，

完成后出现 vendor 文件夹：

```
|-- app
|   `-- public
|-- composer.json
|-- firerabbit-engine
|   `-- module
|-- http_server.php
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

我们第一步要实现路由功能，路由是一个模块，

因此在 modlue 下新建文件夹 Route，

接着在该目录新建文件 `Router.php`：

```
<?php
namespace FireRabbitEngine\Module\Route;

class Router
{
    public function test()
    {
        var_dump('ok');
    }
}

```

> FireRabbitEngine 即框架的命名空间

接着编辑 `http_server.php`，引入 composer 加载文件：

```
<?php
require './vendor/autoload.php';

$http = new Swoole\Http\Server('0.0.0.0', 9527);

// 测试
$router = new \FireRabbitEngine\Module\Route\Router();
$router->test();

$http->on('request', function ($request, $response) {
    $response->header("Content-Type", "text/html; charset=utf-8");
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});

$http->start();
```

在终端输入 `php http_server.php`，看到打印出 ok 就说明自动加载没问题了。

> 每次修改代码都要 Ctrl+C 关掉再重新启动，不然修改了代码也不会生效，因为 swoole 是常驻内存的，只在启动时加载一次

完整的项目目录如下：

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

我们需要同步到 Git 防止丢失代码，

因此需要设置 .gitignore 文件来忽略不需要上传的文件或者目录。

在终端或者直接右键创建文件都可以，.gitignore 内容如下：

```
vendor
.idea
```

> 我用的是 PhpStorm 会产生 .idea 文件夹，但这个是不需要上传的，如果你用的是 vscode，要把 .vscode 文件夹也加进去

然后就可以把项目同步到 Git 上面了。

如此一来类的自动加载就实现了，

下一步我们就可以开始编写路由模块了。
