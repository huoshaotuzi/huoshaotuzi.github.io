---
title: 网站性能优化之静态资源加速
date: 2021-03-24 22:04:35
tags:
 - 前端

categories: 前端技术

description: 让静态资源起飞。
---

## 前言
图片、css、js 等等不会在用户访问时改变的资源统称静态资源。

网站的打开速度除了跟代码有关，静态资源也会拖后腿，优化静态资源加载速度比起优化代码更能显著提高访问速度。

## WEB 服务器
Apache 和 Nginx 是常用的 WEB 服务器。

Nginx 处理静态资源的速度比 Apache 更快，所以选择 Nginx 可以提高静态资源访问速度。

Nginx 可以配置静态资源缓存，参考资料：[Nginx 静态资源缓存设置
](https://www.w3cschool.cn/nginxsysc/nginxsysc-cache.html)

## CDN
两台电脑的远近距离会影响到访问速度，例如在大陆地区访问国外的服务器就会延迟。

CDN 即内容分发网络，它会根据就近原则为你分配访问的节点。

比如在广州有一台服务器，上面保存着图片 A，在美国也有一台服务器，也保存着相同的图片 A，假设你的网站客户群体是全球范围，那么让国内用户访问广州的服务器上面的图片 A，美国用户访问美国服务器的图片 A，这样访问速度就是最快的。

更细分一点，在广州、上海、福州、香港……等等很多个地区都有一台服务器，这些服务器都保存着图片 A，这样广州的人访问广州的服务器，上海的人访问上海的服务器……这就是内容分发，就近原则访问资源。

我们个人是没办法做到那么多节点的，只有依靠服务商。

CDN 的原理就是负载均衡，根据 IP 来分配节点。

有很多免费的 CDN 服务商，推荐使用 jsdelivr + Github 来作为个人的 CDN。

Github：[http://github.com/](http://github.com/)

首先到 Github 创建一个账号，然后新建一个仓库，上传一张图片，比如图片名字为：avatar.png。

然后就可以直接用 jsdelivr 加载这张图片了。

格式：`https://cdn.jsdelivr.net/gh/你的用户名/你的仓库名@发布的版本号/文件路径`

版本号不是必须的，但是加上去可以防止用户本地缓存了资源导致没有更新，你可以随意编写自己的版本号，如：@1.0，不过如果你要加上版本号的话，需要用 git 命令打一个版本的标签。

示例：[https://github.com/laravel/laravel](https://github.com/laravel/laravel)

这是 Laravel 框架的仓库地址，仓库根目录下有一个 `webpack.mix.js` 文件。

那么可以访问下面的地址：

```
https://cdn.jsdelivr.net/gh/laravel/laravel/webpack.mix.js
https://cdn.jsdelivr.net/gh/laravel/laravel@8.5.15/webpack.mix.js
```

这样就 OK 了！

上面的 @8.5.15 是 Laravel 框架已经打好的版本标签。

在仓库的 tags 页面可以查看所有标签 ：[https://github.com/laravel/laravel/tags](https://github.com/laravel/laravel/tags)

默认不写标签就是引用最新版。

前端用的最多的 js 即 JQeury 了，仓库地址：[https://github.com/jquery/jquery](https://github.com/jquery/jquery)

可以试着拿这个仓库练习一下，首先是确定自己要用的 JQuery 版本：[https://github.com/jquery/jquery/tags](https://github.com/jquery/jquery/tags)

例如我要引用最新的 v3.6.0，然后发现 dist 里有不同类型的文件：[https://github.com/jquery/jquery/tree/3.6.0/dist](https://github.com/jquery/jquery/tree/3.6.0/dist)

带有 min 即压缩过的，一般我们都是引用这个。 

可以用下面的链接：

```
<script src="https://cdn.jsdelivr.net/gh/jquery/jquery@3.6.0/dist/jquery.min.js"></script>
```

CDN 不止可以用 js、css 等，还可以把图片也上传到自己的 github 仓库，用相同的办法引入即可。

> 需要注意的是 jsdelivr 可能不太稳定，一旦它挂了你的网站资源就加载不出来了，这也是用 cdn 的风险

## 图片加载优化

推荐两个免费图床：

SM.MS：[http://sm.ms/](http://sm.ms/)

牛图网：[http://niupic.com/](http://niupic.com/)

把图片上传到这两个网站，它们自带 CDN。

上传图片之前，可以到 TinyPng：[https://tinypng.com/](https://tinypng.com/) 将图片压缩一下，体积减小之后访问的速度也就 更快了。

> 需要注意的是压缩画质会受损，请根据实际需求选择是否压缩

除此之外，图片的 jpg 格式比起 png 更小，因为 jpg 是压缩的格式，而 png 可以保留透明背景，如果不需要透明背景可以将图片压缩为 jpg 格式。
