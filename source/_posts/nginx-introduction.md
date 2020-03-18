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
Nginx 是高性能 HTTP 和反向代理 WEB 服务器，还提供了邮件代理服务。

简而言之即**分发服务器请求的软件**。

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

## Nginx 起源
Nginx 是伊戈尔·赛索耶夫为俄罗斯访问量第二的 rambler.ru 站点设计开发的。

第一个公开版本 0.1.0 发布于 2004 年 10 月 4 日。

从发布至今，凭借开源的力量，已经接近成熟与完善。

中国大陆使用 Nginx 网站用户有：百度、京东、新浪、网易、腾讯、淘宝等。

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
    location ~ \.php$ {
        include fastcgi_params;
        try_files $uri =404;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }
```

> 小知识：80 是 http 默认端口，443 是 https 默认端口，访问域名时无需指定端口即可访问，使用其他端口时，就需要在域名后面加上 :端口号才能访问，例如：http://blog.huotuyouxi.com:81

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
location ~ \.php$ {
    include fastcgi_params;
    try_files $uri =404;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
}
```

> 注意！反斜杠 \ 一定要存在，因为 .（点）也是正则表达式，需要加上反斜杠转义才能匹配 .php 后缀 

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
location ~ \.php$ {
    include fastcgi_params;
    try_files $uri =404;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
}
```

进入到这个模块后，实际上是将请求转发给了 PHP-CGI 进行处理，前面几个字段暂时不用理会，只需要知道请求被转发给了 `fastcgi_pass` 这个字段，也就是 `127.0.0.1:9000`（PHP-FPM 本机端口），CGI 处理完请求后将结果返回给客户端。

以上就是 Nginx 解析和分发请求的过程。

## Nginx 匹配规则及优先级
Nginx 配置文件中的 `location` 即匹配规则，匹配规则可以有任意个，按照优先级逐个匹配，匹配成功时停止往下匹配。

### 匹配规则
Nginx 的 `location` 模块包含 4 种匹配标识符：

```
标识符	描述
=	精确匹配：当 $uri 完全匹配。
~	正则匹配：正则表达式匹配 $uri，区分大小写。
~*	正则匹配：正则表达式匹配 $uri，不区分大小写。
^~	非正则匹配：匹配到前缀最多的 $uri 后就结束，该模式匹配成功后，不会使用正则匹配。
```

标识符置于 `location` 语句后面，标识符后面为匹配规则。

示例：

```
location [标识符] <匹配规则> {
    # 匹配成功时执行的代码块
    return 200;
}
```

除了标识符之外，可以不使用标识符进行前缀匹配（最长字符匹配）。

### 匹配优先级

> `location` 的匹配优先级与 `location` 在配置文件的书写顺序无关

Nginx `location` 匹配优先级为：

(location =) > (location 完整路径) > (location ^~ 路径) > (location ~,~* 正则顺序) > (location 部分起始路径) > (location /)

换而言之，即：

（精确匹配）> (最长字符串匹配，但完全匹配) >（非正则匹配）>（正则匹配）>（最长字符串匹配，不完全匹配）>（location 通配）

### “=”精准匹配
使用精准匹配时，URI 必须完全相同才能匹配成功。

如下面的匹配规则，只有 URI 等于 `/php-fpm` 时才会触发成功，模块里的 `return 403;` 将会返回一个 `403 Forbidden` 提示信息，模拟我们不希望用户能直接访问的目录或文件。

```
location = /php-fpm {
    return 403;
}
```

然后把 `server_name` 字段修改为 `localhost` 方便本地调试。

> 修改 Nginx 配置需要重启或平滑重启使配置生效，平滑重启命令：nginx -s reload

请求 URI：`127.0.0.1/php-fpm`

可以使用 `curl` 命令来测试，即 `curl 127.0.0.1/php-fpm`，也可以直接打开浏览器输入这个地址查看结果。

返回结果：403

请求 URI：`127.0.0.1/php-fpm/1.jpg`

匹配失败，`=` 号必须完全匹配。

### “~”正则匹配，区分大小写
`location` 后跟波浪线标识符 `~`，可以实现按照正则表达式规则进行匹配，`~` 波浪线标识符正则匹配时会区分大小写，下面的规则表示不希望用户访问所有后缀为 `.php` 的文件。

```
location ~ \.php$ {
    return 403;
}
```

请求 URI：`127.0.0.1/php-fpm.php`

结果：403

请求 URI：`127.0.0.1/php-fpm.phP`

第二个请求里，最后一个字母 P 为大写，因此匹配失败。

在波浪线前面加上感叹号，形成 `!~` 标识符，表示**不匹配**正则表达式（区分大小写）的规则，也就是跟 `~` 的作用相反。

### “~*”正则匹配，不区分大小写
波浪线后加上星号 `~*` 标识符将不区分大小写进行正则匹配。

```
location ~* .php{
    return 403;
}
```

请求 URI：`127.0.0.1/php-fpm.php`

结果：403

请求 URI：`127.0.0.1/php-fpm.phP`

结果：403

由于使用了不区分大小写的规则，因此最后一个 P 改成大写也能匹配成功。

在波浪线前面加上感叹号，形成 `!~*` 标识符，表示**不匹配**正则表达式（不区分大小写）的规则，也就是跟 `~*` 的作用相反。

### “^~”非正则匹配前缀
“^~” 非正则匹配，后面的参数为匹配的路径，只要 URI 满足了这个前缀就匹配成功。

```
location ^~ /encrpyt/ {
    return 403;
}
```

请求 URI：`127.0.0.1/encrpyt/`

结果：403

请求 URI：`127.0.0.1/encrpyt`

匹配失败，`/encrpyt` 没有满足 `/encrpyt/`，缺少了后面的 `/`。

请求 URI：`127.0.0.1/encrpyt/1.jpg`

结果：403

请求 URI：`127.0.0.1/encrpyt/images/1.jpg`

结果：403

也就是说，只要前缀满足了这个条件就匹配成功，上面的匹配规则含义是 `encrypt` 目录下所有的文件都禁止访问。

### 不使用标识符
不使用标识符即按照最长字符串匹配，优先匹配最长的字符串，只要完全匹配就停止继续往下匹配。

```
location /files/encrypt/ {
    return 403;
}

