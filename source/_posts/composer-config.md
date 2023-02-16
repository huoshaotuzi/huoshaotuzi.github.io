---
title: composer配置参数详解
date: 2020-03-08 14:48:10
tags:
 - composer
 
categories: composer
 
description: composer.json 文件参数详解。
 
---
## composer.json
Composer 使用配置文件 `composer.json` 来指明依赖关系或者包信息。

一个简单的 `composer.json` 配置文件如下所示：

```
{
  "require": {
    "phpmailer/phpmailer": "^6.0",
  },
  "require-dev": {
    "robmorgan/phinx": "^0.10.8"
  },
  "autoload": {
    "psr-4": {
      "App\\Library\\": "application/library",
    }
  },
  "repositories": {
    "packagist": {
      "type": "composer",
      "url": "https://mirrors.aliyun.com/composer/"
    }
  }
}
```

## 开发配置项
如果你不打算将自己的包开源，或者这个配置文件并不是一个库，而是你的项目依赖第三方包的配置文件，那么许多字段都是不需要用到的，以下是几个比较核心的配置字段。
### require : 项目依赖关系
执行 `composer install` 或者 `composer update` 将会下载依赖的包。

示例：

```
{
  "require": {
    "phpmailer/phpmailer": "^6.0",
  }
}
```

这里我们声明了我们需要下载安装的包 `phpmailer/phpmailer` 和对应的版本信息 `^6.0`。

`require` 也可以用来指明 PHP 的版本信息。

示例：

```
"require": {
   "php": ">=5.5.0",
}
```

它要求使用者的 PHP 版本至少是 5.5.0 以上的。

### require-dev : 只在开发环境的依赖
有时候，我们可能需要一些帮助我们调试的第三方包，但是线上环境并不需要用到这些包，这个时候可以将它们放在 `require-dev` 中进行声明：

```
{
  "require-dev": {
    "phpmailer/phpmailer": "^6.0",
  }
}
```

**线上环境**在执行 `composer install` 或者 `composer update` 的时候，**需要添加 `--no-dev` 参数**来跳过 `require-dev` 依赖的包。

如果直接使用 `composer install`，则 `require-dev` 依赖的包也会被安装。

### autoload : 自动加载
通过配置 `autoload` 可以实现类的自动加载。

示例：

```
{
  "autoload": {
    "psr-4": {
      "App\\Library\\": "application/library",
    }
  }
}
```

上面的例子中，我们使用了 `psr-4` 的自动加载规范来加载 `library` 中的类。

除了 `psr-4` 还有几种可选的类型：

- psr-0
- classmap
- files

### repositories : 仓库地址
声明依赖所在仓库的地址，默认情况下使用 Packagist 官方网站：https://packagist.org。

国内镜像源：

```
// 阿里
https://mirrors.aliyun.com/composer

// Composer 中文网
https://packagist.phpcomposer.com
```

此外，还可以搭建自己的仓库地址。

示例：

```
"repositories": {
    "packagist": {
      "type": "composer",
      "url": "https://mirrors.aliyun.com/composer/"
    }
  }
```

支持以下类型（type）的包资源库：

- composer: 一个 composer 类型的资源库，是一个简单的网络服务器（HTTP、FTP、SSH）上的 packages.json 文件，它包含一个 composer.json 对象的列表，有额外的 dist 和/或 source 信息。这个 packages.json 文件是用一个 PHP 流加载的。你可以使用 options 参数来设定额外的流信息。
- vcs: 从 git、svn 和 hg 取得资源。
- pear: 从 pear 获取资源。
- package: 如果你依赖于一个项目，它不提供任何对 composer 的支持，你就可以使用这种类型。你基本上就只需要内联一个 composer.json 对象。

## 开源项目配置项
如果你的包希望上传到 Packagist 提供给他人使用，需要提供包的基本信息，如作者、包的描述等等。

