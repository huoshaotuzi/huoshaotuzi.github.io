---
title: 从零开始搭建自己的Swoole框架（十一）数据库模型
date: 2021-02-14 12:41:11
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 引入数据库处理包。

---

## 前言
数据库操作类自己写不安全，而且也有比较成熟的插件包了，

因此我打算直接引入 Laravel 相同的 ORM。

## 插件包安装
数据库操作属于框架层面的，因此在框架的目录下执行：

```
composer require illuminate/database
```

框架目录下也会自动创建一个 composer.json 文件，同时安装完成后会生成 vendor 文件夹。

在框架目录添加 .gitignore 忽略上传 vendor 文件夹。

## Blade 包错误修正

在前面完成 blade 模板时，blade 模板的包是在 app 目录下的，

这样就不是在框架里了，因此回到博客目录用 `composer remove xiaoler/blade` 命令移除 blade 包。

然后再进入框架目录重新安装 blade 即可，这样 blade 模块就属于框架内部了。

框架现在还不是一个 composer 包，因此框架的自动加载文件需要手动添加，

编辑 swoole 启动文件，http_server.php：

```
require './vendor/autoload.php';

// 新增行
require './firerabbit-engine/vendor/autoload.php';
```

在引入自动加载文件的下一行添加框架的自动加载，这样就完成了。

## 数据库配置
编辑 app/config/app.php，添加数据库配置：

```
<?php
$config = [
    'view_path' => __DIR__ . '/../view',
    'view_cache_path' => __DIR__ . '/../storage/cache/view_cache',

    'database' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'blog',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ],
    ],
];

return $config;
```

## ORM 模块加载
在框架 module 新建文件夹 Database 用来存储数据库相关功能模块代码，

在 Database 文件夹下新建 Manager.php 用于加载数据库配置：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/14
 * Time：13:02
 **/


namespace FireRabbitEngine\Module\Database;


class Manager
{
    protected static $config;

    public static function setConfig($config)
    {
        $db = new \Illuminate\Database\Capsule\Manager();
        $db->addConnection($config);
        $db->setAsGlobal();
        $db->bootEloquent();
    }

    public static function getConfig()
    {
        return self::$config;
    }
}
```

`setConfig` 方法加载一个数组参数的配置。

编辑 http_server.php 加入一行：

```
<?php
require './vendor/autoload.php';
require './firerabbit-engine/vendor/autoload.php';
require_once './app/route/web.php';
require_once './app/config/app.php';

\FireRabbitEngine\Module\Http\Middleware\Kernel::setConfig(require './app/config/middleware.php');
\FireRabbitEngine\Module\View\Blade::setConfig($config['view_path'], $config['view_cache_path']);

// 新增行
\FireRabbitEngine\Module\Database\Manager::setConfig($config['database']['mysql']);

$http = new Swoole\Http\Server('0.0.0.0', 9527);

$http->on('request', function ($request, $response) use ($router) {

    var_dump('请求URI：' . $request->server['request_uri']);

    $router->handle($request, $response);
});

$http->start();

```

在 on 之前加载数据库配置。

> 现在这个启动文件已经不堪入目了，等以后再优化

## 创建 Model
在 app/Http 下新建 Model 文件夹用来保存模型文件。

在 Model 新建第一个模型文件 User：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/14
 * Time：13:05
 **/


namespace App\Http\Model;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}
```

只要让它继承 `Illuminate\Database\Eloquent\Model` 即可。

## 添加数据
打开数据库，在 users 表加入一行数据：

```
name：花花 - 001
password：123123
```

## 查询数据
打开 IndexController，修改  index 方法：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:17 PM
 **/

namespace App\Http\Controller\Home;

use App\Http\Model\User;
use FireRabbitEngine\Module\Controller\Controller;
use FireRabbitEngine\Module\View\Blade;

class IndexController extends Controller
{
    public function index()
    {
        $user = User::find(1);
        $html = Blade::view('index', ['name' => $user->name]);

        $this->showMessage($html);
    }
}
```

原来的代码是直接传入 name 字符串，现在改成从数据库查询数据然后传给模板。

然后测试，发现页面输出了名字：花花 - 001

如此一来，ORM 模块也完成了。
