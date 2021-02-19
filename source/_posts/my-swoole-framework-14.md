---
title: 从零开始搭建自己的Swoole框架（十四）启动程序优化
date: 2021-02-17 18:42:19
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 优化启动程序及加载配置部分的代码。

---

## 前言
前面几篇文章临时修改程序的启动文件，结果变成如下这般惨不忍睹：

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

参数的加载方式也需要优化一下，接下来就开始整改。

## 参数文件统一
除了路由配置和中间件映射关系配置，其他的都可以移动到 app.pho 统一管理。

比如数据库的配置、redis 的配置、模板文件的存放位置等，都属于项目的配置。

因此将原来几个单独的配置文件删掉，统一放到 app.php：

```
<?php

use FireRabbitEngine\Module\Constant;

return [
    'framework' => [
        Constant::DATABASE_CONFIG => [
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
        Constant::LOGGER_CONFIG => [
            'path' => __DIR__ . '/../storage/logs/log.log',
            'level' => 'info',
            'channel' => 'channel-name',
        ],
        Constant::VIEW_CONFIG => [
            'path' => __DIR__ . '/../view',
            'cache_path' => __DIR__ . '/../storage/cache/view_cache',
        ],
    ],
];

```

这个 Constant 类是框架配置的常量，定义如下：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/16
 * Time：10:08
 **/

namespace FireRabbitEngine\Module;

class Constant
{
    const DATABASE_CONFIG = 'firerabbiit_database';
    const LOGGER_CONFIG = 'firerabbit_logger';
    const VIEW_CONFIG = 'firerabbit_view';
}
```

它的作用是统一配置的键名。

## Server
框架现在是直接使用 swoole 的函数来启动程序，

基于面向对象的思想，现在把 server 也封装为一个类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/15
 * Time：16:26
 **/

namespace FireRabbitEngine\Module\Http;

use FireRabbitEngine\Module\Constant;
use FireRabbitEngine\Module\Database\Manager as DatabaseManager;
use FireRabbitEngine\Module\Logger\Log as Logger;
use FireRabbitEngine\Module\Route\Router;
use FireRabbitEngine\Module\View\Blade;
use Swoole\Http\Server;

class HttpServer
{
    public $server;
    public $router;

    public function __construct($host, $port, $config = [])
    {
        $this->server = new Server($host, $port);
        $this->server->set($config);
    }

    public function loadRouter(Router $router)
    {
        $this->router = $router;
        return $this;
    }

    public function loadMiddleware($middleware)
    {
        \FireRabbitEngine\Module\Http\Middleware\Kernel::setConfig($middleware);
        return $this;
    }

    public function bootstrap($config)
    {
        Blade::setConfig($config[Constant::VIEW_CONFIG]);
        DatabaseManager::setConfig($config[Constant::DATABASE_CONFIG]);
        Logger::setConfig($config[Constant::LOGGER_CONFIG]);

        return $this;
    }

    public function request($request, $response)
    {
        var_dump('请求URI：' . $request->server['request_uri']);

        $this->registerError($response);
        $this->router->handle($request, $response);
    }

    private function registerError($response)
    {
        register_shutdown_function(function () use ($response) {
            $error = error_get_last();
            var_dump($error);
            switch ($error['type'] ?? null) {
                case E_ERROR :
                case E_PARSE :
                case E_CORE_ERROR :
                case E_COMPILE_ERROR :
                    $response->status(500);
                    $response->end($error['message']);
                    break;
            }
        });
    }

    public function start()
    {
        $this->server->on('request', [$this, 'request']);
        $this->server->start();
    }
}
```

## 修改启动程序
现在就可以用 server 类来启动程序了，修改 http_server.php：

```
<?php

use FireRabbitEngine\Module\Http\HttpServer;

date_default_timezone_set("Asia/Shanghai");
define('ROOT_PATH', __DIR__);

require './vendor/autoload.php';
require './firerabbit-engine/vendor/autoload.php';

$config = require './app/config/app.php';

$server = new HttpServer('0.0.0.0', 9527, [
    'worker_num' => 4,
]);

$router = require './app/route/web.php';
$middleware = require './app/config/middleware.php';

$server->bootstrap($config['framework'])
    ->loadMiddleware($middleware)
    ->loadRouter($router)
    ->start();

```

这样就实现了用类来控制启动程序，启动程序的代码也变得整洁了。
