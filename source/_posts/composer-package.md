---
title: 从零开始开发自己的Composer包
date: 2020-03-08 14:42:18
tags:
 - composer
 
categories: composer
 
description: 在使用 composer 的时候，我们几乎都是在用其他人分享出来的包，久而久之，难免会产生开发自己包的想法，不仅可以加深对 composer 的理解程度，同时还可以把自己常用的功能封装起来，作为自己的“小金库”储备起来。
 
---
## 前言
在使用 `composer` 的时候，我们几乎都是在用其他人分享出来的包，久而久之，难免会产生开发自己包的想法，不仅可以加深对 `composer` 的理解程度，同时还可以把自己常用的功能封装起来，作为自己的“小金库”储备起来。

> 使用 `composer` 开发依赖包是一项非常考验能力的事情，它涉及到一个微型系统的架构，阅读**设计模式**有助于帮助我们开发一个优秀的 `composer` 依赖包。

## 开发自己的第一个 Composer 包
`composer.json` 是 `composer` 的基础，文件夹目录下存在 `composer.json` 文件，那么这个文件夹就是一个**资源包**。

我们可以手动创建 `composer.json` 文件，不过，我们推荐使用 `composer init` 命令。

> composer init 命令帮助我们自动生成 composer.json，文件可以修改，不必担心按错了什么会产生不好的影响。

## 创建配置文件

`composer.json` 配置项的字段较多，后续步骤如果对配置文件的字段抱有疑问，可以返回此处查看：[composer.json 详解](/composer-config)。

现在，不需要了解这些。

### 创建包目录
我们的操作是在命令行界面操作的，如果是 Windows 系统，则需要进入 CMD 界面，进入到你的工作磁盘目录下，使用快捷键 `Shift + 鼠标右键`，在菜单栏中选择进入命令行（DOS 界面）。

首先，我们需要创建一个包的目录 `packagist`：

```
# Mac OS 系统
mkdir packagist

# Windows 系统(可以直接右键创建）
md packagist

# 创建完成后进入文件夹
cd packagist
```

### 输入包的名字
在 `packagist ` 目录下，执行 `composer init`，`composer` 会提示我们设置配置参数信息，如下图：