location /post.php {
    return 403;
}
```

请求 URI：`127.0.0.1/post.php`

首先会查找最长的字符串规则：`/files/encrypt/` 发现不匹配，接着匹配第二长的规则：`/post.php`，匹配成功，返回 403。

请求 URI：`127.0.0.1/files/encrypt/post.php`

首先查找最长字符串规则：`/files/encrypt/` 前缀满足条件，匹配成功，停止往下匹配。

> 注意！测试匹配规则时，如果你拷贝了上面完整的 Nginx 配置文件，.php 结尾是有 location ~ \.php$ 规则的，记得删掉，否则会优先匹配到正则规则，建议测试时删除多余的所有规则，避免干扰结果，只建立一个对照组进行测试

## Nginx 配置文件
Nginx 包括主配置文件与子配置文件，默认路径为：

- /etc/nginx/nginx.conf（主配置）
- /etc/nginx/conf.d/（子配置目录）

Nginx 提供了官方的中文文档可供查阅：[Nginx - 官方中文文档](https://www.nginx.cn/doc/index.html)

### 主配置文件
主配置文件可以让所有子配置文件共享通用的配置，可以定义 Nginx 基本参数等。

编辑主配置文件 `/etc/nginx/nginx.conf`，对应参数及作用如下：

```
# Nginx 运行时的用户和用户组
user  nginx;

# Nginx 工作进程数，推荐设置为 CPU 核心数
worker_processes  1;

# 全局错误日志存储路径及类型，[ debug | info | notice | warn | error | crit ]
error_log  /var/log/nginx/nginx_error.log warn;

# 进程 PID 文件路径
pid        /var/run/nginx.pid;

# 事件模块
events {
    # 每个工作进程的最大连接数
    worker_connections  1024;
}

