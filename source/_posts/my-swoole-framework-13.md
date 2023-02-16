---
title: 从零开始搭建自己的Swoole框架（十三）框架性能小测
date: 2021-02-14 20:42:02
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 用 ab 压测工具测测看性能如何。

---

## 前言
自从写完路由模块之后就各种偷懒了……

如果所有功能都要自己写的话，工作量实在太大了。

而且我对于 swoole 也没有花时间来学习，现在写的框架也只不过是简单的封装而已。

swoole 的优势很难体现出来，但是相比于用 Laravel 这种重型框架来说，

自己写的框架虽然是“山寨”版 Laravel，但是性能应该比 Laravel 强不少。

于是忍不住就想用 ab 工具来测一下了。

## 压测结果

如果是访问域名的话，其实是先经过 nginx，然后再通过反向代理转发给 swoole，

这种方法与直接访问 swoole 端口有区别，于是就分作两租测试。

Nginx 反向代理：`ab -c 100 -n 10000 http://firerabbit-engine.ht/`

直接访问 Swoole 端口：`ab -c 100 -n 10000 http://127.0.0.1：9527/`

两种情况分别测试三组数据，取平均值。

测试的路由配置：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Http\\Controller\\Home\\',

])->group(function () use ($router) {

    $router->get('/', 'IndexController@index')->middleware(['a', 'b']);

});

return $router;
```

路由加入了两个中间件，中间件处理过程也是比较消耗性能的地方。

然后是测试的方法：

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

        $this->showMessage($html);
    }
}
```

测试方法通过 ORM 查询 users 表的数据，然后传给视图，最后输出视图页面。

这样可以模拟普通的业务逻辑，看看这个框架写的 WEB 程序到底能跑多少分吧！

### Nginx 反向代理

第一组数据：

```
Server Software:        nginx/1.15.12
Server Hostname:        firerabbit-engine.ht
Server Port:            80

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   51.355 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5570000 bytes
HTML transferred:       3980000 bytes
Requests per second:    194.72 [#/sec] (mean)
Time per request:       513.548 [ms] (mean)
Time per request:       5.135 [ms] (mean, across all concurrent requests)
Transfer rate:          105.92 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.8      0      51
Processing:    42  511  35.3    512     629
Waiting:       30  506  35.2    508     629
Total:         42  511  35.0    512     629

Percentage of the requests served within a certain time (ms)
  50%    512
  66%    523
  75%    529
  80%    533
  90%    546
  95%    557
  98%    572
  99%    581
 100%    629 (longest request)
```

第二组数据：

```
Server Software:        nginx/1.15.12
Server Hostname:        firerabbit-engine.ht
Server Port:            80

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   54.842 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5570000 bytes
HTML transferred:       3980000 bytes
Requests per second:    182.34 [#/sec] (mean)
Time per request:       548.422 [ms] (mean)
Time per request:       5.484 [ms] (mean, across all concurrent requests)
Transfer rate:          99.18 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.5      0      40
Processing:    56  544  49.7    543    1046
Waiting:       50  539  49.6    538    1043
Total:         61  544  49.8    543    1050

Percentage of the requests served within a certain time (ms)
  50%    543
  66%    557
  75%    567
  80%    573
  90%    588
  95%    602
  98%    619
  99%    633
 100%   1050 (longest request)
```

第三组数据：

```
Server Software:        nginx/1.15.12
Server Hostname:        firerabbit-engine.ht
Server Port:            80

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   54.510 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5570000 bytes
HTML transferred:       3980000 bytes
Requests per second:    183.45 [#/sec] (mean)
Time per request:       545.097 [ms] (mean)
Time per request:       5.451 [ms] (mean, across all concurrent requests)
Transfer rate:          99.79 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   3.1      0     196
Processing:    28  542 112.1    527    1561
Waiting:        9  538 111.6    522    1557
Total:         28  543 111.9    527    1561

Percentage of the requests served within a certain time (ms)
  50%    527
  66%    542
  75%    554
  80%    562
  90%    589
  95%    614
  98%    745
  99%   1471
 100%   1561 (longest request)

```

