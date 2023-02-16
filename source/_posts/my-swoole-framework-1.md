---
title: 从零开始搭建自己的Swoole框架（一）框架概述
date: 2021-02-04 19:10:12
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 从框架的执行流程开始。

---
## 从入口文件开始说起
## 框架概述
简单地说框架就是封装好各种便利功能的工具，

同时还会“自动化”的处理复杂的事情，

通俗的说就是让程序员开开心心码代码的结构。

我们现在要撸的这套框架，应该满足以下需求：

- 可以缩短开发时间
- 支持后期扩展

不是仅仅封装一下类和方法就叫框架了，

它整体的结构应该让程序员使用起来非常舒服！

## 环境

PHP 版本：7.2.31
Swoole 版本：4.5.2
PHP Redis 版本：5.1.1

这里我用的 redis 是 PHP 的扩展，

如果你不安装 redis 扩展也可以用 composer 引入。

## 框架起名
第一步是给框架起个好名字，

我把这个框架叫做“火兔引擎”，

创建目录：`/firerabbit-engine`。

这个目录名称和位置你可以随意设置，

之后用 nginx 指定就行了，最简单的就是放到 www 目录下。

## 虚拟域名
平时我们可能都是用 `127.0.0.1`，

但是本地的项目多了的话，就不能用单一的地址了。

我们可以设置一个虚拟域名，即修改本地的主机解析记录。

windows 系统和 mac os 都是修改 hosts 文件，

以 mac os 为例：

```
sudo vim /etc/hosts
```

在最底下插入一行：

```
127.0.0.1 firerabbit-engine.ht
```

`firerabbit-engine.ht` 是你设置的虚拟域名，

可以任意设置，但是最好不要是跟真正域名冲突的，

比如你设置了 `baidu.com`，

那你访问百度就会变成解析到自己本机了，

这个 `.ht` 后缀也是我虚构的。

测试的时候就可以在浏览器输入 `firerabbit-engine.ht` 访问博客地址。

## Hello World！
现在项目是空的，一个文件也没有，

首先在项目目录下新建一个 `http_server.php`。

