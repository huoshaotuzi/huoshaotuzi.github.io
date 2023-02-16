---
title: 从零开始搭建自己的Swoole框架（十八）异步任务 
date: 2021-02-18 19:42:11 
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 为框架添加执行异步任务的能力。

---

## 前言

框架现在拥有发送邮件的能力了，但是发送邮件是非常耗时的一件事，

因此需要用异步任务来解决这个问题。

## 异步任务

swoole 内置了异步任务处理，参照文档：[https://wiki.swoole.com/#/start/start_task](https://wiki.swoole.com/#/start/start_task)

一个简单的异步任务示例：

```

$http = new Swoole\Http\Server('0.0.0.0', 9527);

$http->set([
    'task_worker_num' => 1,
]);

$http->on('Request', function ($request, $response) use ($http) {

    // 投递任务
    $params = ['name' => '花花'];
    $taskID = $http->task($params);
    var_dump('投递了一个任务，ID：' . $taskID . '，参数：' . json_encode($params, JSON_UNESCAPED_UNICODE));

    $response->header('Content-Type', 'text/html; charset=utf-8');
    $response->end('<h1>Hello Swoole. #' . rand(1000, 9999) . '</h1>');
});

//处理异步任务(此回调函数在task进程中执行)
$http->on('Task', function ($serv, $task_id, $reactor_id, $data) {

    var_dump('收到任务，开始处理，任务ID：' . $task_id . '，参数：' . json_encode($data, JSON_UNESCAPED_UNICODE));

    // 业务逻辑
    $result = '那只猫的名字叫做' . $data['name'];

    //返回任务执行的结果
    $serv->finish($result);
});

//处理异步任务的结果(此回调函数在worker进程中执行)
$http->on('Finish', function ($serv, $task_id, $data) {
    // 任务执行完成后的回调
    var_dump('【处理结果】任务ID：' . $task_id . '，返回结果：' . $data);
});

$http->start();
```

要开启任务，必须设置 `task_worker_num`，此参数是处理任务的进程数。

要投递一个任务，只要调用 server 的 task 方法即可，task 方法接收一个参数，执行完成后返回任务 ID。

投递的任务会在 task 事件中执行，要监听事件只需要调用 on 方法。

task 事件处理完成后的结果可以通知给 finish 事件，也可以不通知。

上述代码输出结果：

```
string(59) "投递了一个任务，ID：0，参数：{"name":"花花"}"
string(71) "收到任务，开始处理，任务ID：0，参数：{"name":"花花"}"
string(78) "【处理结果】任务ID：0，返回结果：那只猫的名字叫做花花"
```

如果多次执行，任务 ID 会从 0 开始不断加 1，第二个任务的 ID 为 1，第三个任务的 ID 为 2，以此类推。

如果关闭程序再重新启动，任务 ID 又会从 0 开始。

即使将 `task_worker_num` 改为 2 或者更大，ID 也是保持相同规则自增，因此可以判定 ID 是多个工作进程共享的，不会出现 ID 重复的情况。

## 执行逻辑

swoole 的异步任务必须接受一个数组作为参数，而不能直接将对象作为参数传给任务：

```
# 错误的方法
$server->task(new MyTask());

# 正确的方法
$server->task(['name' => '花花']);
```

只要想起之前路由是怎么设计的，任务系统就很简单了。

既然只能传递数组作为参数，那只要传一个任务名称，再实际调用的时候实例化就可以了。

## 实现任务

分发任务必须要在 `Swoole\Http\Server`，因此原来的代码就需要修改一遍了。

### 传参：server

要调用任务的地方，目前只有 controller，因此 server 必须传给 controller。

只要修改路由模块传递参数即可：

```
/**
 * 处理路由
 * @param Server $server
 * @param $request
 * @param $response
 */
public function handle(Server $server, $request, $response)
{
    $route = $this->findRoute($request);

    if ($route == null) {
        (new NotFoundResponse)->response($request, $response, $route);
        return;
    }

    $route->createResponse($server, $request, $response);
}
```

handle 多接收一个 server 参数，

然后再在实例化路由配置的时候把 server 传给路由，

路由实例化控制器的时候，再把 server 传给控制器就行了，详细的代码就不贴出来了。

控制器现在已经可以拿到 server 了，但这是 swoole 的方法，

### TaskInterface：标准任务接口

基于面向对象的思想，此处应该有封装。

在框架目录下新建 Task 目录，再创建 TaskInterface 接口：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：20:23
 **/


namespace FireRabbitEngine\Module\Task;


interface TaskInterface
{
    /**
     * 处理逻辑
     * @param $params
     * @return mixed
     */
    public function handle($params);

    /**
     * 处理完成回调
     * @param $params
     * @return mixed
     */
    public function finish($result);
}
```

这个接口就是一个统一标准的 Task 类，以后用户想要创建一个任务，就实现这个接口。

handle 方法即 swoole 监听的 task 事件中处理任务逻辑的地方；

finish 方法即 swoole 监听的 finish 事件处理完任务执行回调的地方。

### Task：分发任务

统一的标准任务类已经有了，但还需要一个任务处理类，在 Task 文件夹下创建 Task 类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：20:29
 **/


namespace FireRabbitEngine\Module\Task;

use Swoole\Http\Server;

class Task
{
    /**
     * 分发一个任务
     * @param Server $server
     * @param TaskInterface $task
     * @param array $data
     * @return int
     */
    public static function dispatch(Server $server, string $task, array $data = []): int
    {
        $params = [
            'task' => $task,
            'data' => $data,
        ];

        var_dump($task);

        return $server->task($params);
    }
}
```

这个类只需要一个 dispatch 方法，接收任务类的名称以及附加参数。

然后再修改框架的 controller：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:16 PM
 **/


namespace FireRabbitEngine\Module\Controller;

use FireRabbitEngine\Module\Task\Task;
use FireRabbitEngine\Module\Http\Kernel as HttpKernel;

class Controller
{
    protected $httpKernel;

    public function __construct(HttpKernel $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    /**
     * 分发任务
     * @param $task
     * @param $data
     * @return int
     */
    public function dispatch($task, $data)
    {
        $server = $this->httpKernel->getServer();
        return Task::dispatch($server, $task, $data);
    }

    public function showMessage($message)
    {
        $this->httpKernel->getResponse()->header("Content-Type", "text/html; charset=utf-8");
        $this->httpKernel->getResponse()->end($message);
    }

    public function getRequest()
    {
        return $this->httpKernel->getRequest();
    }

    public function getResponse()
    {
        return $this->httpKernel->getResponse();
    }
}
```

server 是通过 httpKernel 在路由时传参得到的，

如果没有 server 就无法调用 swoole 的 task 方法。

controller 也声明了一个 dispatch 方法供用户直接调用。

### 执行任务
执行任务是在 HttpServer 处通过监听 task 和 finish 两个事件。

修改 HttpServer：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/15
 * Time：16:26
 **/

namespace FireRabbitEngine\Module\Http;

use FireRabbitEngine\Module\Auth\Auth;
use FireRabbitEngine\Module\Cache\Cache;
use FireRabbitEngine\Module\Constant;
use FireRabbitEngine\Module\Database\Manager as DatabaseManager;
use FireRabbitEngine\Module\Logger\Log as Logger;
use FireRabbitEngine\Module\Mail\Mailer;
use FireRabbitEngine\Module\Route\Router;
use FireRabbitEngine\Module\Task\TaskInterface;
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
        // 视图
        Blade::setConfig($config[Constant::VIEW_CONFIG]);
        // 数据库ORM
        DatabaseManager::setConfig($config[Constant::DATABASE_CONFIG]);
        // 日志
        Logger::setConfig($config[Constant::LOGGER_CONFIG]);
        // 缓存
        $cache = $config[Constant::CACHE_CONFIG];
        Cache::setConfig($cache['driver'], $cache[$cache['driver']]);
        // JWT
        Auth::setConfig($config[Constant::JWT_CONFIG]);
        // 邮件
        Mailer::setConfig($config[Constant::MAIL_CONFIG]);

        return $this;
    }

    public function request($request, $response)
    {
        var_dump('请求URI：' . $request->server['request_uri']);

        $this->registerError($response);
        $this->router->handle($this->server, $request, $response);
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

    public function task()
    {
        $this->server->on('task', function ($server, $taskID, $reactorID, $data) {
            var_dump('收到任务，开始处理，任务ID：' . $taskID . '，参数：' . json_encode($data));

            if (isset($data['task']) && class_exists($data['task'])) {

                $task = new $data['task'];

                if ($task instanceof TaskInterface) {
                    $resultData = $task->handle($data['data']);
                    $result = [
                        'task' => $data['task'],
                        'result' => $resultData ?? null,
                    ];
                    $server->finish($result);
                }
            }
        });
    }

    public function finish()
    {
        $this->server->on('finish', function ($server, $taskID, $data) {
            var_dump('任务处理完了，任务ID：' . $taskID);

            if (isset($data['task']) && class_exists($data['task'])) {

                $task = new $data['task'];

                if ($task instanceof TaskInterface) {
                    $task->finish($data['result']);
                }
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

新增了两个方法：task 和 finish，只要调用此方法即可实现监听事件。

### 开启监听
在启动程序 http_server.php 处新增监听事件：

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
    'task_worker_num' => 1,
]);

$router = require './app/route/web.php';
$middleware = require './app/config/middleware.php';

$server->task();
$server->finish();

$server->bootstrap($config['framework'])
    ->loadMiddleware($middleware)
    ->loadRouter($router)
    ->start();
```

task 和 finish 必须在 start 之前，

而且 swoole 的参数必须加上 `task_worker_num`，该值是处理事件的进程数量。

> 通俗的讲 task_worker_num 就是工具人的数量，工具人越多，堆积的任务处理速度越快，swoole 会轮询分发给工具人任务，工具人至少也要有 1 个，如果没有工具人谁来干活呢？

由于我的博客系统只需要发送邮件这个简单的任务，并不会堆积很多，所以只需要 1 个进程用来处理任务就够了。

## 项目任务
前面已经完成了邮件系统，现在可以把发送邮件当做异步任务来执行了。

在博客项目新建存放任务类的文件夹 app/Http/Task，再创建一个用来发送邮件的任务：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：21:46
 **/

namespace App\Http\Task;

use FireRabbitEngine\Module\Mail\Mailer;
use FireRabbitEngine\Module\Task\TaskInterface;

class MailTask implements TaskInterface
{
    public function handle($params)
    {
        var_dump('调用handle处理任务');

        $mailer = new Mailer();
        $mailer->subject('测试异步任务发送邮件')
            ->body('这是邮件内容')
            ->address($params['email'])
            ->send();

        return '发送成功';
    }

    public function finish($result)
    {
        var_dump($result);
    }
}
```

然后在 controller 添加测试代码：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:17 PM
 **/

namespace App\Http\Controller\Home;

use App\Http\Task\MailTask;
use FireRabbitEngine\Module\Controller\Controller;

class IndexController extends Controller
{
    public function test()
    {
        $this->dispatch(MailTask::class, ['email' => '874811226@qq.com']);
        $this->showMessage('ok');
    }
}
```

最终输出结果为：

```
string(17) "请求URI：/test"
string(126) "收到任务，开始处理，任务ID：0，参数：{"task":"App\\Http\\Task\\MailTask","data":{"email":"874811226@qq.com"}}"
string(24) "调用handle处理任务"
string(33) "任务处理完了，任务ID：0"
string(12) "发送成功"
```

并且邮箱也能正常收到测试邮件。

如此一来，框架的异步任务也算完成了。

## 延迟任务
swoole 提供了毫秒定时器，可以用来延迟分发任务。

而定时器又分为 after（一次性）与 tick（重复）两种类型。

一次性定时器执行完就会销毁，而重复定时器则会间隔执行，直到手动销毁为止。

Swoole 官方文档：[swoole - 定时器](https://wiki.swoole.com/#/timer)

### 一次性任务
调用 delay 即可实现延迟发布任务。

### 重复性任务
例如每隔半小时就将缓存中的数据写入到数据库，或者是爬虫任务每小时执行一次，诸如此类。

### 具体实现
修改 Task 类，添加对应的方法：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：20:29
 **/


namespace FireRabbitEngine\Module\Task;

use Swoole\Http\Server;
use Swoole\Timer;

class Task
{
    /**
     * 分发一个任务
     * @param Server $server
     * @param TaskInterface $task
     * @param array $data
     * @return int
     */
    public static function dispatch(Server $server, string $task, array $data = []): int
    {
        $params = [
            'task' => $task,
            'data' => $data,
        ];

        return $server->task($params);
    }

    /**
     * 延迟分发任务
     * @param Server $server
     * @param int $ms
     * @param string $task
     * @param array $data
     */
    public static function delay(Server $server, int $ms, string $task, array $data = []): int
    {
        $params = [
            'task' => $task,
            'data' => $data,
        ];

        return Timer::after($ms, function () use ($server, $params) {
            $server->task($params);
        });
    }

    public static function tick(Server $server, int $ms, string $task, array $data = []): int
    {
        $params = [
            'task' => $task,
            'data' => $data,
        ];

        return Timer::tick(1000, function () use ($server, $params) {
            $server->task($params);
        });
    }

    public static function clear(int $timerID): bool
    {
        return Timer::clear($timerID);
    }
}
```

执行延迟任务时，可以返回一个 int 类型的时钟 ID，调用 clear 可以将定时器清除。