### 直接访问 swoole 程序 

第一组：

```
Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9527

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   41.408 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5620000 bytes
HTML transferred:       3980000 bytes
Requests per second:    241.50 [#/sec] (mean)
Time per request:       414.077 [ms] (mean)
Time per request:       4.141 [ms] (mean, across all concurrent requests)
Transfer rate:          132.54 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   1.4      0      15
Processing:    41  412  34.8    410     580
Waiting:       26  411  34.8    410     580
Total:         41  412  34.4    410     591

Percentage of the requests served within a certain time (ms)
  50%    410
  66%    421
  75%    428
  80%    435
  90%    454
  95%    469
  98%    489
  99%    507
 100%    591 (longest request)
```

第二组：

```
Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9527

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   40.637 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5620000 bytes
HTML transferred:       3980000 bytes
Requests per second:    246.08 [#/sec] (mean)
Time per request:       406.368 [ms] (mean)
Time per request:       4.064 [ms] (mean, across all concurrent requests)
Transfer rate:          135.06 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   1.4      0      20
Processing:    38  404  38.0    404     594
Waiting:       19  404  38.0    403     594
Total:         39  404  37.3    404     594

Percentage of the requests served within a certain time (ms)
  50%    404
  66%    415
  75%    423
  80%    428
  90%    443
  95%    461
  98%    484
  99%    497
 100%    594 (longest request)
```

第三组：

```
Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9527

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   41.103 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5620000 bytes
HTML transferred:       3980000 bytes
Requests per second:    243.29 [#/sec] (mean)
Time per request:       411.031 [ms] (mean)
Time per request:       4.110 [ms] (mean, across all concurrent requests)
Transfer rate:          133.52 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.3      0       5
Processing:    39  408  49.5    402    1022
Waiting:       38  407  49.4    402    1022
Total:         43  408  49.5    402    1024

Percentage of the requests served within a certain time (ms)
  50%    402
  66%    413
  75%    420
  80%    426
  90%    441
  95%    463
  98%    521
  99%    628
 100%   1024 (longest request)
```

### 对比结果
主要对比 `Requests per second` 参数，

RPS（也叫 QPS）即平均每秒完成的请求数，这个值越大代币能承受的并发量越高。

nginx 转发的三组分别为：194.72、182.34、182.45

直接访问 swoole 的三组分别为：241.50、246.08、243.29

取平均值即：nginx=186.50，swoole=243.62

也就是说，通过 nginx 反向代理会损失一部分的性能。

而且距离最开始想要实现在几十毫秒内返回也差了很多，即使是直接访问 swoole 最快的也需要 600ms。

而且通过 nginx 转发之后，QPS 只有不到 200，相比其他 swoole 框架，自己写的框架性能已经大幅下降了。

这中间应该是有一些非异步的请求，比如 MySQL 查询，只有完成查询后才会继续往下执行，导致程序阻塞了。

不过总体而言，使用了 swoole 自己写的框架性能比起普通的 php-fpm 框架要高得多，

如果再加上一些逻辑业务处理，QPS 应该也能维持在 100-200 之间，这样的结果还是比较满意的。

关于 swoole 的特性还是需要仔细学习一番，框架方面的代码也还有很大的优化空间。

如果后续不断更新的话，可支持的并发量应该也会不断变大吧！

## 后记
为了提高性能，我把 PHP 升级到 7.4，同时 swoole 扩展也升级到 4.6.3，

然后重新测试了一遍。

### Nginx 反向代理

测试数据：

```
Server Software:        nginx/1.15.12
Server Hostname:        firerabbit-engine.ht
Server Port:            80

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   55.727 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5570000 bytes
HTML transferred:       3980000 bytes
Requests per second:    179.45 [#/sec] (mean)
Time per request:       557.265 [ms] (mean)
Time per request:       5.573 [ms] (mean, across all concurrent requests)
Transfer rate:          97.61 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.6      0      26
Processing:    37  555  58.0    550     938
Waiting:       24  550  57.8    545     938
Total:         37  555  57.8    550     938

Percentage of the requests served within a certain time (ms)
  50%    550
  66%    565
  75%    576
  80%    583
  90%    604
  95%    628
  98%    676
  99%    797
 100%    938 (longest request)
```

