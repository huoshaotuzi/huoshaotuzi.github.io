---
title: 从零开始搭建自己的Swoole框架（十二）日志系统
date: 2021-02-14 14:27:25
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 引入日志系统。

---

## 安装日志系统
日志习题属于框架的一部分，因此在框架目录下执行：

```
composer require monolog/monolog
```

## Logger
在框架 module 下新建 Logger 文件夹用来保存日志相关功能代码，

在 Logger 创建 Log 类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/14
 * Time：13:38
 **/


namespace FireRabbitEngine\Module\Logger;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;

class Log
{
    /**
     * 日志配置
     * @var array
     */
    protected static $config;

    /**
     * 日志对象实例
     * @var Logger
     */
    protected static $instance = null;

    public static function setConfig($config)
    {
        self::$config = $config;
    }

    public static function getLogger()
    {
        if (self::$instance == null) {
            self::$instance = new Logger(self::$config['channel']);

            if (!file_exists(self::$config['path'])) {
                $file = fopen(self::$config['path'], 'w');
                fwrite($file, '');
                fclose($file);
            }

            $streamHandler = new StreamHandler(self::$config['path'], self::$config['level']);
//            $streamHandler->setFormatter(new JsonFormatter());

            self::$instance->pushHandler($streamHandler);
        }

        return self::$instance;
    }
}

```

`setConfig` 加载配置参数，`getLogger` 判断是否存在日志文件，如果没有则创建，同时返回插件包的 Logger。

## 配置参数
打开 app/config/app.php，添加日志配置：

```
<?php
$config = [

    'view' => [
        'view_path' => __DIR__ . '/../view',
        'view_cache_path' => __DIR__ . '/../storage/cache/view_cache',
    ],

    'logger' => [
        'path' => __DIR__ . '/../storage/logs/log.log',
        'level' => \Monolog\Logger::INFO,
        'channel' => 'channel-name',
    ],

    'database' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'mysql',
            'port' => '3306',
            'database' => 'blog',
            'username' => 'root',
            'password' => '123123',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ],
    ],
];

return $config;
```

这里的 view 也被我修改了下，这样看起来更整齐。

## 加载配置
编辑 http_server.php 加载日志配置：

```
<?php

date_default_timezone_set("Asia/Shanghai");

require './vendor/autoload.php';
require './firerabbit-engine/vendor/autoload.php';
require_once './app/route/web.php';
require_once './app/config/app.php';

\FireRabbitEngine\Module\Http\Middleware\Kernel::setConfig(require './app/config/middleware.php');
\FireRabbitEngine\Module\View\Blade::setConfig($config['view']['view_path'], $config['view']['view_cache_path']);
\FireRabbitEngine\Module\Database\Manager::setConfig($config['database']['mysql']);

// 新增行
\FireRabbitEngine\Module\Logger\Log::setConfig($config['logger']);

$http = new Swoole\Http\Server('0.0.0.0', 9527);

$http->on('request', function ($request, $response) use ($router) {

    var_dump('请求URI：' . $request->server['request_uri']);

    $router->handle($request, $response);
});

$http->start();

```

## 调用日志
在 IndexController 测试日志是否能正常写入，添加测试代码：

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
use FireRabbitEngine\Module\Logger\Log;
use FireRabbitEngine\Module\View\Blade;

class IndexController extends Controller
{
    public function index()
    {
        $user = User::find(1);
        $html = Blade::view('index', ['name' => $user->name]);

        Log::getLogger()->error('日志');

        $this->showMessage($html);
    }
}
```

然后访问首页，可以看到配置日志路径的文件夹下多了一个 log.log：

```
[2021-02-14T14:26:29.828157+08:00] channel-name.ERROR: 日志 [] []
```

这样日志系统也完成了。
