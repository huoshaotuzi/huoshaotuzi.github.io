---
title: Gitlab + Satis 搭建私有 Composer 仓库
date: 2020-03-07 22:21:18
tags:
 - docker
 - composer
 - gitlab

categories: composer
 
description: 搭建一套属于自己的composer私有仓库。

---
## Satis 介绍

Satis 是开源的静态 Composer 仓库生成器，可用于托管公司私有包的元数据。

环境要求：PHP >= 7.2

## 安装 Gitlab

Centos 搭建 Gitlab：[Centos 系统安装 Gitlab](/centos-install-gitlab)
Docker 搭建 Gitlab：[再战！Docker 安装 Gitlab](/docker-install-gitlab)

## 上传 Composer 包
Composer 包开发教程：[从零开始开发自己的 Composer 包](/composer-package)

在 Gitlab 新建一个仓库，把自己开发完成的包上传到这个仓库，上传完成后的仓库如下：

![image](https://user-images.githubusercontent.com/28209810/64165187-204f2a80-ce77-11e9-9532-fbe4a9239a37.png)

包的配置文件 `composer.json` 如下：

![image](https://user-images.githubusercontent.com/28209810/64166482-c7cd5c80-ce79-11e9-8b25-7719a08c156e.png)

包的名字叫做：`huotu/test`，我们后面需要用到。

这个仓库的地址就是我们私有包的地址，接下来拿这个包作为演示。

## 安装 Satis
可以使用两种方式安装 Satis。

### 1、Composer 安装
可以直接使用 `composer` 命令安装 Satis： 

```
composer create-project composer/satis --stability=dev --keep-vcs
```

### 2、从 GitHub 下载
使用 `git clone` 将 Satis 下载到本地：

```
git clone https://github.com/composer/satis.git
```

## 添加 Satis 配置文件
在下载好的 satis 目录下，创建 `satis.json` 配置文件，一个示例的配置文件如下：

```
{
  "name": "My Repository",
  "homepage": "http://packages.example.org",
  "repositories": [
    { "type": "vcs", "url": "https://github.com/mycompany/privaterepo" },
    { "type": "vcs", "url": "http://svn.example.org/private/repo" },
    { "type": "vcs", "url": "https://github.com/mycompany/privaterepo2" }
  ],
  "require": {
    "company/package": "*",
    "company/package2": "*",
    "company/package3": "2.0.0"
  },
  "require-all": false
}
```

- name：仓库的名字，将会展示在页面上
- homepage：satis 访问地址
- repositories：包所在的地址
- require：获取指定的包
- require-all：如果为 true 表示获取所有包

根据自己的情况进行配置，这里我们拿刚刚上传到 Gitlab 的包演示，配置如下文件：

```
{
  "name": "My Repository",
  "homepage": "http://satis.com",
  "repositories": [
    { "type": "vcs", "url": "http://gitlab.com/huotu/test" }
  ],
  "require-all": false
}
```

`http://satis.com` 为 satis 访问页面地址，`http://gitlab.com/huotu/test` 为私有包所在地址。

## Composer 配置
由于我们使用 `http`，在这里需要修改设置：

```
composer config -g secure-http false
```

## 生成 Satis 索引页面

在 satis 目录下执行命令 `composer install` 安装所需依赖，然后再执行如下命令生成 satis 页面：

```
php bin/satis build <configuration file> <build dir>

# 示例 ：
php bin/satis build satis.json public/

# 跳过 Gitlab 密码验证
php bin/satis build -n satis.json public/
```

执行完命令后，在当前目录生成了 public 文件夹，接着配置 nginx，将域名指向这个目录：

```
server {
    listen 80;
    server_name satis.com;
    root /www/satis/public;

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

重启 nginx，不出意外就可以通过域名访问 satis 了！

> 使用域名记得添加解析到服务器

访问 `http://satis.com`（这个是你配置的域名）：

![image](https://user-images.githubusercontent.com/28209810/64164278-8044d180-ce75-11e9-8b1a-9e81d905418c.png)

## Composer 使用私有仓库
我们现在完成了 Satis + Gitlab 的全部安装，接下来我们的项目就可以使用自己搭建的私有 Composer 源了。

新建一个 test 文件夹，在 test 目录下创建 `compsoer.json`，编辑 `composer.json` 添加如下内容：

```
{
    "require": {
        "huotu/test": "*"
    },
    "repositories": [{
        "type": "composer",
        "url": "http://satis.com"
    }]
}
```

保存，然后在当前目录下执行 `composer install`：

![image](https://user-images.githubusercontent.com/28209810/64166283-58576d00-ce79-11e9-9cf3-233193e4963e.png)

成功把自己私有仓库的包下载下来了。

如果有多个包，则添加多个仓库地址：

```
{
  "name": "My Repository",
  "homepage": "http://satis.com",
  "repositories": [
    { "type": "vcs", "url": "http://gitlab.com/huotu/test" },
    { "type": "vcs", "url": "http://gitlab.com/huotu/test2" },
    { "type": "vcs", "url": "http://gitlab.com/huotu/test3" },
  ],
  "require-all": false
}
```

## 缓存包资源
可以把所需要的包都缓存在本地 Satis 上，这样可以避免每次都需要从仓库中 clone，在 `satis.json` 添加：

```
{
    "archive": {
        "directory": "dist",
        "format": "tar",
        "prefix-url": "http://satis.com",
        "skip-dev": true
    }
}
```

`archive` 参数：

- directory: 表示生成的压缩包存放的目录，会在我们 build 时的目录中
- format: 压缩包格式，zip（默认） tar
- prefix-url: 下载链接的前缀的 Url, 默认从 homepage 中取
- skip-dev: 默认为 false，是否跳过开发分支
- absolute-directory: 可选，包文件存储到绝对路径的目录
- whitelist: 可选，如果设置为包名称列表，则只会转储这些包的 dist 文件
- blacklist: 可选，如果设置为包名称列表，则不会转储这些包的 dist 文件
- checksum: 可选，默认情况下为 true，禁用时（false）不会为 dist 文件提供 sha1 校验 启用后，所有下载（包括来自 GitHub 和 BitBucket 的下载） 将替换为本地版本。

添加 `archive` 后，配置的包信息就会下载到本地 Satis 目录下 dist 文件夹中，从 Satis 下载依赖时将从这个文件夹获取资源。

## 定期更新 Satis

需要定期执行 `php bin/satis build satis.json public/` 命令来生成最新的 Composer 包信息，可以将此命令作为定期任务执行，或是增加一个钩子 push 来更新 Satis。