然后查看 Swoole 官方文档：[Http 服务器](https://wiki.swoole.com/#/start/start_http_server)

直接把示例代码抠下来复制到 `http_server.php`：

```
$http = new Swoole\Http\Server('0.0.0.0', 9527);

$http->on('request', function ($request, $response) {
    var_dump($request->server);
    $response->header("Content-Type", "text/html; charset=utf-8");
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});

$http->start();
```

> 注意我这里把默认端口改成 9527 了。

之后可以用 `127.0.0.1:9527` 进行访问。

如果你跟我一样是用 docker 的话，

记得要设置端口映射：`-p 9527:9527`，不然宿主机是访问不到的。

接着在项目的目录下，在控制台输出命令启动程序：`php http_server.php`

你会看到光标卡住了，这说明程序已经运行了。

> swoole 本身就自带了 http 服务器的功能，因此不需要借助 nginx

```
cd /www/firerabbit-engine/
php http_server.php 
```

做完这一步就可以用 IP 地址+端口号的方式访问了，

打开浏览器，输入地址：`127.0.0.1:9527`

可以看到：

```
Hello Swoole. #7090
```

后面的是随机数字所以每次刷新都不一样。

控制台也打印出了请求详情，

这样，第一步就成功了。

## Nginx 转发
虽然 swoole 自带了 http 服务器，

但是 swoole 处理 css、js 等静态文件却没有什么好的方法，

而 nginx 的强项就是处理静态文件，一拍即合，各展所长！

所以这里我们结合 nginx 处理静态文件，

静态文件 nginx 处理，动态文件 swoole 处理；

同时还可以利用上面的虚拟域名来访问网站。

nginx 增加一个配置文件 `firerabbit.conf`：

```
server {
    listen 80;
    server_name firerabbit-engine.ht;

    location ~* \.(gif|jpg|jpeg|png|css|js|ico|ttf|woff|woff2|svg|map)$ {
        root /www/firerabbit-engine/public;
    }

    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        
        if (!-e $request_filename){
            proxy_pass http://php-fpm72:9527; # 注意
        }
    }
}
```

因为我是用 docker 搭建的 nginx 和 php 环境，

容器之间不能直接通过 IP 访问，而是要用容器名。

如果你不是用 docker 环境，php-fpm72 要改成 `127.0.0.1`：

```
proxy_pass http://127.0.0.1:9527;
```

然后执行命令：`nginx -s reload` 来平滑重启 nginx。

接着在浏览器输入上面设置的虚拟域名：`http://firerabbit-engine.ht/`

可以看到同样的 hello world 页面。

通过 nginx 的转发，

所有静态文件如图片、css 文件等都会被转发到请求 public 这个目录，

不会发送到 swoole 那边，只有找不到文件才会转发给 swoole。

捋一捋程序的处理流程：

在浏览器输入域名的时候，用户的请求先经过 nginx 的正则判断，

如果是 jpg 之类的结尾，就去 public 这个目录下面找，

如果找不到文件了，再转发给 `http://php-fpm72:9527`。

这样 nginx 的配置也弄好了。

## 项目结构
### 基本结构
一个典型的框架，包含以下几个特征：

- 单一入口
- MVC 结构（模型、视图、控制器分离）
- 自动加载（composer）

单一入口这个很好理解，

swoole 默认即单一入口，

也就是全部请求都转发给 `http_server.php` 文件处理。

MVC 就是分离代码，让每个类的功能更加单一，

简单的说就是 PHP 写接口，前端人员写前端页面，

但是我们并不会真正的完全分离，因为博客是要做 SEO 的，

如果全部都用接口，搜索引擎就不会收录了。

我们的 MVC 结构会用模板引擎来实现代码解耦。

自动加载是我们这个框架的核心部分，

因为我们会依赖其他组件，同时我们自己的类也需要加上命名空间。

现代 PHP composer 自动加载几乎是必备的。

### 执行流程

捋一捋框架的整体运行流程：

swoole 收到 nginx 转发来的请求，

通过“解析器”将请求 uri 解析成对应的控制器和方法，

（解析器就是路由功能，解析器是一个类文件，自己要写的）

以及获取各种参数、cookie 等等，

将这些参数传递给 controller，

controller 负责处理业务逻辑。

虽然 MVC 框架已经很流行了，

但是我们这里不推荐在 controller 处理逻辑，

在这里写业务，后面这个文件就会变成几千行，不方便维护。

我们再增加一个 Service 层，将业务逻辑的代码移到 Service 去处理。

这样整个框架的流程大致可以捋顺了，如下图：

![流程1.png](https://i.loli.net/2021/02/04/INCJc6vlGMBYH8r.png)

swoole 收到 nginx 转发的请求，

通过解析器（一个类文件），

将请求的 uri 解析成对应的路由和参数，

实例化路由的类并且调用对应的方法，并将参数传递给类的实例化对象，

类的实例化对象（controller）再调用 service 来处理逻辑。

（controller 的作用 与 nginx 类似，也是分发请求，但是它还有一个返回响应的功能）

controller 得到参数和 cookie 等，交给 service 处理，

service 再调用 model 或者其他的类库，返回处理结果，将值返回给  controller，

最后一步，controoler 收到返回的值，再返回对应类型的响应。

（响应的种类有很多，例如 json、html 等等，如：`content-type: text/html; charset=UTF-8`）

原本 controller 是处理逻辑代码的地方，

在这里我们把它变成分发请求了，

这是防止以后 controller 变得臃肿，

但实际上，

逻辑代码转移到 service 会让 service 变得臃肿……

（目前没有更好的方法了）

控制器的处理流程可以看图：

![流程2.png](https://i.loli.net/2021/02/04/cm8xCQ5aLjNYbrK.png)

我个人比较喜欢这种方式，

最终我们写的控制器会是这样的：

```

class IndexController {
    public function index() {
        // 查询列表数据，为了方便调用，service全部做成单例的
        $articles = ArticleService::getInstance()->getList();
        // ... 这里返回视图
        $this->view('index', ['articles' => $articles]);
    }
}

```

控制器的代码会变得十分简洁。

控制器还有一个作用就是验证数据，

比如用户提交的表单，

如果输入的邮箱格式不正确就直接返回错误的响应，

数据验证就全部在 controller 处理了，而不是传到 service。

我把控制器的功能限制为：① 验证数据 ② 转发给 service 处理 ③ 返回结果

而 service 则处理数据库操作之类的业务逻辑。

controller 和 service 的功能变得十分单一，从而降低耦合性。

问题就是，控制器干净了，service 却脏了……

service 不仅要处理数据库，还要更新缓存之类的，想想就可怕。

后面我们可以考虑把数据库处理的逻辑转移到 model，

这样可以减少 service 臃肿度。

## 第一阶段目标
现在思路已经很明确了，

但是很多细节部分我们还没有设计，

比如日志系统、缓存系统、数据库系统、配置参数文件、中间件……

下期统一进行规划，本篇文章就到这了。