### 直接访问 swoole 程序 

测试数据：

```
Completed 1000 requests
Completed 2000 requests
Completed 3000 requests
Completed 4000 requests
Completed 5000 requests
Completed 6000 requests
Completed 7000 requests
Completed 8000 requests
Completed 9000 requests
Completed 10000 requests
Finished 10000 requests


Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9527

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   41.988 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5620000 bytes
HTML transferred:       3980000 bytes
Requests per second:    238.16 [#/sec] (mean)
Time per request:       419.885 [ms] (mean)
Time per request:       4.199 [ms] (mean, across all concurrent requests)
Transfer rate:          130.71 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.3      0       5
Processing:    44  416  50.0    411    1090
Waiting:       44  416  50.0    410    1090
Total:         47  416  50.1    411    1092

Percentage of the requests served within a certain time (ms)
  50%    411
  66%    421
  75%    428
  80%    434
  90%    448
  95%    464
  98%    520
  99%    559
 100%   1092 (longest request)
```

### 对比结果
升级了 PHP 和 swoole 扩展的版本后，

nginx=179.45，swoole=238.16

> 原本为：nginx=186.50，swoole=243.62

好像也没有肉眼可见的提升……

然后又尝试优化 composer 生成的自动加载：`composer dump-autoload -o`

测试的结果也没有太大的变化。

看来，如果想进一步提升 QPS 的话，重点应该是解决阻塞的地方了。

### 工作进程数
忽然想到提高工作进程数，按道理应该可以提高一定的性能，

编辑 http_server.php 为 swoole 的 http 设置参数：

```
$http->set([
    'worker_num' => 8,
]);
```

我的电脑是 4 核 i5，把工作进程设置为核心数的两倍，然后继续测试 swoole 程序和 nginx 转发的结果。

nginx 转发的结果：

```
Server Software:        nginx/1.15.12
Server Hostname:        firerabbit-engine.ht
Server Port:            80

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   59.781 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5570000 bytes
HTML transferred:       3980000 bytes
Requests per second:    167.28 [#/sec] (mean)
Time per request:       597.808 [ms] (mean)
Time per request:       5.978 [ms] (mean, across all concurrent requests)
Transfer rate:          90.99 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.8      0      38
Processing:    46  595  84.2    591    1436
Waiting:       39  590  84.2    585    1435
Total:         46  595  84.7    591    1445

Percentage of the requests served within a certain time (ms)
  50%    591
  66%    607
  75%    618
  80%    624
  90%    647
  95%    668
  98%    697
  99%    742
 100%   1445 (longest request)
```

直接访问 swoole：

```
Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9527

Document Path:          /
Document Length:        398 bytes

Concurrency Level:      100
Time taken for tests:   39.114 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      5620000 bytes
HTML transferred:       3980000 bytes
Requests per second:    255.66 [#/sec] (mean)
Time per request:       391.139 [ms] (mean)
Time per request:       3.911 [ms] (mean, across all concurrent requests)
Transfer rate:          140.32 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.4      0       6
Processing:    61  387  77.4    380    1409
Waiting:       61  387  77.4    380    1409
Total:         67  388  77.6    381    1413

Percentage of the requests served within a certain time (ms)
  50%    381
  66%    395
  75%    405
  80%    413
  90%    433
  95%    452
  98%    494
  99%    543
 100%   1413 (longest request)
```

nginx=167.28，swoole=255.66

> PHP 和 swoole 未升级前：nginx=186.50，swoole=243.62
> PHP 和 swoole 升级后：nginx=179.45，swoole=238.16

嗯？？？nginx 的反而下降了？swoole 的倒是有一定的提升。

也许是因为没有足够的业务，导致测试的结果准确性不高。

测试就到这里吧，博客系统也还没开始制作，框架也属于半成品，等到完成度比较高的时候再测测看。
