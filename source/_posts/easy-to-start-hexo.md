---
title: 猴子都能学会的hexo博客安装教程
date: 2020-03-01 10:49:10
tags:
 - Hexo
 - gitlab
 
categories: Hexo

description: 小白都能学会的从零开始照猫画虎hexo博客搭建教程。

---
## hexo 简介

hexo 是一款开源博客项目。

即使是小白借助 hexo 也能轻松搭建属于自己的博客。

根据下面的教程，你能学会搭建个人博客，并且其他人可以通过外网访问到你的博客！

hexo 官网：[https://hexo.io/](https://hexo.io/)

官方中文文档：[https://hexo.io/zh-cn/docs/index.html](https://hexo.io/zh-cn/docs/index.html)


【FAQ】

需要买服务器和域名吗？

通过 Github Pages 可以白嫖域名和服务器，所以不用买。

当然，如果你的钱包预算足够，买一款心仪的域名和自己的服务器那就更完美了！

话不多说，接下来直接进入教学篇。

## 准备工作

本教程主要基于 Linux 系统，如果是 Window 系统也不用急，只是增加一个配置环境变量的步骤，机智的你一定懂得怎么做！

代码编辑器：[VS Code](https://code.visualstudio.com/)

## 基础环境

hexo 依赖于 node，首先需要安装 node 环境。

### 1、安装 Nodejs

**Windows 系统**

nodejs 官网下载：[https://nodejs.org/zh-cn/download/](https://nodejs.org/zh-cn/download/)

如果确实是小白，建议下载 Windows 安装包 (.msi)，msi 安装包会自动配置环境变量，真正实现小白式操作。

**Linux 系统**

Linux 版本众多，在这里只介绍 Centos 的安装方法。

第一步：安装 node 相关依赖

```
yum install -y gcc gcc-c++ openssl-devel epel-release
```

第二步：安装 nodejs

```
yum install -y nodejs
```

安装完成后，在控制台输入命令：

```
node -v
```

如果可以看得到版本信息说明安装成功。

安装完 node 之后，即可使用 npm 命令，由于 npm 的源是国外网站，速度会很慢，为了节省时间我们可以更换淘宝提供的镜像。

```
npm install -g cnpm --registry=https://registry.npm.taobao.org
```

安装完成后就可以使用 `cnpm` 命令了，在后续的操作用 cnpm 来代替 npm。

### 2、安装 Git

Git 是代码托管工具，整个过程 Git 的戏份很少，不懂的小白也不用担心，安装好就对了！

- Windows：[https://git-scm.com/download/win](https://git-scm.com/download/win).
- Mac：使用 Homebrew, MacPorts 或者下载 [安装程序](http://sourceforge.net/projects/git-osx-installer/)。
- Linux (Ubuntu, Debian)：sudo apt-get install -y git-core
- Linux (Fedora, Red Hat, CentOS)：sudo yum install -y git-core

> Mac 用户请先到 App Store 安装 Xcode，Xcode 完成后，启动并进入 Preferences -> Download -> Command Line Tools -> Install 安装命令行工具。

### 3、安装 hexo

使用 cnpm 命令一键安装 hexo：

```
cnpm install -g hexo-cli
```

安装完成后，在控制台输入命令：

```
hexo
```

如果看得到如下信息说明安装成功：

```
Usage: hexo <command>

Commands:
  help     Get help on a command.
  init     Create a new Hexo folder.
  version  Display version information.

Global Options:
  --config  Specify config file instead of using _config.yml
  --cwd     Specify the CWD
  --debug   Display all verbose messages in the terminal
  --draft   Display draft posts
  --safe    Disable all plugins and scripts
  --silent  Hide output on console

For more help, you can use 'hexo help [command]' for the detailed information
or you can check the docs: http://hexo.io/docs/
```

## 搭建博客！

现在开始搭建博客项目，运行命令：

```
hexo init blog
```

这个 blog 是文件夹的名字，你可以随意取，在这里我把它取名 blog。

执行完这个命令会自动在当前目录下创建一个 blog 文件夹，使用命令 `cd blog` 进入这个文件夹，接着再执行 `cnpm install`。

安装完成后，文件夹的目录如下所示：

```
.
├── _config.yml
├── package.json
├── scaffolds
├── source
|   ├── _drafts
|   └── _posts
└── themes
```

需要注意两个主要的配置文件：

### 1、_config.yml

网站的配置信息，比如网站的名字什么的，都在这里进行配置。

### 2、package.json

网站依赖的包（小白不用管这个东西），默认已经帮你配置好常用的包了。

以上，你的博客已经搭建完啦！

## 访问博客！

什么鬼！？这就搭建完了？？

yes，我们打开控制台，在博客目录下执行命令启动博客：

```
hexo s
```

如图所示：

![启动hexo博客](https://user-images.githubusercontent.com/28209810/75619193-9a0c6a80-5bb3-11ea-88af-f8ea2b22b051.png)


然后打开浏览器，输入 `http://localhost:4000` 或 `127.0.0.1:4000`。

你就可以看到搭建好的博客啦！

如下图所示：

![hexo博客](https://user-images.githubusercontent.com/28209810/75619228-6716a680-5bb4-11ea-99c2-d121aba9338f.png)

## 写下第一篇博文！

好吧，其实你的第一篇博客已经被系统写好了，也就是你在上图看到的标题为 Hello World 的博文。

接下来我们要手动创建第一篇博客！

使用命令 `hexo n <博文标题>` 来创建一篇新的博文。

控制台下输入：

```
hexo n "我的第一篇博客"
```

执行完命令后，可以看到生成了一个 .md 格式的文件 `/blog/source/_posts/我的第一篇博客.md`，如下图所示：

![image](https://user-images.githubusercontent.com/28209810/75619292-13588d00-5bb5-11ea-9ff2-4833965a8456.png)

这个文件就是我们的博文内容，进入 `/blog/source/_posts/` 然后打开 `我的第一篇博客.md`， 可以看到默认内容类似：

```

---
title: 我的第一篇博客
date: 2020-03-01 10:49:10
tags:
---

```

开头的部分不要动，在结尾部分写自己想写的文章即可，比如：

```

---
title: 我的第一篇博客
date: 2020-03-01 10:49:10
tags:
---

# 兔子的日记

今天的天气真好！
```

如果你用的是 Markdown 还能看到预览效果！

Markdown 编辑器推荐：[有道云笔记](http://note.youdao.com/semdl/markdown.html)

（你可以在其他地方编辑好文章，然后复制过来）

我用的是 PHPstorm，预览效果如下：

![hexo第一篇博文](https://user-images.githubusercontent.com/28209810/75619349-c5905480-5bb5-11ea-89d2-d2abf88cdef4.png)

文章内容已经写好了，接下来要生成静态页面，按 `Ctrl+C` 把刚才启动的博客关掉：

![关闭hexo服务](https://user-images.githubusercontent.com/28209810/75619390-2ae44580-5bb6-11ea-8835-12430e945e21.png)

然后依次运行如下命令：

```
hexo clean
```

清空数据库，如图所示：

![hexo clean](https://user-images.githubusercontent.com/28209810/75619432-8d3d4600-5bb6-11ea-9985-a56c65a7cf48.png)

接着输入 `hexo g` 重新编译生成静态页面：

```
hexo g
```

运行结果如下：

![hexo 生成博文](https://user-images.githubusercontent.com/28209810/75619447-c4135c00-5bb6-11ea-827b-28be7914d84c.png)

最后，再启动 hexo：

```
hexo s
```

浏览器输入：`127.0.0.1:4000` 就可以看到自己刚才写的那篇文章了。

如图所示：

![hexo第一篇博文](https://user-images.githubusercontent.com/28209810/75619492-5451a100-5bb7-11ea-9af3-c25af149898f.png)

至此，hexo 的基本操作已经 OK 了。

再来总结一下怎么发一篇博文。

1、执行 `hexo n <标题>` 创建博文文件

2、在 Markdown 编辑器完成博文书写，把内容复制到上面生成的博文文件里

3、执行 `hexo clean` 清空数据

4、执行 `hexo g` 重新编译生成静态文件

此外，本地调试用 `hexo s` 开启本地服务，在浏览器输入 `127.0.0.1:4000` 访问博客项目。

要记住这些步骤和命令对小白来说十分吃力，这是很正常的事情。

如果忘记了怎么操作，回头多看几遍本博文，熟能生巧！

## 自定义主题！

默认博客页面太丑，想换一个怎么办？

网上找到其他人分享的主题：[https://github.com/zhvala/hexo-material-x-black](https://github.com/zhvala/hexo-material-x-black)

然后把它下载下来，点击右侧 Clone or download：

下载下来并且解压，把解压后的文件复制到博客项目的 themes 目录下。

或者直接 clone 到 themes 目录下并且命名为 material-x：

```
git clone https://github.com/xaoxuu/hexo-theme-material-x themes/material-x
```

主题文件都很大，等下载完成就可以了。

主题下载下来以后，目录结构如下：

![3g86hV.png](https://s2.ax1x.com/2020/03/01/3g86hV.png)

themes 下面的文件夹都是主题。

接着编辑 .config.yml，拉到底部，修改 theme 字段：

```
# Extensions
## Plugins: https://hexo.io/plugins/
## Themes: https://hexo.io/themes/
theme: material-x
```

theme 默认是 landscape，改成刚刚下载的主题 material-x（即文件夹的名字）。

然后安装主题所需的依赖：

```
npm i -S hexo-generator-search hexo-generator-feed hexo-renderer-less hexo-autoprefixer hexo-generator-json-content hexo-recommended-posts
```

接着执行下面几个命令（如果之前启动了博客项目，记得按 Ctrl+C 先关掉）：

```
hexo clean
hexo g
hexo s
```

访问 `http://localhost:4000`，可以发现主题已经被更换成新的了。

在 Github 上还有更多主题可以选择：[点击此处获取更多主题](https://github.com/search?q=hexo+theme&type=Repositories)

## 发布博客！

上面的步骤只能在自己的电脑打开博客项目，现在我们要把博客发到外网去，让别人来参观你的博客！

--- 小剧场 ---

你是想要节操，还是想白嫖域名和服务器？

我是想要节操还把域名和服务器嫖了！

嫖不成。

啪一声，你把 300 块大洋拍在桌子上。

这个能不能换来节操？

能，但是钱包空了。

啪一声，你把节操拍在桌子上，300 块大洋收回兜里。

嫖谁的？

GitHub 的！

就那个全球最大同性交流社区？

正是。

敢问君为何方神圣？

GitHub 基佬是也。

--- 小剧场（完） ---

没错，我们要白嫖 GitHub 提供的免费域名和空间——GitHub Pages！

GitHub Pages 提供了免费的服务可以让我们部署博客项目。

Github：[https://github.com/](https://github.com/)

注册一个账户并登陆。

接着点击左侧 Repositories 旁边的按钮 New 创建一个新的仓库，如图：

![image](https://user-images.githubusercontent.com/28209810/75619699-608b2d80-5bba-11ea-8bff-2570abd32650.png)

仓库的配置有两点需要注意，如果你没按照下面的要求，你的博客就访问不了：

1、仓库名称必须为：你的 GitHub 用户名.github.io，比如我的 Github 用户名是 huoshaotuzi，那么仓库的名字就是：huoshaotuzi.github.io

2、仓库必须为 Public 公开权限，如果选择 Private，就不能白嫖了，这也是上面的小剧场所说的出卖节操的原因，一旦公开权限，你的仓库 **任何人都能访问**，你的仓库设置成 Public，相当于你光着屁股暴露在 Github 几百万基佬面前，知道啥意思了吧？

创建好仓库后，可以看到如下的仓库信息：

![image](https://user-images.githubusercontent.com/28209810/75619801-8107b780-5bbb-11ea-9b9b-77f1eb0b4078.png)
（xxoo 是随便取的名字，不要问 xxoo 是什么意思，问了也不会告诉你。）

在这里把 SSH 后面的仓库地址复制下来，后面会用到。

接着返回到你博客的目录下，执行命令安装 git 插件：

```
cnpm install --save hexo-deployer-git
```

安装完成后，编辑根目录下的 _config.yml，拉到最底部，deploy 后面添加如下内容：

```
# Deployment
## Docs: https://hexo.io/docs/deployment.html
deploy:
  type: git
  repo: https://github.com/huoshaotuzi/xxoo.git（填你自己的）
  branch: master
```

这里的 repo 填入上面说的仓库地址：

![image](https://user-images.githubusercontent.com/28209810/75619912-0fc90400-5bbd-11ea-9636-1d7bbc7310ce.png)

保存配置文件后，输入命令：

```
hexo d
```

执行命令后开始自动编译并且上传到 GitHub 仓库，然后会提示要输入 Github 的账号跟密码。

为了避免每次上传都要重复输入账号密码，你可以输入下面的命令记住密码：

```
git config --global credential.helper store
```

上传成功后，返回仓库就能看到上传好的代码。

然后就可以通过仓库名称访问到你的博客了！

我的仓库名称是：huoshaotuzi.github.io

在浏览器输入：huoshaotuzi.github.io

刚刚部署上去的时候，需要等几分钟才能看到博客，不然会出现 404 或者其他问题。

白嫖党只能使用 GitHub 提供的域名，接下来给大家介绍如何指定域名进行访问。

比如我的网站是：[blog.huotuyouxi.com](https://blog.huotuyouxi.com)

这是怎么实现的呢?

## 指定域名！

首先，你得有一个域名。

万网阿里云、百度云、腾讯云、京东云、华为云、国外的 GoDaddy……诸如此类域名服务提供商，任选一家即可。

建议购买 .com 后缀的域名，域名的名称就按照你自己喜欢的挑选了。

比如张三：zhangsan.com

皮卡丘博客：pikachublog.com

简单好记的域名，这个没什么要求的，选你喜欢的就好。

域名买好之后，在仓库主页的菜单栏，选择 Settings：

![image](https://user-images.githubusercontent.com/28209810/75619981-0be9b180-5bbe-11ea-8b71-1a5cc2f906df.png)

拉到下面，直到看到 GitHub Pages，然后点击 Choose a theme 选择一个主题：

![image](https://ae01.alicdn.com/kf/Hb6adf75de5bb405396b8991dcab4ccf2d.png)

主题按照你喜欢的随便选一个就好：

![image](https://ae01.alicdn.com/kf/Hdad8baba745a4a5698426e9b386d9606a.png)

比如选第一个，然后点击右下角 Select theme。

接着返回 Github Pages 配置域名：

![image](https://ae01.alicdn.com/kf/Ha7c7c267f4594459854b89d8782cf9366.png)

在 Custom domain 栏处填写你购买的域名，然后点击 Save。

如果需要启用 https 域名，把 Enforce HTTPS 勾选即可，刚部署时需要等待几分钟 Enforce HTTPS 才会显示可选，并且勾选完成后也需要等待几分钟，Github 会为你免费提供 SSL 证书。

然后打开你购买域名的网站，例如阿里云，登录后打开右上角控制台-点击左上角展开菜单-选择域名：

![image](https://ae01.alicdn.com/kf/H59ae77f519c645c486feacd4c8e68ff8d.png)

在域名右侧选择解析：

![域名解析](https://ae01.alicdn.com/kf/H0f6fb6011e264e39a694159c9deac969C.png)

点击“添加记录”，记录类型 CNAME，主机记录 @，记录值填你自己仓库的名字：

![](https://ae01.alicdn.com/kf/Hfa26d74adaf24408aba88ef049091802E.png)

如果你希望可以用 www 访问，那就再添加一条记录，主机记录填 www，其他跟上面的一样：

![](https://ae01.alicdn.com/kf/H638380f92dbf4ff0bd97f57117e225fcv.png)

完成这一步你就可以通过购买的域名访问到博客了！

## 指定服务器！

不想出卖节操，就买台服务器自己安装环境，这样可以保证代码不被其他基佬看到。

### 1、域名和服务器的选择

选服务器可是很有讲究的，看你网站的受众，如果是面向海外用户，就选国外的服务器；如果面向国内用户，那就买国内的服务器。服务器放在哪很大程度上会影响你网站的打开速度。

再者，如果你选国内服务器，还需要注意域名备案的问题。

想要搭建博客，最好提前买个域名进行备案。

没有备案的域名只能选择国外主机。

只建议购买：com、cn、net 这三个老牌域名。

非主流域名即使能备案，搜索引擎也很难收录，所以不推荐。

有条件或者嫌备案麻烦的，可以购买香港或者台湾地区的服务器，靠近大陆，延迟相对低一点，域名即买即用。

### 2、服务器环境

服务器推荐 Centos 系统，如果是小白也可以选 Windows Server。

要让网站解析到服务器，首先需要安装 Web 服务器，老牌的 Web 服务器软件有 Apache，Windows 的 IIS。

这两个本人几乎没用过，就不在这里介绍了。

推荐使用 Nginx。

以 Centos 为例，安装十分简单：

```
yum install -y nginx
```

安装完成后，执行命令：

```
vim /etc/nginx/conf.d/blog.conf
```

按下 i 键插入如下内容：

```
server {
    server_name blog.huotuyouxi.com;
    root /var/www/blog;
    index index.html;
}
```

然后按 Esc，输入 `wq!` 保存。

接着重启 Nginx：

```
nginx -s reload
```

在这里，`server_name` 是你的域名。

`root` 是你的博客项目路径，可以下载 FileZilla 把博客上传到服务器，或者直接使用 `git clone` 下来。


```
cd /var/www
git clone <你的博客仓库地址>
```

然后你在哪买的域名，就登录到控制台把域名解析到服务器的公网 IP。

解析的方法在上面已经有介绍了，这里不再重复。

3、更新博客内容

前面的文章介绍了如何编写和更新博客：

```
hexo clean
hexo g
hexo d
```

更新博客内容并且推送到 Github 上面，你就可以回到服务器上，然后执行 `git pull` 把最新的博客内容拉取下来，实现博客的更新。

其中，`hexo g` 和 `hexo d` 两个命令可以简写成：

```
hexo d -g
```

> 有兴趣的可以去了解一下 Git 自动构建，延伸的内容太多了，本文写不下

### 4、博客代码备份

使用 `hexo d` 上传到 Github 的只是编译后的静态文件，博客的代码并没有上传到仓库里。

这样一旦你的博客代码丢失，你就无法继续更新了。

为了避免这种情况，我们要把博客代码也上传到仓库里，最好是上传到同一个仓库。

回到本地的博客项目，执行如下命令：

```
git checkout -b develop
git push origin develop
```

这两个步骤，第一个是切换到新的分支 `develop`（名字可以随便取），然后把新的分支推送到远程仓库。

如此一来，你的博客代码也被保存至仓库里。

前往 Github 查看，点击 Branch 即可看到新的分支：

![git 分支](https://s2.ax1x.com/2020/03/01/3g2BzF.png)

还记得最早我们在 _config.yml 设置的 Github 仓库地址吗？

![hexo github 配置](https://s2.ax1x.com/2020/03/01/3g2gd1.png)

这里我们把静态文件的分支设置成 master，因此我们可以专注于在 develop 分支上写博文，然后用 `hexo d -g` 进行推送，不需要进行分支切换，十分方便！

把博客代码上传到远程仓库，即使你在另一台电脑上面也可以把项目克隆下来，然后执行如下命令：

```
git checkout develop
```

切换到 develop 分支上去写博文。

## 结语

借助 hexo 可以快速搭建属于自己的博客，平时学习到新东西的时候就可以记录下来，养成良好的习惯有助于学习成果的积累。