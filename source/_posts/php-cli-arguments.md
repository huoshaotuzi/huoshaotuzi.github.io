---
title: PHP-CLI 常用命令参数
date: 2020-03-15 12:15:44
tags:
 - PHP
 
categories: PHP

description: PHP 常用命令及参数整理。

---
## 运行 PHP 文件
可以直接使用 `php <文件路径>` 执行 PHP 文件。

```
php /var/www/html/project/index.php;
```

## 进入命令行模式
使用命令 `php -a` 可以进入命令行模式，在这里可以直接运行 PHP 代码：

```
root@0139eebfa774:/var/www/html# php -a
Interactive shell

php > echo "hello world";
hello world
```

## 加载指定配置文件
使用 `php -c` 可以加载指定的配置文件 `php.ini`:

```
php -c /test/my_php.ini
```

## 显示当前配置文件路径
使用命令 `php --ini` 查看当前加载的配置文件路径。

```
root@0139eebfa774:/var/www/html# php --ini
Configuration File (php.ini) Path: /usr/local/etc/php
Loaded Configuration File:         /usr/local/etc/php/php.ini
Scan for additional .ini files in: /usr/local/etc/php/conf.d
Additional .ini files parsed:      /usr/local/etc/php/conf.d/docker-php-ext-bcmath.ini,
/usr/local/etc/php/conf.d/docker-php-ext-gd.ini,
/usr/local/etc/php/conf.d/docker-php-ext-gmp.ini,
/usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini,
/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini,
/usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini,
/usr/local/etc/php/conf.d/docker-php-ext-redis.ini,
/usr/local/etc/php/conf.d/docker-php-ext-sockets.ini,
/usr/local/etc/php/conf.d/docker-php-ext-sodium.ini,
/usr/local/etc/php/conf.d/docker-php-ext-swoole.ini
```

## 启动一个 WebServer
Web Server（网页服务）一般是由 Apache、Nginx 或是 Windows 系统的 IIS 提供。

从 `PHP 5.4.0` 起，也可以使用 cli 模式来启动 PHP 内置的 Web Server。

> 这个内置的 Web 服务器主要用于本地开发使用，不可用于线上产品环境。

使用命令 `php -S localhost:<端口号> [文件路径]` 来启动一个 WebServer：

```
root@0139eebfa774:/var/www/html# php -S localhost:999
[Sun Mar 15 04:25:52 2020] PHP 7.4.1 Development Server (http://localhost:999) started
```

> localhost 等价于 127.0.0.1

启动 PHP WebServer 时可以指定一个文件作为启动脚本（如框架的入口文件），在该文件注册所需要的插件及分发路由等等。

```
php -S 127.0.0.1:666 test.php
```

命令添加 `-t` 参数，将会以目录作为 WebServer 的启动目录。

```
php -S 127.0.0.1:666 -t public/
```

关掉命令行窗口或者按 `Ctrl + C` 即可退出 PHP WebServer。

## PHP 版本号
使用 `php -v` 命令可以查看 PHP 版本信息。

```
root@0139eebfa774:/var/www/html# php -v
PHP 7.4.1 (cli) (built: Dec 28 2019 20:56:41) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
    with Zend OPcache v7.4.1, Copyright (c), by Zend Technologies
```

## PHP 扩展
使用 `php -m` 命令可以查看 PHP 安装的扩展。

```
root@0139eebfa774:/var/www/html# php -m
[PHP Modules]
bcmath
Core
ctype
curl
date
dom
fileinfo
filter
ftp
gd
```

## PHP 参数
使用 `php -i` 命令可以查看 PHP / 扩展的配置参数，等价于 `phpinfo`。

## 语法查错
可以用 `php -l <文件路径>` 来检测该 php 文件是否有语法错误：

```
root@0139eebfa774:/var/www/html# php -l index.php
No syntax errors detected in index.php
```

## 执行一段代码
使用 `php -r "代码"` 来执行一段 PHP 代码：

```
root@0139eebfa774:/var/www/html# php -r "echo 'ok';"
ok
```

## 查看扩展详情
使用命令 `php --ri <扩展名称>` 获取扩展配置详情：

```
root@0139eebfa774:/var/www/html# php --ri swoole

swoole

Swoole => enabled
Author => Swoole Team <team@swoole.com>
Version => 4.4.14
Built => Jan  6 2020 07:22:54
coroutine => enabled
epoll => enabled
eventfd => enabled
signalfd => enabled
cpu_affinity => enabled
spinlock => enabled
rwlock => enabled
openssl => OpenSSL 1.1.1d  10 Sep 2019
http2 => enabled
zlib => 1.2.11
mutex_timedlock => enabled
pthread_barrier => enabled
futex => enabled
mysqlnd => enabled
async_redis => enabled

Directive => Local Value => Master Value
swoole.enable_coroutine => On => On
swoole.enable_library => On => On
swoole.enable_preemptive_scheduler => Off => Off
swoole.display_errors => On => On
swoole.use_shortname => On => On
swoole.unixsock_buffer_size => 8388608 => 8388608
```

## 查看扩展提供的方法
使用命令 `php --re <扩展名称>` 获取扩展的所有方法：

```
php --re swoole
```


## 获取函数详情
使用命令 `php --rf <函数名称>` 获取函数详情，可以用来检测函数是否存在：

```
root@0139eebfa774:/var/www/html# php --rf array_columns
Exception: Function array_columns() does not exist
root@0139eebfa774:/var/www/html# php --rf array_column
Function [ <internal:standard> function array_column ] {

 - Parameters [3] {
    Parameter #0 [ <required> $arg ]
    Parameter #1 [ <required> $column_key ]
    Parameter #2 [ <optional> $index_key ]
  }
}
```