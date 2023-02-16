---
title: Window环境下安装Docker的坑
date: 2020-03-31 01:03:50
tags:
  - Docker
  
categories: Docker

description: 相比于 Linux 系统，要在 Window 系统安装 Docker 相对要麻烦得多。

---

## 前言
最近下载了 Docker for Window 搭建 Win10 系统的 PHP 环境，结果遇到了一大堆问题，网络上搜索答案极少，而且也很难找到能解决问题的方法，通过不断尝试和推测，踩了很多坑，也找到了填坑的办法，在此记录下来。

Docker for Window 下载地址：[Docker 官方网站下载](https://www.docker.com/products/docker-desktop)

## Docker Hub 登录问题
需要注册一个 Docker Hub 来进行登录，登录的帐号只能是 Docker ID，也就是注册时的用户名，而不是邮箱，此处需要注意，在使用 `docker login` 命令时经常会犯错而无法成功登录。

## Hyper-V 缺失问题
Docker for Window 依赖 Hyper-V（微软的虚拟机系统），只有 Window 专业版才有这个功能，家庭版如果需要开启此功能就需要升级到专业版。

> 查看电脑系统的方法：右键我的电脑，选择属性即可看到 Window 版本信息。

家庭版也可以不升级，安装另一个 Docker 的产品——Docker Tool。

Docker Tool 不依赖 Hyper-V，而是 VirtualBox（也是一种虚拟机软件），如果安装了 Docker Tool，则会自动安装 VirtualBox。Docker Tool 的操作方法与 Docker for Window 不太一样，更复杂一些，虽然本人也尝试下载过，但是感觉十分不便，因此最后卸载了。（由于安装了 Docker Tool，这里又挖了一个新坑，后面进行介绍）

本人用的是 Win10 专业版系统，却发现没有 Hyper-V 这个选项，原来是因为下载了精简版的 ISO 作为装机镜像，一些装机系统那边下载的软件都是经过二次封装的，削减了一部分的功能，因此推荐用那些精简过的系统。

找了许多装机的 ISO 镜像，最后决定下载微软官方原版的 ISO 镜像（十分干净，不带第三方软件），然后重新安装了 Win10 专业版。

Window MSDN 镜像下载：[MSDN 我告诉你](https://msdn.itellyou.cn/)

选择左侧的系统，根据个人需求安装即可，不过据说对 Window 版本有一定的要求，太早的版本可能还是没有这个功能，建议至少 Win 8 以上(专业版)。

查看 Hyper-V 的方法是打开 **控制面板**，然后选择 **程序**，再选择 **启用或关闭 Windows 功能**。

重装后的系统已经可以看到 Hyper-V 这个选项了，如下图所示：

![image.png](https://i.loli.net/2019/10/20/uHBJOYP3Mtze6jc.png)

勾选后选择确定，重新启动电脑。

## 开启 Hyper-V 导致无法开机问题
在开启 Hyper-V 的过程中，又遇到新的问题，如果将 Hyper-V 勾选起来点击确定，系统会安装软件然后提示重新启动，此时重新启动会导致电脑无法开机，一直在开始界面，并且提示“系统正在自动修复”。

只有进入到安全模式，将 Hyper-V 取消掉才能正常开机；又或者多次重启失败，系统自动恢复最后一次正确的配置。网上查了很多资料都没有找到解决方法，于是推测是因为相关的虚拟机服务没有启动导致的。

解决方法是进入服务管理，将 Hyper-V 的相关服务调整为“自动启动”，点击开始，选择运行（或者直接按 Win+R 快捷键），然后输入 `services.msc` 进入服务管理。

![image.png](https://i.loli.net/2019/10/20/75OeL1pd4x6QtzS.png)

![image.png](https://i.loli.net/2019/10/20/hkmugev9KtBzlZ3.png)

然后打开 Hyper-V，再重启就可以了。

## Docker 命令被占用
在安装好了 Docker for Window 后，打开命令行使用 docker 命令，却提示如下的错误信息：

```
unable to resolve docker endpoint: open C:\Users\Administrator\.docker\machi....
```

大致意思是找不到某个文件，而 `docker-machine` 是 Docker Tool 用到的东西，在翻找了许多资料后才发现原来是卸载时残留的环境变量导致的问题，Docker Tool 虽然卸载了，但是环境变量还在，这就导致了使用 docker 命令用的环境变量路径还是 Docker Tool 设置的路径，由于软件被卸载了，路径自然就找不到了。

解决方法是删掉残留的环境变量。

右键我的电脑，高级系统设置，环境变量。

![image.png](https://i.loli.net/2019/10/20/VLGM5XjYpJhZEeW.png)

![image.png](https://i.loli.net/2019/10/20/1HFzeVMgtSoLRb7.png)

![image.png](https://i.loli.net/2019/10/20/QYVrGO3k2M8By1D.png)

然后在用户变量与系统变量中，找到 Docker 相关的变量全部删除，然后卸载掉 Docker for Window，重装一遍，即可解决（建议重启一次电脑）。

## 磁盘共享
Docker 恢复正常以后，本人使用的是自己封装的一套 docker-compose 系统，由于里面用到了容器卷，在 Window 系统中还存在磁盘共享问题。

在使用 `docker-compose up -d` 的时候遇到如下错误：

```
Cannot create container for service redis: b'Drive sharing failed for an unk...
```

大致意思是说磁盘共享失败。

解决方法是打开 Docker for Window 的 Settings，右键右下角的小鲸鱼图标进入设置界面，在设置界面中选择 Shared Drives 选项卡，将需要共享的磁盘勾选起来，然后点击 Apply（应用）保存设置，这个过程可能需要输入 Window 系统的用户名和密码，如果没有设置密码则需要设置一个。

![image.png](https://i.loli.net/2019/10/20/RCuri4gzwynGNeH.png)

## Docker-Compose 路径问题
接着又遇到新的问题，执行 `docker-compose up -d` 弹出如下错误：

```
ERROR: for workspace_redis_1  Cannot start service redis: OCI runtime create failed: container_linux.go:345: starting container process caused "process_linux.go:430: container init caused \"rootfs_linux.go:58: mounting \\\"/etc/localtime\\\" to rootfs \\\"/var/lib/docker/overlay2/c6e01c3620bbec9f7dc46bc22dbda8a9cdbf050746f17af60e665fb2191f5d27/merged\\\" at \\\"/var/lib/docker/overlay2/c6e01c3620bbec9f7dc46bc22dbda8a9cdbf050746f17af60e665fb2191f5d27/merged/usr/share/zonStarting workspace_mysql_1 ... error 
```

这是由于我在 `docker-compose.yml` 文件中写了一个错误的路径，`/etc/localtime` 是 Linux 系统里面的路径，将它去掉就可以了。

```
redis:
        build: redis/
        restart: always
        volumes:
          - ./redis/conf/redis.conf:/usr/local/etc/redis/redis.conf
          - ./var/logs/redis.log:/var/log/redis.log
          - /etc/localtime:/etc/localtime
        ports:
          - "6379:6379"
        networks:
          - default
```

去掉后就可以正常运行了。

## Vmware、Hyper-V 不兼容问题
第一次因为 Hyper-V 缺失而无法安装时，曾经尝试过使用 Vmware 来安装虚拟机，想要在虚拟机里面装一个 MacOS 系统，结果也是一个大坑，默认情况下的 Vmware 不支持 MacOS，而需要安装一个补丁，结果折腾了半天也没搞定，MacOS 的镜像高达 7 个 G，还因为百度网盘暂停会导致重新开始……折腾得心累，于是放弃了。

Hyper-V 与 Vmware 是不兼容的，一山不容二虎，如果要使用其中的一种，需要卸载掉另外一种。否则可能会因为服务的问题导致软件不能正常启动。

## 完结感言
在中途曾经放弃过安装 Docker 的念头，而是手动安装了 PHP、MySQL、Redis、Nginx 等环境，但是后面又发现许多 PHP 扩展在 Window 系统的安装都十分麻烦，比如 swoole 扩展还需要安装 cygwin，折腾的心累于是放弃了。

真是艰辛的过程~~~