一个开源的项目，[PHPMailer](https://github.com/PHPMailer/PHPMailer/blob/master/composer.json) 的配置文件示例：

```
{
    "name": "phpmailer/phpmailer",
    "type": "library",
    "description": "PHPMailer is a full-featured email creation and transfer class for PHP",
    "authors": [
        {
            "name": "Marcus Bointon",
            "email": "phpmailer@synchromedia.co.uk"
        },
        {
            "name": "Jim Jagielski",
            "email": "jimjag@gmail.com"
        },
        {
            "name": "Andy Prevost",
            "email": "codeworxtech@users.sourceforge.net"
        },
        {
            "name": "Brent R. Matzelle"
        }
    ],
    "require": {
        "php": ">=5.5.0",
        "ext-ctype": "*",
        "ext-filter": "*"
    },
    "require-dev": {
        "friendsofphp/php-cs-fixer": "^2.2",
        "phpdocumentor/phpdocumentor": "2.*",
        "phpunit/phpunit": "^4.8 || ^5.7",
        "zendframework/zend-serializer": "2.7.*",
        "doctrine/annotations": "1.2.*",
        "zendframework/zend-eventmanager": "3.0.*",
        "zendframework/zend-i18n": "2.7.3"
    },
    "suggest": {
        "psr/log": "For optional PSR-3 debug logging",
        "league/oauth2-google": "Needed for Google XOAUTH2 authentication",
        "hayageek/oauth2-yahoo": "Needed for Yahoo XOAUTH2 authentication",
        "stevenmaguire/oauth2-microsoft": "Needed for Microsoft XOAUTH2 authentication",
        "ext-mbstring": "Needed to send email in multibyte encoding charset",
        "symfony/polyfill-mbstring": "To support UTF-8 if the Mbstring PHP extension is not enabled (^1.2)"
    },
    "autoload": {
        "psr-4": {
            "PHPMailer\\PHPMailer\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "PHPMailer\\Test\\": "test/"
        }
    },
    "license": "LGPL-2.1-only"
}
```

### name : 包名
包的名称，它包括供应商名称和项目名称，使用 / 分隔，左边为供应商名称，右边为项目名称。

示例：

```
monolog/monolog
igorw/event-source
```

对于需要发布的包（库），这是必须填写的。

### description : 描述
一个包的简短描述，通常用来描述包的功能，最长只有一行。

对于需要发布的包（库），这是必须填写的。

### version : 版本
version 不是必须的，并且建议忽略。

它应该符合 'X.Y.Z' 或者 'vX.Y.Z' 的形式， -dev、-patch、-alpha、-beta 或 -RC 这些后缀是可选的。在后缀之后也可以再跟上一个数字。

示例：

- 1.0.0
- 1.0.2
- 1.0.0-dev
- 1.0.0-alpha3
- 1.0.0-beta2
- 1.0.0-RC5

### type : 安装类型
包的安装类型，默认为 library。

composer 原生支持以下4种类型：

- library: 这是默认类型，它会简单的将文件复制到 vendor 目录。
- project: 这表示当前包是一个项目，而不是一个库。例：框架应用程序 Symfony standard edition，内容管理系统 SilverStripe installer 或者完全成熟的分布式应用程序。使用 IDE 创建一个新的工作区时，这可以为其提供项目列表的初始化。
- metapackage: 当一个空的包，包含依赖并且需要触发依赖的安装，这将不会对系统写入额外的文件。因此这种安装类型并不需要一个 dist 或 source。
- composer-plugin: 一个安装类型为 composer-plugin 的包，它有一个自定义安装类型，可以为其它包提供一个 installler。详细请查看 自定义安装类型。
仅在你需要一个自定义的安装逻辑时才使用它。建议忽略这个属性，采用默认的 library。

### keywords : 关键字
**非必选，但建议填写。** 该包相关的关键词的数组，可用于搜索和过滤，相当于在 composer 中的 SEO，有助于让更多的人搜索到你的包。

### homepage : 项目主页
该项目网站的 URL 地址，可选。

### time : 版本发布时间
必须符合 YYYY-MM-DD 或 YYYY-MM-DD HH:MM:SS 格式，可选。

### license : 许可协议
包的许可协议，它可以是一个字符串或者字符串数组。

最常见的许可协议的推荐写法：

- Apache-2.0
- BSD-2-Clause
- BSD-3-Clause
- BSD-4-Clause
- GPL-2.0
- GPL-2.0+
- GPL-3.0
- GPL-3.0+
- LGPL-2.1
- LGPL-2.1+
- LGPL-3.0
- LGPL-3.0+
- MIT

可选，但强烈建议提供此内容。

### authors : 作者
包的作者，这是一个对象数组。

这个对象必须包含以下属性：

- name: 作者的姓名，通常使用真名。
- email: 作者的 email 地址。
- homepage: 作者主页的 URL 地址。
- role: 该作者在此项目中担任的角色（例：开发人员 或 翻译）。

示例：

```
{
    "authors": [
        {
            "name": "Nils Adermann",
            "email": "naderman@naderman.de",
            "homepage": "http://www.naderman.de",
            "role": "Developer"
        },
        {
            "name": "Jordi Boggiano",
            "email": "j.boggiano@seld.be",
            "homepage": "http://seld.be",
            "role": "Developer"
        }
    ]
}
```

可选，但强烈建议提供此内容。

### support : 支持信息
获取项目支持的向相关信息对象。

这个对象必须包含以下属性：

- email: 项目支持 email 地址。
- issues: 跟踪问题的 URL 地址。
- forum: 论坛地址。
- wiki: Wiki 地址。
- irc: IRC 聊天频道地址，类似于 irc://server/channel。
- source: 网址浏览或下载源。

示例：

```
{
    "support": {
        "email": "support@example.org",
        "irc": "irc://irc.freenode.org/composer"
    }
}
```

可选。

### minimum-stability (root-only)
这定义了通过稳定性过滤包的默认行为。默认为 stable（稳定）。因此如果你依赖于一个 dev（开发）包，你应该明确的进行定义。

对每个包的所有版本都会进行稳定性检查，而低于 minimum-stability 所设定的最低稳定性的版本，将在解决依赖关系时被忽略。对于个别包的特殊稳定性要求，可以在 require 或 require-dev 中设定。

可用的稳定性标识：dev、alpha、beta、RC、stable。


### prefer-stable (root-only)
当此选项被激活时，Composer 将优先使用更稳定的包版本。

使用 "prefer-stable": true 来激活它。