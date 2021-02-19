---
title: 从零开始搭建自己的Swoole框架（十五）缓存模块 
date: 2021-02-18 11:30:22 

tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 为框架添加缓存模块。

---

## 前言

缓存可以大幅提高程序的性能以及减轻数据库压力。

今天就来设计框架的缓存模块。

缓存可以用很多种方法实现，例如：redis、数据库或者文件。

从性能来看，redis 是最优的，因此本框架将会使用 redis 作为缓存系统。

## 驱动接口

虽然现在使用 redis 作为缓存驱动，但是未来可能会添加其他的。

因此将缓存驱动声明为一个接口，以后就不需要修改业务代码了。

在框架目录下新建一个 Cache 文件夹用来存放缓存相关的代码。

接着声明一个接口：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：10:54
 **/


namespace FireRabbitEngine\Module\Cache;


use Closure;

interface DriverInterface
{
    /**
     * 载入参数
     * @param $config
     * @return mixed
     */
    public function load($config);

    /**
     * 含有过期时间的键值对
     * @param string $key
     * @param int $ttl
     * @param Closure $initFun
     * @return string
     */
    public function remember(string $key, int $ttl, Closure $initFun): string;

    /**
     * 没有过期时间的键值对
     * @param string $key
     * @param Closure $initFun
     * @return string
     */
    public function rememberForever(string $key, Closure $initFun): string;
}
```

这里暂且实现两个键值对缓存的方法，

`remember` 记住一个键值对 ttl 秒；

`rememberForever` 记住一个键值对，且不过期。

上述两个方法如果没有默认值，则从 `$initFun` 闭包函数中获取，同时将数据写入缓存。

除此之外，还有一个 `load` 方法用于获取缓存的配置信息。

## RedisDriver

接着在 Cache 下新建一个 Driver 文件夹，用来保存对应的驱动。

创建 RedisDriver，让它实现 DriverInterface：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：10:53
 **/


namespace FireRabbitEngine\Module\Cache\Driver;


use Closure;
use FireRabbitEngine\Module\Cache\DriverInterface;

class RedisDriver implements DriverInterface
{
    protected $instance;

    public function load($config)
    {
        $this->instance = new \Redis();
        $this->instance->connect($config['host'], $config['port']);
        $this->instance->auth($config['password']);
    }

    public function remember($key, int $ttl, Closure $initFun): string
    {
        $value = $this->instance->get($key);

        if ($value !== false) {
            var_dump('从缓存获取');

            return $value;
        }

        var_dump('从闭包获取');

        $value = $initFun();
        $this->instance->setEx($key, $ttl, $value);

        return $value;
    }

    public function rememberForever($key, Closure $initFun): string
    {
        $value = $this->instance->get($key);

        if ($value !== false) {
            return $value;
        }

        $value = $initFun();
        $this->instance->set($key, $value);

        return $value;
    }
}
```

redis 驱动直接调用 PHP 的 redis 扩展提供的方法。

## Cache

现在有了缓存驱动，但是并不是直接在控制器或者其他地方实例化这个缓存驱动来调用。

而是创建一个通用的 Cache 类来让用户调用，

如果不这样做，项目的缓存系统就相当于写死了，以后如果要把 redis 换成数据库缓存就很麻烦。

因此我们提供一个 Cache 类，用户只要调用 Cache 暴露出来的方法即可。

在框架的 Cache 目录下新建：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：10:53
 **/


namespace FireRabbitEngine\Module\Cache;


use FireRabbitEngine\Module\Cache\Driver\RedisDriver;

class Cache
{
    protected static DriverInterface $driver;

    public static function setConfig($cache, $config)
    {
        switch ($cache) {
            case 'redis':
                self::redisDriver($config);
                break;
        }
    }

    protected static function redisDriver($config)
    {
        self::$driver = new RedisDriver();
        self::$driver->load($config);
    }

    public static function driver(): DriverInterface
    {
        return self::$driver;
    }
}
```

Cache 类对外提供了 `driver` 方法用于获取缓存驱动，

用户调用框架的缓存系统时，只需要从 driver 方法获得缓存驱动的实例，

然后再调用 DriverInterface 声明的标准方法即可。

## 加载配置

缓存系统需要在启动程序的时候连接到 redis，

因此声明一个新的常量，然后在 app.php 添加框架配置：

```
Constant::CACHE_CONFIG => [
    'driver' => 'redis',
    'redis' => [
        'host' => 'redis',
        'port' => '6379',
        'password' => '123123',
    ],
],
```

接着，在封装好的启动程序 HttpServer 初始化时加入缓存系统的初始化代码：

```
public function bootstrap($config)
{
    Blade::setConfig($config[Constant::VIEW_CONFIG]);
    DatabaseManager::setConfig($config[Constant::DATABASE_CONFIG]);
    Logger::setConfig($config[Constant::LOGGER_CONFIG]);

    // 新增代码
    $cache = $config[Constant::CACHE_CONFIG];
    Cache::setConfig($cache['driver'], $cache[$cache['driver']]);

    return $this;
}
```

如此一来，缓存系统就算完成了。

## 使用缓存

创建一个 test 路由，控制器的代码如下：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:17 PM
 **/

namespace App\Http\Controller\Home;

use FireRabbitEngine\Module\Cache\Cache;
use FireRabbitEngine\Module\Controller\Controller;

class IndexController extends Controller
{
    public function test()
    {
        $value = Cache::driver()->remember('test', 5, function () {
            return 'aaa';
        });

        $this->showMessage(json_encode($value));
    }
}
```

从缓存驱动中获取名称为 "test" 的键，如果不存在则执行闭包，

闭包里面是用户的业务逻辑，例如从数据库查询数据等等，最终将结果以字符串的形式返回，

缓存系统将闭包返回的值写入到缓存，最后再把该值返回。

通俗的讲，就是 **从缓存获取该键的值，如果没有就执行闭包的函数进行初始化。**

测试结果：

```
root@0a71c06b420b:/www/blog# php http_server.php 
string(17) "请求URI：/test"
string(15) "从闭包获取"
string(17) "请求URI：/test"
string(15) "从缓存获取"

# 间隔5秒后再访问
string(17) "请求URI：/test"
string(15) "从闭包获取"

```

可以发现，第一次因为缓存没有数据，因此执行了闭包的函数，

第二次缓存已经有数据了，所以直接返回缓存中的数据，证明了闭包成功将数据写入到缓存了。

然后 5 秒之后再访问，可以发现又调用了闭包，证明缓存在 5 秒的时候过期了。

## 优化方案
键值对只是 redis 的基本类型，后续还会加入更多的操作方法。
