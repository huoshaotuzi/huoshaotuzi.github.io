---
title: PHP-FPM
date: 2020-03-14 20:24:27
tags:
 - PHP

categories: PHP

description: FastCGI 进程管理器。

---
## 前言
在学 PHP 的时候，搜索网上教程一顿操作配置了 LNMP（Linux + Nginx + MySQL + PHP）环境，在配置 Nginx 的时候听说了 PHP-FPM，然而却没有进行深究，只知道让 Nginx 转发就完事了。

为了进一步的学习 PHP，需要了解 PHP-FPM 是什么。

## CGI 通用网关接口
通用网关接口（Common Gateway Interface，CGI）是一个为用户和 WEB 服务（如 Nginx）与其他应用（如 PHP）提供交互的程序。

早期的 WEB 应用只处理静态的页面，用户访问站点只要请求指定的 `.htm` 或 `.html` 文件，静态文件可以直接输出给浏览器，所以 Nginx 不需要做额外的处理。

随着互联网的发展，只有静态的页面已经不能满足人们的需求了。

Nginx 本身不处理文件，只是分发请求，比如用户请求 `/index.html`，它会去服务器寻找这个文件，找到了就输出给浏览器，没找到就返回 404。这里分发的是静态数据，但如果用户请求的是 `/index.php`，这是一个 PHP 文件，不能像 `.html` 文件一样直接返回给浏览器。

这个时候就犯难了，Nginx 应该如何处理非静态的文件呢？

为了解决如何处理非静态文件的问题，CGI 诞生了。

CGI 做的事情就是解析用户的请求，然后将请求的结果解析成 HTML 返回给浏览器，开发者可以使用任何语言处理 Web Server 发来的请求，生成动态的内容。

上面的例子，用户访问 `/index.php` 文件，由于 Nginx 无法处理这种格式的文件，于是将请求（包括参数等等）转发给 CGI 程序（可理解为语言解释器）进行处理，这里的 CGI 就是 PHP-CGI，PHP-CGI 可以解析 PHP 文件，`index.php` 文件在 PHP-CGI 程序进行解析和处理后才会输出给浏览器。

`index.php` 文件就交给 PHP 程序去处理，`.jsp` 文件就让 Java 去处理，每种动态语言都有对应的 CGI，Nginx 只需要将请求转发给 CGI 就可以了，再通过 CGI 输出数据给浏览器。

## FastCGI 快速网关接口
CGI 在高并发时存在性能问题，作为改进版的 FastCGI 便出现了。

快速网关接口（Fast Common Gateway Interface，FastCGI）是 CGI 的增强版。

### CGI 原理
在接收到请求时，先 fork 出 CGI 进程，然后处理请求，处理完后结束这个进程，这就是 fork-and-execute 模式。

所以用 CGI 方式的服务器有多少连接请求就会有多少 CGI 进程，每个进程都会加载解析配置文件，初始化执行环境，那么当高并发请求时，会大量挤占系统的资源如内存，CPU 等，造成效能低下。

### FastCGI 原理
FastCGI 进程管理器启动时会创建一个主（Master）进程和多个 CGI 解释器进程（Worker 进程），然后等待 Web 服务器的连接。

Web 服务器接收 HTTP 请求后，将 CGI 报文通过 UNIX 或 TCP Socket 进行通信，将环境变量和请求数据写入标准输入，转发到 CGI 解释器进程。

CGI 解释器进程完成处理后将标准输出和错误信息从同一连接返回给 Web 服务器。

CGI 解释器进程变为空闲状态，等待下一个 HTTP 请求的到来。

由于 FastCGI 模式在启动时便创建了很多个子进程，这些子进程常驻内存中，一旦接收到请求就可以立即进入工作状态，而传统的 CGI 模式，只有在接收到请求的时候才会去创建进程，重新读取配置文件等一系列初始化操作，毫无疑问性能会相差很多。

## PHP-FPM FastCGI 进程管理器
PHP 的 FastCGI进程管理器（FastCGI Process Manager，PHP-FPM），PHP-FPM 即 FastCGI 的具体实现。

PHP 的解释器是 PHP-CGI，它本身只会解析请求返回结果，不能进行进程的调度，而 PHP-FPM（进程管理器）所做的事情便是管理进程。

PHP-FPM 包含了一个 `master` 进程和许多个 `worker` 进程，`worker` 进程的数量是可以动态调节的，创建和销毁全部由 `master` 进程来控制。