# HTTP 服务
http {
    # 文件扩展名与文件类型映射表
    include       /etc/nginx/mime.types;
    
    # 默认文件类型
    default_type  application/octet-stream;
 
    # 日志格式
    log_format  main  '$remote_addr - $remote_user [$time_local] "$request" '
                      '$status $body_bytes_sent "$http_referer" '
                      '"$http_user_agent" "$http_x_forwarded_for"';
    # 全局访问日志目录
    access_log  /var/log/nginx/nginx_access.log  main;
    
    # 是否调用 sendfile 函数输出文件
    sendfile        on;
    
    # 此选项仅在使用 sendfile 的时候使用
    #tcp_nopush     on;
 
    # http 层面的连接超时时长（单位：秒），如一个网页打开 65 秒后服务器还没给出响应，则显示超时
    keepalive_timeout  65;
 
    # 是否开启 gzip 压缩，开启时可以压缩文件体积减少网络传输，使得网页打开更快，但是会消耗 CPU 资源
    #gzip  on;
 
    # 读取子配置文件
    include /etc/nginx/conf.d/*.conf;
}
```

`events` 模块包含了 Nginx 处理连接的设置，常见的配置如下：

```
events{
    # 设置网路连接序列化，防止惊群现象发生，默认为 on
    accept_mutex on;
    # 设置一个进程是否同时接受多个网络连接，默认为 off
    multi_accept on;
    # 事件驱动模型，select|poll|kqueue|epoll|resig|/dev/poll|eventport
    use epoll;
    # 最大连接数
    worker_connections  1024;
}
```

Events 模块的更多配置项：[Nginx - 指令](https://www.nginx.cn/doc/core/events.html)

`log_format` 字段可以定义日志的输出格式，具体可设置的参数格式及说明如下：

| 参数 | 说明 | 示例 |
| --- |---|---|
| $remote_addr |	客户端地址 |	219.227.111.255 |
|$remote_user	|客户端用户名称|	—|
|$time_local	|访问时间和时区|	18/Jul/2014:17:00:01 +0800|
|$request	|请求的URI和HTTP协议|	“GET /article-10000.html HTTP/1.1”
|$http_host	|请求地址，即浏览器中你输入的地址（IP或域名）|	www.blog.huotuyouxi.com 198.98.120.87|
|$status	|HTTP请求状态	|200
|$upstream_status	|upstream状态	|200
|$body_bytes_sent	|发送给客户端文件内容大小	|1547
|$http_referer	|url跳转来源	|https://www.google.com/|
|$http_user_agent	|用户终端浏览器等信息	|“Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SV1; GTB7.0; .NET4.0C;
|$ssl_protocol	|SSL协议版本	|TLSv1|
|$ssl_cipher	|交换数据中的算法	|RC4-SHA|
|$upstream_addr	|后台upstream的地址，即真正提供服务的主机地址	|10.36.10.80:80
|$request_time	|整个请求的总时间	|0.165|
|$upstream_response_time	|请求过程中，upstream响应时间	|0.002|

`server` 模块用于配置站点，可以在主配置文件添加，但是后期不方便维护，我们可以单独为每个站点编辑一份配置文件。

### 子配置文件
子配置文件是一个包含 `server` 模块的配置文件，由自己来创建，推荐为每个站点单独创建一个配置文件。

命名规则一般是根据站点名称，如：`blog.huotuyouxi.com.conf`。

当然这个没有严格要求，也可以写成：`blog.conf`。

当子配置文件多的时候方便区分即可。

使用命令 `vim/etc/nginx/conf.d/blog.conf` 来创建博客的配置文件：

```
server {
    listen 80;
    server_name blog.huotuyouxi.com;
    
    return 301 https://blog.huotuyouxi.com$request_uri;
}

server {
    # https
    listen 443 ssl;
    server_name blog.huotuyouxi.com;
    root /www/blog;
    index index.html;
    
    # 访问日志存储位置
    access_log /var/log/nginx/blog_access.log;
    
    # 错误日志存储位置
    error_log /var/log/nginx/blog_error.log;

    # 以下为 SSL 相关配置
    ssl_certificate   /etc/nginx/ssl/3527929_blog.huotuyouxi.com.pem;
    ssl_certificate_key /etc/nginx/ssl/3527929_blog.huotuyouxi.com.key;
    ssl_session_timeout 5m;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE:ECDH:AES:HIGH:!NULL:!aNULL:!MD5:!ADH:!RC4;
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
    ssl_prefer_server_ciphers on;
}
```

上述配置文件将默认的 http 80 端口重定向到了 https 的链接上。

编辑完成后运行：`nginx -s reload` 平滑重启即可使配置生效。

在子配置里可以单独设置访问日志和错误日志，让每个站点都有独立的日志记录文件：

```
# 访问日志存储位置
access_log /var/log/nginx/blog_access.log;

# 错误日志存储位置
error_log /var/log/nginx/blog_error.log;
```

access_log：访问日志主要记录客户端的请求。客户端向 Nginx 服务器发起的每一次请求都记录在这里。客户端 IP，浏览器信息，referer，请求处理时间，请求 URL 等都可以在访问日志中得到。当然具体要记录哪些信息，你可以通过 log_format 指令定义。

```
access_log path [format [buffer=size] [gzip[=level]] [flush=time] [if=condition]]; # 设置访问日志
access_log off; # 关闭访问日志
```

- path 指定日志的存放位置。
- format 指定日志的格式。默认使用预定义的 combined。
- buffer 用来指定日志写入时的缓存大小。默认是 64k。
- gzip 日志写入前先进行压缩。压缩率可以指定，从 1 到 9 数值越大压缩比越高，同时压缩的速度也越慢。默认是 1。
- flush 设置缓存的有效时间。如果超过 flush 指定的时间，缓存中的内容将被清空。
- if 条件判断。如果指定的条件计算为 0 或空字符串，那么该请求不会写入日志。

error_log：记录服务器和请求处理过程中的错误信息。

```
error_log path [level];
```

- path：指定错误日志的存储位置
- level：指定要记录的错误级别，只有发生错误的级别大于等于 level 的时候才会记录错误，可取值为：debug, info, notice, warn, error, crit, alert,emerg，默认值为 error。


如果没有配置单独的日志文件，则会记录在全局日志里。

## 代理服务
代理指的是中介服务。

分为正向代理与反向代理。

### 正向代理
正向代理可以理解成代购模式，例如你的朋友要去国外旅游，于是你拜托他帮忙购买你需要的商品，他从国外买好回来再交给你。

在这里你就是客户端，而你的朋友就是代理服务器。

这样直接由代理服务器去完成某件事的过程，叫做正向代理。

正向代理的应用例子——VPN：

由于国内无法访问到国外的某些网站，比如谷歌；但不是所有的国外服务器都被墙了，因此你可以买一台没有被墙的国外服务器，当你需要访问国外网站的时候，就让服务器去访问，然后再让服务器把结果转发给你。

正向代理的特征是你知道自己委托了谁去干这件事。

### 反向代理
反向代理与正向代理不同的地方在于：客户委托中介完成一件事，结果中介私底下把需求转交给了别人去干，客户不知道究竟是谁帮自己做完了需求，但是得到了自己想要的结果就够了。

好比游戏里面的公会，村民可以把自己的委托贴在公会告示板上，然后公会的看板娘会把委托的需求分配给适合的冒险者来完成。

反向代理的应用例子——负载均衡：

“负载”可以理解成负荷，用户访问一个非静态网站，程序需要读取数据库、渲染 HTML 页面、维持 TCP 连接等操作需要消耗 CPU、内存资源，会给服务器带来一定的负荷。

假如服务器可以承受的压力为 100N（物理学单位）

在某个瞬间，每有一名用户访问这个网站，会给服务器带来 5N 的压力，那么这个瞬间最大承载量就是 20 名用户，当服务器压力超过 100N 时就会崩溃。

要解决这个问题，可以进行硬件提升或者优化项目代码。

硬件提升可以提升服务器最大承受压力值，比如双核的服务器升级到 4 核，使服务器的最大承受压力从 100N 提升到 200N。

软件优化可以减少每个访客造成的压力，比如某些数据库 N+1 的问题严重影响了数据库的性能，造成数据库卡顿，优化了这个问题后，访客造成的压力值从 5N 降低到了 4N。

但是这两种方法提升都有一个临界点，比如硬件优化继续往上提升，服务器的价格就越来越贵，也不可能存在无限核心数的服务器；软件优化到一定程度后已经很难再找到优化的空间。

达到临界值后就无法再继续优化了，单机的性能已经达到了极致。

此时，如果能再买一台同样的服务器并且部署同一套项目，是不是可以使最大承载访客数翻倍呢？

确实可以，只要有两台服务器，那么它们的处理能力就会翻倍！

只要把一半的请求转发给另一台相同的服务器，平均分担压力。

但是应该怎么让请求分别进入到不同的服务器呢？

答案是通过 Nginx，利用上文介绍的反向代理功能。

![负载均衡](http://p3.pstatp.com/large/pgc-image/1534991903053fc12397bd0)

这个过程称为“均衡”，负责维持均衡的那台服务器只负责分发请求，把请求转发给其他能完成功能的服务器处理，由其他服务器返回结果。

负责分发请求的服务器称为“均衡调度器”，Nginx 配置：

```
upstream huotu-server {
    server 192.168.0.14;
    server 192.168.0.15;
    server 192.168.0.16;
}

server {
    listen 80;
    server_name blog.huotuyouxi.com;
    
    location / {
        proxy_pass http://huotu-server;
    }
}
```

`upstream` 字段定义了代理服务器的 IP 地址，当访客进来的时候，Nginx 会按照某种规则将请求分发给其中一个服务器处理。

`server` 模块里通过 `proxy_pass http://huotu-server;` 将匹配到规则的请求转发给代理服务器来处理。

当用户访问：blog.huotuyouxi.com

Nginx 会把请求转发给某台服务器处理，因此每次访问网站看到的返回 IP 可能都会不同。

## Nginx 模块
Nginx 发展迅速的原因除了开源之外，还可以使用官方提供的模块或用户自定义开发的模块，由于模块化使得 Nginx 的定制能力很强，可以使用第三方扩展模块让 Nginx 适应各种场景，有能力的大神也可以分享自己写好的模块让其他人直接使用，像这样可以自由 DIY 的软件，有谁会不喜欢呢？

例如，由淘宝的工程师清无（王晓哲）和春来（章亦春）所开发的 nginx_lua_module 可以将 Lua 语言嵌入到 Nginx 配置中，从而利用 Lua 极大增强了 Nginx 本身的编程能力，甚至可以不用配合其它脚本语言（如 PHP 或 Python 等），只靠 Nginx 本身就可以实现复杂业务的处理。

Nginx 本身支持多种模块，如 HTTP 模块、EVENT 模块和 MAIL 模块等。

前文提到，在 HTTP 请求中，Nginx 的工作仅仅只是分析请求然后转发给匹配规则的 `location` 去处理，`location` 模块中的内容才是真正干活的人。

```
server {
    # 匹配规则
    location / {
        # 实际的工作者
        return 403;
    }
}
```

已安装模块可以用命令：`nginx -V` 查看，输出结果类似如下：

```
built by gcc 6.3.0 20170516 (Debian 6.3.0-18+deb9u1) 
built with OpenSSL 1.1.0j  20 Nov 2018
TLS SNI support enabled
configure arguments: --prefix=/etc/nginx --sbin-path=/usr/sbin/nginx --modules-path=/usr/lib/nginx/modules --conf-path=/etc/nginx/nginx.conf --error-log-path=/var/log/nginx/error.log --http-log-path=/var/log/nginx/access.log --pid-path=/var/run/nginx.pid --lock-path=/var/run/nginx.lock --http-client-body-temp-path=/var/cache/nginx/client_temp --http-proxy-temp-path=/var/cache/nginx/proxy_temp --http-fastcgi-temp-path=/var/cache/nginx/fastcgi_temp --http-uwsgi-temp-path=/var/cache/nginx/uwsgi_temp --http-scgi-temp-path=/var/cache/nginx/scgi_temp --user=nginx --group=nginx --with-compat --with-file-aio --with-threads --with-http_addition_module --with-http_auth_request_module --with-http_dav_module --with-http_flv_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_mp4_module --with-http_random_index_module --with-http_realip_module --with-http_secure_link_module --with-http_slice_module --with-http_ssl_module --with-http_stub_status_module --with-http_sub_module --with-http_v2_module --with-mail --with-mail_ssl_module --with-stream --with-stream_realip_module --with-stream_ssl_module --with-stream_ssl_preread_module --with-cc-opt='-g -O2 -fdebug-prefix-map=/data/builder/debuild/nginx-1.15.12/debian/debuild-base/nginx-1.15.12=. -fstack-protector-strong -Wformat -Werror=format-security -Wp,-D_FORTIFY_SOURCE=2 -fPIC' --with-ld-opt='-Wl,-z,relro -Wl,-z,now -Wl,--as-needed -pie'
```

### 安装模块
Nginx 有许多可以自定义安装的模块，下面以 echo 为例。

echo 仓库地址：[Github - echo-nginx-module](https://github.com/openresty/echo-nginx-module)

安装 echo 模块 `echo-nginx-module` 可以使 Nginx 具有输出字符串的能力，这个功能可以用来简单的调试，如输出参数等。

```
location /hello { 
    return 200 'hello!';
}

location /hello_echo { 
    echo "hello, echo!";
}
```

### 官方模块
官方模块是 Nginx 官方提供的可扩展模块。

例如 Nginx 预制的模块 stub_status 可以用来查看 Nginx 的运行状态：

```
location /test {
    stub_status;
}
```

访问后的输出页面：

```
Active connections: 2 
server accepts handled requests
 3 3 2 
Reading: 0 Writing: 1 Waiting: 1 
```

更多 Nginx 官方模块以使用方法：[http://nginx.org/en/docs/](http://nginx.org/en/docs/)

如上面的 `stub_status`，可以通过查询手册：[ngx_http_stub_status_module](http://nginx.org/en/docs/http/ngx_http_stub_status_module.html) 来获取如何使用。

### 第三方模块
可以通过搜索 Github 寻找来自全世界开发者分享的模块：[Github - Nginx 扩展模块](https://github.com/search?q=nginx+module)

具体的使用方法可以参照作者写的 README 文档。

## Nginx 应用场景
最后再来总结一下，Nginx 具体的应用场景。

### HTTP 服务
Nginx 可以为站点提供 HTTP 服务。

利用 `location` 模块匹配 URI 规则，再将其分发到对应的程序进行处理。

### 负载均衡
Nginx 的反向代理服务，可以实现分发请求的功能。

具体方法是配置 `upstream` 字段：

```
http {
    upstream myproject {
        server 127.0.0.1:8000 weight=3;
        server 127.0.0.1:8001;
        server 127.0.0.1:8002;
        server 127.0.0.1:8003;
    }

server {
    listen 80;
        server_name www.domain.com;
        location / {
            proxy_pass http://myproject;
        }
    }
}
```

负载均衡有以下几种模式：

| 模式 | 说明 |
| --- | --- |
|轮询模式|Nginx 默认配置，当客户端访问服务的时候，请求按时间顺序逐一分配到不同的后端服务器，如果有某台服务器挂了，Nginx 会自动剔除|
|权重模式|可以为每个服务器配置不同的权重，权重值越高的越容易被分发到请求，当多台服务器性能存在差异时，可以让配置高的服务器分配更高的权重|
|IP 哈希|如果客户已经访问了某个服务器，当用户再次访问时，会将该请求通过哈希算法，自动定位到该服务器。解决 Session 丢失的问题，保证每个用户访问到同一台服务器。|
|第三方插件|可以通过安装第三方插件实现更多的模式|

### 防盗链
当自己网站的图片、视频等静态资源被其他人引用时，其他人访问了别人的网站，就会请求到我们服务器上的资源，给我们的服务器带来压力。

Nginx 可以验证来源网站 referers 来判断请求是否是本站点，如果是其他的站点则不让它们获取到图片资源：

```
location ~* \.(gif|jpg|png|swf|flv)$ {
    root html
    valid_referers none blocked *.huotuyouxi.com;
    if ($invalid_referer) {
        return 404;
    }
}
```

### CDN 服务器
可以用 Nginx 搭建 CDN 服务器。

### 限流
Nginx 的 `ngx_http_limit_req_module` 模块可以限制访问频率和并发连接数。

HttpLimit zone：[HttpLimit zone](https://www.nginx.cn/doc/standard/httplimitzone.html)

HttpLimitReqest：[HttpLimitReqest](https://www.nginx.cn/doc/standard/httplimitrequest.html)

### 黑/白名单
可以禁止某些 IP 访问，或允许指定 IP 访问。

```
location / {
    deny 8.8.8.8;
    allow 9.9.9.9;
}
```

### 解决跨域问题
以 Ajax 方式访问非本站点的链接时，浏览器出于安全考虑会禁止访问，也就是跨域问题（HTTP 访问限制，CORS）。

在 Nginx 中只需要配置允许访问的来源类型即可解决跨域问题：

```
# 允许访问的来源，* 指的是全部，可以是 IP 地址或者域名
add_header Access-Control-Allow-Origin *;
# 允许请求的类型
add_header Access-Control-Allow-Headers X-Requested-With;
# 允许请求的方法
add_header Access-Control-Allow-Methods GET,POST,OPTIONS;
```

### 邮件服务
Nginx 提供了邮件代理服务，具体参照官方文档：[Nginx - MailCore](https://www.nginx.cn/doc/mail/mailcore.html)