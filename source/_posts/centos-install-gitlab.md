---
title: Centos安装Gitlab
date: 2020-03-07 22:21:32

tags:
 - centos
 - gitlab
 
categories: docker
 
description: 搭建自己的 Git 仓库。 
 
---

## 1、安准基础依赖
安装 Gitlab 所需依赖：

```
sudo yum install -y curl policycoreutils-python openssh-server
```

启动 ssh 服务并设置开机启动：

```
sudo systemctl start sshd
sudo systemctl enable sshd
```

## 2、安装邮件服务
Postfix 是一个邮件服务器，GitLab 发送邮件需要用到。

安装 postfix：

```
sudo yum install -y postfix
```

启动 postfix 并设置为开机启动：

```
sudo systemctl start postfix
sudo systemctl enable postfix
```

## 3、开放 ssh 以及 http 服务（80 端口）
查看防火墙是否启动：

```
systemctl status firewalld
```

以下为我的服务器上的防火墙状态：

```
[root@ecs-86c0 ~]# systemctl status firewalld
● firewalld.service - firewalld - dynamic firewall daemon
   Loaded: loaded (/usr/lib/systemd/system/firewalld.service; disabled; vendor preset: enabled)
   Active: inactive (dead)
     Docs: man:firewalld(1)
```

如果看到 Active: inactive (dead)，表示防火墙没有启动，执行下面的命令启动防火墙并且设置为开机启动：

```
systemctl start firewalld
systemctl enable firewalld
```

此时，在查看防火墙状态：

```
[root@ecs-86c0 ~]# systemctl status firewalld
● firewalld.service - firewalld - dynamic firewall daemon
   Loaded: loaded (/usr/lib/systemd/system/firewalld.service; disabled; vendor preset: enabled)
   Active: active (running) since 一 2019-09-02 17:56:23 CST; 2s ago
     Docs: man:firewalld(1)
 Main PID: 26477 (firewalld)
   CGroup: /system.slice/firewalld.service
           └─26477 /usr/bin/python -Es /usr/sbin/firewalld --nofork --nopid
```

`Active: active (running)` 表示防火墙正常运行。

开放 ssh、http 服务：

```
sudo firewall-cmd --add-service=ssh --permanent
sudo firewall-cmd --add-service=http --permanent
```

> 只有防火墙开启状态才能执行上述命令，否则会报 FirewallD is not running 错误

重新加载防火墙：

```
sudo firewall-cmd --reload
```

## 4、安装 Gitlab
我们使用 Gitlab 的社区版：gitlab-ce，如果需要使用商业版，则安装：gitlab-ee。

### 4.1、添加 Gitlab 社区版资源包
默认情况下 yum 源没有 gitlab-ce 软件包，需要手动下载：

```
curl https://packages.gitlab.com/install/repositories/gitlab/gitlab-ce/script.rpm.sh | sudo bash
```

### 4.2、使用 yum 安装 gitlab-ce
在这里强烈推荐阅读：[Centos 系统更新 yum 源为国内镜像源](https://idce.com/document/VO4j)

```
yum install -y gitlab-ce
```

软件包的安装大小足足有 1.4 G，更换镜像后只需要数十秒就下载完了。

![image.png](https://i.loli.net/2019/09/02/eAobTa7M8LyECUn.png)

安装成功后可以看到一个类似“狐狸头像”的图案：

![image.png](https://i.loli.net/2019/09/02/PcAFUhG7LDm4TfJ.png)

### 4.3、配置 Gitlab 访问地址
Gitlab 安装完成后，配置文件所在路径为 `/etc/gitlab/gitlab.rb`，编辑配置文件：

```
vim /etc/gitlab/gitlab.rb
```

将 `external_url` 字段修改为你的域名信息，如果没有域名可以改成 `IP:端口` 的方式。

```
## GitLab configuration settings
##! This file is generated during initial installation and **is not** modified
##! during upgrades.
##! Check out the latest version of this file to know about the different
##! settings that can be configured by this file, which may be found at:
##! https://gitlab.com/gitlab-org/omnibus-gitlab/raw/master/files/gitlab-config-template/gitlab.rb.template


## GitLab URL
##! URL on which GitLab will be reachable.
##! For more details on configuring external_url see:
##! https://docs.gitlab.com/omnibus/settings/configuration.html#configuring-the-external-url-for-gitlab
external_url 'http://gitlab.example.com'
```

### 4.4、启动 Gitlab
重新载入配置并启动 Gitlab（如果修改了配置文件需要再运行此命令）。
```
sudo gitlab-ctl reconfigure
```

翻车现场：

![image.png](https://i.loli.net/2019/09/02/6kpd51B8TZqHyGD.png)

报错原文：

```
Running handlers:
There was an error running gitlab-ctl reconfigure:

Multiple failures occurred:
* Chef::Exceptions::MultipleFailures occurred in chef run: Multiple failures occurred:
* Errno::ENOMEM occurred in delayed notification: ruby_block[restart_log_service] (/opt/gitlab/embedded/cookbooks/cache/cookbooks/runit/libraries/provider_runit_service.rb line 69) had an error: Errno::ENOMEM: ruby_block[wait for logrotate service socket] (/opt/gitlab/embedded/cookbooks/cache/cookbooks/runit/libraries/provider_runit_service.rb line 266) had an error: Errno::ENOMEM: Cannot allocate memory - fork(2)
* Errno::ENOMEM occurred in delayed notification: ruby_block[reload_log_service] (/opt/gitlab/embedded/cookbooks/cache/cookbooks/runit/libraries/provider_runit_service.rb line 77) had an error: Errno::ENOMEM: ruby_block[wait for logrotate service socket] (/opt/gitlab/embedded/cookbooks/cache/cookbooks/runit/libraries/provider_runit_service.rb line 266) had an error: Errno::ENOMEM: Cannot allocate memory - fork(2)

* Errno::ENOMEM occurred in delayed notification: execute[clear the gitlab-rails cache] (gitlab::gitlab-rails line 408) had an error: Errno::ENOMEM: Cannot allocate memory - fork(2)
* Errno::ENOMEM occurred in delayed notification: service[gitaly] (dynamically defined) had an error: Errno::ENOMEM: Cannot allocate memory - fork(2)
* Errno::ENOMEM occurred in delayed notification: runit_service[gitaly] (gitaly::enable line 75) had an error: Errno::ENOMEM: Cannot allocate memory - fork(2)
* Errno::ENOMEM occurred in delayed notification: service[gitlab-workhorse] (dynamically defined) had an error: Errno::ENOMEM: Cannot allocate memory - fork(2)
```

%>_<%

原来是我的测试机内存（1G）不够！

> 穷人没有资格安装 Gitlab （╯‵□′）╯︵┴─┴ 

### 4.5、访问 Gitlab
如果不出意外，输入 `external_url` 配置的地址，即可看到 Gitlab 页面。

第一次登陆 Gitlab 时需要设置 root 密码，然后就可以愉快的创建项目了。