---
title: 一文读懂Nginx
date: 2020-03-10 22:20:15
tags:
 - Nginx
 - Linux
 
categories: Nginx

description: 一篇文章学会侃侃而谈 Nginx。
 
---

## Nginx 是什么？
Nginx 是高性能 HTTP 和反向代理 WEB 服务器，同时还提供了邮件相关服务。

一语概之，即分发服务器请求的软件。

访问一个网站的本质：
- 在浏览器输入网址
- DNS 服务商将网址解析成服务器 IP 地址
- 访问此 IP 的服务器
- 服务器收到请求，建立连接
- 服务器上的 Nginx 解析请求并转发给对应程序处理
- 程序处理请求
- 程序返回请求的结果（响应）

在此过程，Nginx 负责分发请求给指定的程序处理。

Nginx 在分发请求的时候，会携带请求参数和请求头等其他信息，Nginx 自身无法处理请求，它只是将请求转发给对应程序处理，如果是 PHP 搭建的网站，则转发给 PHP-CGI，由 PHP-CGI 返回结果给客户端。

> PHP-CGI 是处理 PHP 文件的程序

## URI 是什么？
在了解 Nginx 是如何分发请求之前需要先了解什么是 URI。

URI 统一资源标识符(Uniform Resource Identifier， URI)，它由三个部分组成：

- 协议
- 主机
- 资源路径

示例：

```
https://blog.huotuyouxi.com/img/1.jpg
```

- https：协议
- blog.huotuyouxi.com：主机
- /img/1.jpg：资源路径

上面也提到了，访问网址的本质是访问服务器上某个文件，示例的网址访问 blog.huotuyouxi.com 所在服务器上的 1.jpg 这个文件。

如果这样理解不能，换句话说：

```
F:\games\Lol\Lol.exe
```

访问服务器上的文件与访问本地 F 盘并无太大的差别，区别在于访问网址相当于访问远程服务器上的文件。

通俗的讲 URI 就是远程服务器文件的路径。

至于协议部分，协议即一种人为约定的规则，除了 http、https 协议，还有 ftp、sftp 等等各种协议，请求协议跟后文没有多大关系，有兴趣可以自行查阅当做扩展阅读。

## Nginx 如何分发请求？
客户端请求 URI 对应的文件，Nginx 是如何处理的呢？

在分发请求之前，Nginx 首先需要解析请求。

假如我们把域名：blog.huotuyouxi.com 解析到 IP 为 xxx.xxx.xxx.xxx 的服务器上。

然后在该服务器上安装 Nginx，并且添加如下配置文件：

```
server {
    # 监听 80 端口
    listen 80;
    
    # 对应的域名
    server_name  blog.huotuyouxi.com;
    
    # 项目根目录
    root /www/blog/public;

    # 访问日志存储位置
    access_log /var/log/nginx/blog_access.log;
    
    # 错误日志存储位置
    error_log /var/log/nginx/blog_error.log;

    # 字符集
    charset utf-8;

    # 匹配 URI 以 / 开头（因为所有的 URI 都是以 / 开头，所以会匹配到所有请求）
    location / {
        # 尝试获取这几种文件
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # 匹配后缀为 .php 的请求
    location ~ .php$ {
        include fastcgi_params;
        try_files $uri =404;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }
```

> 小知识：80、443 两个端口是默认端口，访问域名时无需指定端口即可访问，使用其他端口时，就需要在域名后面加上 :端口号才能访问，例如：blog.huotuyouxi.com:81

`location` 是 Nginx 主要的模块之一，用来匹配 URI，满足条件时进入到模块内执行，我们在这个配置文件里设置了两个 `location` 模块匹配资源文件。

第一个 `location` 定制了规则 `/`，匹配以 `/` 开头的所有 URI，由于所有的 URI 都是以 / 开头，所以会匹配到所有请求。

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

`try_files` 按顺序检查文件是否存在，返回第一个找到的文件，至少需要两个参数，当前面的文件都找不到时，会内部重定向到最后一个参数：

- $uri
- $uri/
- /index.php?$query_string

`$uri` 指的是完全匹配的文件，`$uri/` 指的是文件夹，当前面两个都没有时，会访问 `/index.php`，而后面的 `?$query_string` 指的是携带请求参数，如果不携带参数，使用 `$_GET` 会获取不到任何参数。