![image.png](https://i.loli.net/2019/08/24/NSPn6GYuE83jcs1.png)

```
Package name (<vendor>/<name>) [firerabbit/packagist]:
```

提示让你输入包的名字，格式为 `<vendor>/<name>`，`vendor` 为服务商名字，个人开发可以使用自己在 GitHub 中使用的昵称，`name` 为包的名字，`<vendor>/<name>` 不能存在同名，这里设置的名字即后面使用 `composer require` 的名字。

中括号内的 `[firerabbit/packagist]` 是默认值，直接按回车的话就会使用这个名字。

### 输入包的描述信息
包名输入完成后，接下来需要设置 `Description` （描述）字段，这个字段是包的功能性描述，作为练习包就随便输入啦：

![image.png](https://i.loli.net/2019/08/24/y6EWBMhGbZ1V3Qt.png)

### 输入作者信息

接下来设置 `Author` (作者信息)，格式为 `name example@email.com`，输入 n 可以跳过：

![image.png](https://i.loli.net/2019/08/24/V7UsaPQpm5LyKqu.png)

### minimum-stability 最小稳定版本
通过设置 `minimum-stability` 的值，来告诉 `Composer` 当前开发的项目的依赖要求的包的全局稳定性级别，它的值包括：dev、alpha、beta、RC、stable，stable 是默认值。

![image.png](https://i.loli.net/2019/08/24/IFecMfkGXYgWU9T.png)

稳定性介绍：[理解 Composer 的稳定性（Stability）标识
](https://learnku.com/php/t/9929/understanding-composers-stability-stability-identity)

我们直接按回车默认值即可。

### Package Type 包类型

接下来设置包的类型：

![image.png](https://i.loli.net/2019/08/24/4PMEiWuaXIb2Zyd.png)

composer 原生支持以下4种类型：

- library: 默认类型，它会简单的将文件复制到 vendor 目录。
- project: 一个项目，而不是一个库。
- metapackage: 空的包，包含依赖并且需要触发依赖的安装。
- composer-plugin: 一个安装类型为 composer-plugin 的包，它有一个自定义安装类型，可以为其它包提供一个 installler。

这里我们直接按回车，采用默认的 library。

### License 许可协议
接下来输入包的许可协议：

![image.png](https://i.loli.net/2019/08/24/UW2StviLwyIO9ef.png)

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

这里我们输入 MIT （开源许可协议）。

### 定义依赖项
接下来设置依赖项：

![image.png](https://i.loli.net/2019/08/24/6iHFcMvR8UK7o2O.png)

```
Define your dependencies.

Would you like to define your dependencies (require) interactively [yes]?
```

我们的练习项目不需要设置此项，输入 no。

### dev 依赖项
设置 dev 环境依赖项：

![image.png](https://i.loli.net/2019/08/24/LEelWoaq4dwNts2.png)

```
Would you like to define your dev dependencies (require-dev) interactively [yes]?
```

同上，输入 no。

### 最后一步：确认信息
最后一步，确认包的信息：

![image.png](https://i.loli.net/2019/08/24/fDkiBObK9QXo1aJ.png)

输入 yes，回车，然后查看当前目录即可看到 `composer.json` 文件。

`composer init` 命令帮助你自动生成文件，实际上你可以直接在**包的目录下**创建 `composer.json`，并且输入以下内容：

```
{
    "name": "huotu/test",
    "description": "我的第一个包。",
    "license": "MIT",
    "authors": [
        {
            "name": "火兔兔子",
            "email": "huoshaotuzi@icloud.com"
        }
    ],
    "require": {}
}
```

本质上两种方式都是一样的。

## 依赖包的基本结构
一个 `composer` 依赖包的基本结构，以我们上面的 `packagist` 为例：

- packagist
   - src
      - 类文件
      - ...
   - tests
      - 单元测试文件
      - ... 
   - README.md
   - composer.json
   - LICENSE

### src 文件夹
`src` 是包所在的路径，一般我们都会将其命名为 `src`（业界共识），不建议改成其他的名字。

开发包的工作就是在这个目录下进行的，你可以在这个目录下创建更多的文件夹来划分不同功能的类。

### tests 文件夹
`tests` 文件夹用来存放单元测试的，如果你不写的话，这个文件夹可以不要。

### README.md 文件
`README.md` 是包描述的 Markdown 语法的介绍文档，在 GitHub 中将会自动解析这个文件并且展示出来，每一个包都**应该**要包含 `README.md` 文件，用来介绍这个包的基本信息和操作方法。

创建 `README.md` 文件的方法：

```
# Mac OS
vim README.md

# Windows 系统
创建 README.txt，保存后改成 .md
如需编辑，右键以文本文档打开即可
```

### composer.json 文件
包的配置信息。

### LICENSE
许可协议文本，文本格式。

练习项目中，我们只需要 `src` 和 `README.md` 即可。

推荐使用 `PHPstorm` 作为编辑工具，最后我们的包目录结构如图所示：

![image.png](https://i.loli.net/2019/08/24/he9UfyOz2W6Z5Qi.png)

## 配置自动加载规则
为了防止命名空间冲突，开发的包需要配置自动加载，修改 `composer.json` 添加 `autoload` 字段：

```
"autoload": {
        "psr-4": {
            "Huotu\\Test\\": "src/"
        }
    }
```

我们采用 `psr-4` 的规范来自动加载包目录下 `src` 文件夹内的类文件，这里的 `"Huotu\\Test\\"` 是我们使用的命名空间，`\\` 不能写成 `\`，一般而言，命名空间以包的名字来命名。

完整的 `composer.json` 配置如下：

```
{
    "name": "huotu/test",
    "description": "我的第一个包。",
    "license": "MIT",
    "authors": [
        {
            "name": "火兔兔子",
            "email": "huoshaotuzi@icloud.com"
        }
    ],
    "require": {},
    "autoload": {
        "psr-4": {
            "Huotu\\Test\\": "src/"
        }
    }
}

```

### 创建包的类文件
在 `src` 目录下创建 `Robot.php` 文件，我们希望写一个可以自动打招呼的机器人（类）：

```
<?php

namespace Huotu\Test;

class Robot
{
    public function sayHello($name) {
        echo 'hello,' . $name . PHP_EOL;
    }
}
```

这样我们就完成了一个依赖包的开发，使用者只需要引入这个包就可以调用 `Robot` 的 `sayHello` 方法。

## 测试包的功能
我们现在已经写好了一个包，但是开发过程以及准备发布的时候，我们都需要对功能进行调试，你可以在目录下创建一个 `test.php`，然后运行 `php test.php` 来测试，不过这样总是不太方便的，尤其是某些有其他依赖的操作（如需要连接数据库、Redis）等等。最好的方法是将包文件放在一个真实的项目里进行测试，下面模拟创造一个 `project` 来作为我们实际的项目。

在 `packagist` 同级目录下，创建一个文件夹 `project`。

由于我们本地开发的包并未上传的 Packagist，无法通过 `composer require` 进行安装，因此我们必须手动配置加载目录，进入 project 文件夹，创建 `composer.json`：

- project
   - composer.json

编辑 `composer.json`，输入如下内容：

```
{
  "autoload": {
    "psr-4": {
      "Huotu\\Test\\": "../packagist/src/"
    }
  }
}
```

创建完成后，我们需要执行 `composer dump-autoload` 来生成自动加载文件。

> 如果对依赖包添加了新的类或者删除了类，涉及到类文件数量、名称改变的，都需要重新执行 composer dump-autoload，否则无法读取到最新的类文件

执行完成后，在当前目录下生成了 `vendor` 文件夹，这个文件夹里即包含了我们依赖包的自动加载信息。

![image.png](https://i.loli.net/2019/08/24/nCVmNxviFpfJUw5.png)

接着一个文件用来测试结果 `test.php`：

- project
   - composer.json
   - test.php
   - vendor
      - composer
         - ...
      - autoload.php 

输入如下内容：

```
<?php

require './vendor/autoload.php';

use Huotu\Test\Robot;

$robot = new Robot();
$robot->sayHello('IDCE.COM');
```

终端中输入 `php test.php` 执行结果：

![image.png](https://i.loli.net/2019/08/24/BYavLTK2W9JDncZ.png)

可以看到我们成功调用自己开发的包了！

必须将 `autoload` 引入才能实现自动加载，如果提示找不到类可能就是没有正确引入的关系或者 `composer.json` 配置的 `psr-4` 路径不正确。

## 上传到 GitHub
开发完成后，我们需要把包文件上传到 [GitHub](https://github.com)，如果没有账号则注册一个。

进入个人主页，在左侧的 Repositories（仓库）选择 New 创建一个新的仓库：

![image.png](https://i.loli.net/2019/08/24/ISdkXipUAeLfv2c.png)

仓库信息，权限要选择 `public`（公开的），完成后点击 `Create repository`：

![image.png](https://i.loli.net/2019/08/24/FuyLgqBpdAH3W8R.png)

创建好的项目：

![image.png](https://i.loli.net/2019/08/24/WZdpEtnvL3qCeVX.png)

GitHub 十分友好的提示了上传文件的步骤，我们只需要执行以下几个步骤即可：

```
git init
git add .
git commit -m "first commit"
git remote add origin https://github.com/huoshaotuzi/test.git
git push -u origin master
```

返回 `packagist` 目录下，我们按照 GitHub 上提示的内容，执行 `git init`，并添加文件：

![image.png](https://i.loli.net/2019/08/24/B1vg4hK95lz2VkC.png)

> 注！由于 ide 产生的文件是必须添加 .gitigonre 排除的

然后添加上传的仓库信息，并执行 `push` 推送到 GitHub 的仓库：

![image.png](https://i.loli.net/2019/08/24/FK5Gz1rpVluQLgn.png)

返回 GitHub 仓库，刷新页面即可看到上传文件的信息：

![image.png](https://i.loli.net/2019/08/24/IbWEe8VnKwmZhCP.png)

## 上传到 Packagist
如果希望自己的包被其他人安装，就需要将包上传到 Packagist 官网上。

Packagist 官网：[https://packagist.org/](https://packagist.org/)

![image.png](https://i.loli.net/2019/08/24/sX6AxhSyfEC9pVg.png)

如果没有账号可以注册一个，或者直接使用 GitHub 登录（推荐）。

登录后，选择右上角的 `Submit`（提交）：

![image.png](https://i.loli.net/2019/08/24/8zhUPTDwsbOt5fa.png)

在提交页面会提示你输入 GitHub 上仓库的地址：

![image.png](https://i.loli.net/2019/08/24/GQSMnub7JdOxWjH.png)

输入刚才创建的仓库地址，点击 `Check`：

![image.png](https://i.loli.net/2019/08/24/qeuQ6oEsWdLl2cF.png)

这边会提示一些同名的包，并且出现了 `Submit` 按钮，我们直接点击 `Submit`，此时会进入包页面，`update` 会进入转圈圈状态，表示正在同步包信息，稍等一会刷新页面即可看到包信息。

到目前为止，已经将包上传到 Packagist 官网了，但是我们还没有设置版本信息，需要返回到包目录下，给这个包打上标签。

输入以下命令：

```
git tag -a v1.0 -m "初始版本"
git push origin v1.0
```

然后返回 Packagist 官网，点击 `update` 同步包信息，然后刷新页面就能看到刚刚提交的版本信息了。

现在，用户可以使用 `composer require` 命令下载你的包了。

```
composer require huotu/test:1.0
```

## 自动同步版本更新
每次更新包都需要手动点击 `update` 十分不便，实际上 GitHub 提供了钩子可以用来推送更新信息到 Packagist，默认情况下已经帮助我们打开了自动更新功能。

回到 GitHub 的仓库地址，选择 `Setting`，左侧菜单 `Webhook`，可以看到配置的推送信息：

![webhook](https://user-images.githubusercontent.com/28209810/63644830-3432b800-c724-11e9-98c1-8ee55b76825c.png)

每当我们推送新的版本标签到 GitHub 时，Packagist 就会接收到一个 GitHub 的 Hook（钩子）发出的 POST 请求，这样 Packagist 上的包就会与 GitHub 上的同步了。

如果你不需要自动更新功能，可以点击右侧的 `Delete` 将其删除，删除后每次更新包都要前往 Packagist 点击 `Update` 手动进行更新。

开启自动更新情况下，每次 `push` 标签后都会自动同步到 Packagist，刷新页面即可看到最新提交的版本：

![new version](https://user-images.githubusercontent.com/28209810/63644872-eff3e780-c724-11e9-9507-f722204a1422.png)

## 依赖包编写小建议
开发依赖包需要要丰富的开发经验，可以多参考其他开源包，尤其是具有团队进行维护的，观察他们是如何区分目录和封装类的，可以学到很多知识。

推荐阅读：[PHP 设计模式](https://learnku.com/docs/php-design-patterns/2018)

编写依赖包能得到锻炼和成长的机会，开源自己的包也是一件十分具有成就感的事。

> 分享，是最好的学习方式 —— IDCE.COM