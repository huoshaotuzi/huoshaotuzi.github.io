---
title: 从零开始搭建自己的Swoole框架（十九）封包，发到composer仓库！
date: 2021-02-19 11:13:19
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 最后一步，将框架封包发布到 composer 提供的仓库。

---
## 前言
过年的假期也结束了，大家都陆续上班了。

2020 年我辞职回家一年也没有实现游戏梦，2021 年还有最后一次为梦想拼搏的机会。

再加上今年还有买房的梦想，所以今年开始没有太多任性的时间了，

如果家里有矿的话，我也想要归隐山林，专心钻研技术，无奈。

今年是毕业第五年的开始，五年……足以让一个人发生巨大的改变，

而我却连毕业时当架构师的梦想都没有实现，于是退而求其次才想要自己写一个框架。

即使是现在雏形完成了，但是技术方面还是没有很大的提升。

原因我很清楚，因为急于求成，因为想要证明自己给某个人看……

后悔的是大学没有好好学习，然悔之无用。

看过一部电视剧，里面有一句台词深深的触动了我：“当你觉得一切都晚了的时候，恰恰是最早的时机。”

在 30 岁之前幡然醒悟，也许是一种值得庆幸的事，如果再晚几年，恐怕翻身的机会只会愈加渺茫。

所以今年，2021 年，我要把握最后一年的机会，尽全力实现自己的游戏梦。

一边实现梦想，一边学好技术，打铁还需自身硬，只有自己变强了，才能掌控自己的生活。

## 封装扩展包
关于 composer 如何发布自己的扩展包，我在去年的时候写过一篇比较详细的文章了。

传送链接：[https://huotublog.com/composer-package/](https://huotublog.com/composer-package/)

## 配置信息
因为用到了变量类型声明和返回值声明：

```
public function test(int num) : int {
    return num;
}
```

这是 PHP7.4 新增的功能，所以要对 PHP 的版本进行限制。

修改框架下的 composer.json 文件：

```
{
  "name": "firerabbit/engine",
  "description": "基于swoole的个人框架。",
  "license": "MIT",
  "authors": [
    {
      "name": "火烧兔子",
      "email": "huoshaotuzi@icloud.com"
    }
  ],
  "require": {
    "php": "^7.4",
    "illuminate/database": "^7.30",
    "xiaoler/blade": "^5.4",
    "monolog/monolog": "^2.2",
    "firebase/php-jwt": "^5.2",
    "phpmailer/phpmailer": "^6.2"
  },
  "autoload": {
    "psr-4": {
      "FireRabbit\\Engine\\": "src/"
    },
    "files": [
      "src/function.php"
    ]
  }
}
```

除了限制 PHP 版本之外，添加了作者和描述信息。

## 项目结构
将框架的目录进行一番修改，大致如下：

![QQ20210219-134228.jpg](https://i.loli.net/2021/02/19/zMH3gN2DjJWoaAU.jpg)

## Github 仓库
Github 上创建一个公开的仓库。

然后将框架的代码上传至仓库。

![QQ20210219-115347.jpg](https://i.loli.net/2021/02/19/JpyXLvh6kqHV9AK.jpg)

## Packagist 仓库
接着发布到 composer 仓库。

地址：[https://packagist.org/](https://packagist.org/)

点击上面的 Submit 按钮，然后把 Github 的仓库地址复制过来，然后提交，检测包名字，没问题就继续点下一步。

等待 composer 抓取 github 的信息，完成之后显示如下界面：

![QQ20210219-120712.jpg](https://i.loli.net/2021/02/19/j6VymxTihnfwUJ8.jpg)

说明已经成功传到 composer 的仓库了，现在这个包可供所有人拉取，但包还未指定版本号，因此仍然无法安装成功。

## 版本号
任何包都需要有一个版本号，第一个版本可以计作：v1.0.0

版本号是个人自定义的，我定义的版本号规则如下：

- 第一个 1，代表大版本，除非框架有翻天覆地的更新，否则这个版本不会改变，一旦大版本号改变就说明原来的代码已经无法保证正常使用了，不应该直接从旧版本升级到新版本，无法保证向下兼容
- 第二个 0，代表中版本号，此版本更新说明添加了一些新功能，但是兼容旧版，可以直接升级
- 第三个 0，代表小版本号，此版本更新说明一些微不足道的改变，例如优化代码或者结构，不影响正常使用，可以直接升级

composer 包的版本是通过 git 标签实现的。

执行命令：

```
git tag -a v1.0.0 -m "初始版本"
git push origin v1.0.0
```

发布一个标签之后，回到 Packagist 页面，看看右下角是否有更新，

如果没有更新，手动点击 Update 按钮，同步完成之后就可以看到右下角多出了一个 v1.0.0。

## 安装
现在就可以从远程仓库直接安装框架包了。

在任意位置创建一个空文件夹，然后进入文件夹，执行命令：`composer require firerabbit/engine`

具体的使用方法可以参照框架的 readme 文件。

## 最后的话
框架部分大致就到这结束了，后续我还会不断更新框架功能。

接下来就要开始使用这个框架雏形来开发一个博客系统了。