访问目录是以配置的项目相对路径：

```
root /www/blog/public;
```

最后的值访问 `index.php` 即访问 `/www/blog/public/index.php`。

接着第二个 `location`，`~` 是一种标识符，用于正则匹配 URI，区分大小写，正则匹配规则为：`.php$` 也就是以 `.php` 结尾的文件，当满足条件时进入此模块。

```
location ~ .php$ {
    include fastcgi_params;
    try_files $uri =404;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
}
```

`location` 在配置文件里的前后顺序并不是匹配的顺序，`location` 匹配的规则及顺序后文介绍，在这个例子中 `/` 的匹配优先级低于 `~`，也就是先匹配 `.php` 后缀的文件，如果匹配不到再匹配 `/` 规则。

假如某个用户访问：blog.huotuyouxi.com/php-fpm

此时，在 Nginx 中的处理流程是：

首先匹配到了 `server_name`：blog.huotuyouxi.com，进入当前配置文件进行处理。

接着解析 $uri（文件路径）即 `server_name` 后面的部分 `/php-fpm`，然后优先匹配规则 `~ .php`，发现它并没有 `.php` 结尾，接着往下级匹配 `/`，满足条件，进入到该模块：

```
location / {
    # 尝试获取这几种文件
    try_files $uri $uri/ /index.php?$query_string;
}
```

try_files 会尝试获取 `/www/blog/public/php-fpm` 文件，发现没有，继续匹配第二个参数；`$uri/` 比之前的参数多加了一个 `/` 结尾，指的是文件夹，发现也没有 `/www/blog/public/php-fpm` 这个目录，前面两个参数都匹配完了，因此请求会变为内部重定向到最后一个参数 `index.php` 这个文件，然后重新开始匹配，此时正好匹配了规则 `.php` 结尾，进入 `location ~ .php` 模块：


```
location ~ .php$ {
    include fastcgi_params;
    try_files $uri =404;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
}
```

进入到这个模块后，实际上是将请求转发给了 PHP-CGI 进行处理，前面几个字段暂时不用理会，只需要知道请求被转发给了 `fastcgi_pass` 这个字段，也就是 `127.0.0.1:9000`（PHP-FPM 本机端口），CGI 处理完请求后将结果返回给客户端。

以上就是 Nginx 解析和分发请求的过程。

## Nginx 匹配规则及优先级
Nginx 的 `location` 模块包含 4 种匹配标识符：

```
标识符	描述
=	精确匹配：当 $uri 完全匹配。
~	正则匹配：正则表达式匹配 $uri，区分大小写。
~*	正则匹配：正则表达式匹配 $uri，不区分大小写。
^~	非正则匹配：匹配到前缀最多的 $uri 后就结束，该模式匹配成功后，不会使用正则匹配。
```

除了标识符之外，可以不使用标识符进行前缀匹配（最常字符匹配），与 location 顺序无关，是按照匹配的长短来取匹配结果。若完全匹配，就停止。

### 精准匹配“=”

```
location = /php-fpm {
    echo "ok";
}
```

请求 URI：blog.huotuyouxi.com/php-fpm

结果：ok

请求 URI：blog.huotuyouxi.com/php-fpm/1.jpg

匹配失败，`=` 号必须完全匹配。

### 正则匹配，区分大小写“~”

```
location ~ .php$ {
    echo 'little';
}
location ~ .PHP$ {
    echo 'big';
}
```

请求 URI：blog.huotuyouxi.com/php-fpm.php

结果：little

请求 URI：blog.huotuyouxi.com/php-fpm.PHP

结果：big

### 正则匹配，不区分大小写“~*”

```
location ~* .php{
    echo 'ok';
}
```

请求 URI：blog.huotuyouxi.com/php-fpm.php

结果：ok


请求 URI：blog.huotuyouxi.com/php-fpm.PHP

结果：ok

### 非正则匹配前缀

```
location ^~ /php/ {
    echo 'ok';
}
```

请求 URI：blog.huotuyouxi.com/php/php-fpm.php

结果：ok

请求 URI：blog.huotuyouxi.com/php/book.php

结果：ok

只要满足以 `/php/` 开头即满足条件。