![image](https://user-images.githubusercontent.com/28209810/64397840-35090980-d095-11e9-959e-eda11f5cb000.png)

其中，`master` 进程负责分发请求，首先 `master` 进程检测是否有可用的 `worker` 进程，如果没有则返回错误（502），然后将请求分发给空闲的 `worker` 进程处理，然后接取下一个请求，再将请求分发给空闲的 `worker`，如果 `worker` 进程处理请求超时则返回错误（504）。

这样的协作方式大大的提高了程序处理并发请求的性能，`worker` 进程的数量可以通过 `php.ini` 文件进行配置。

理论上进程越多，可以处理的请求也越多，但空闲的进程太多反而会造成内存的浪费。

## Nginx 与 PHP-FPM 通信
Nginx 与 PHP-FPM 的通信方式有两种：TCP SOCKET 和 Unix SOCKET。

TCP socket 的优点是可以跨服务器，Nginx 服务器不需要与 PHP-FPM 在同一台服务器上，由于跨服务器的特性，还可以实现分布式部署。

Unix socket 用于实现同一主机上的进程间通信，相较于 TCP socket，Unix socket 跳过了许多验证的步骤，因此 Unix socket 的效率比 TCP socket 要高，但是不稳定。

### TCP socket
一个基于 TCP socket 的 PHP 站点 Nginx 配置：

```
server {
    listen       80;
    server_name  localhost;
    root /www/web;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        try_files $uri =404;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_pass 127.0.0.1:9000;
    }
}
```

为了能够使 Nginx 理解 fastcgi 协议，Nginx 提供了 fastcgi 模块来将 http 请求映射为对应的 fastcgi 请求。

Nginx 的 FastCGI 模块提供了 fastcgi_param 来主要处理这些映射关系，其主要完成的工作是将 Nginx 中的变量翻译成 PHP 中能够理解的变量，fastcgi_param 是一个文件，包含了 Nginx 中的变量映射关系：

```
fastcgi_param  QUERY_STRING       $query_string;
fastcgi_param  REQUEST_METHOD     $request_method;
fastcgi_param  CONTENT_TYPE       $content_type;
fastcgi_param  CONTENT_LENGTH     $content_length;

fastcgi_param  SCRIPT_NAME        $fastcgi_script_name;
fastcgi_param  REQUEST_URI        $request_uri;
fastcgi_param  DOCUMENT_URI       $document_uri;
fastcgi_param  DOCUMENT_ROOT      $document_root;
fastcgi_param  SERVER_PROTOCOL    $server_protocol;
fastcgi_param  REQUEST_SCHEME     $scheme;
fastcgi_param  HTTPS              $https if_not_empty;

fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;
fastcgi_param  SERVER_SOFTWARE    nginx/$nginx_version;

fastcgi_param  REMOTE_ADDR        $remote_addr;
fastcgi_param  REMOTE_PORT        $remote_port;
fastcgi_param  SERVER_ADDR        $server_addr;
fastcgi_param  SERVER_PORT        $server_port;
fastcgi_param  SERVER_NAME        $server_name;
```

除此之外，还有一个重要的指令 `fastcgi_pass`，这个指令用于指定 FPM 进程监听的地址，Nginx 会把所有的 PHP 请求翻译成 FastCGI 请求之后再发送到这个地址。

上面的 Nginx 配置文件中，我们配置了 `fastcgi_pass 127.0.0.1:9000;`，其含义是将请求转发到本机 9000 端口（PHP-FPM 进程）处理，这样的方式叫做 TCP socket。

TCP socket 的好处是可以将 Nginx 服务器与 FPM 服务器进行分离，因此可以实现分布式 PHP-FPM 架构：

![image](https://user-images.githubusercontent.com/28209810/64407389-c5a41180-d0b6-11e9-8d5f-5b5ef39a52ce.png)

配置 `upstream` 来指定 PHP-FPM 服务器：

```
upstream php-fpm {
    server 127.0.0.1:9000;
    server 127.0.0.2:9000;
}

server {
    listen       80;
    server_name  localhost;
    root /www/web;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        try_files $uri =404;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_pass php-fpm;
    }
}
```

### Unix socket
基于 Unix socket 的 Nginx 配置：

```
fasrcgi_pass /usr/run/php-fpm.sock
```

PHP-FPM 配置：

```
listen = 127.0.0.1:9000
# 或者
listen = /var/run/php-fpm.sock
```

> socket 的本质是一个文件，因此还存在权限问题，所以需要注意 Nginx 所在的用户组是否有该文件的操作权限。

Unix socket 通信方式需要在本机生成 sock 文件，因此 Nginx 服务器与 PHP-FPM 必须在同一台机子。

关于两者的取舍，当并发量较小时（比如几百），可以选择 Unix socket 以提高处理效率，在并发量较大时，可以选择 TCP socket 以保持连接的稳定性。