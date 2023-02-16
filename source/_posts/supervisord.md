---
title: 进程管理工具 Supervisord
date: 2020-03-10 00:56:30
tags:
 - Supervisord
 - 技术
 
categories: Linux

description: Supervisord 是一个进程管理工具，可以管理队列任务进程或者其他需要处理的进程任务。

---

## Supervisord 是什么？

Supervisord 是一个进程管理工具，它可以用来执行一些需要在后台持续存在的进程（守护进程）的启动命令。

比如前端的 Nuxt 框架使用 SSR（服务端渲染），需要启动服务端的进程，会使用 pm2 来管理进程的启动。

Supervisord 同样是一种进程管理工具。

下文将演示 Supervisord 管理 Laravel 的队列任务进程。 

## 安装 Supervisord

以 Centos 为例，直接使用 yum 安装即可。

```
# 1、安装 epel-release
yum install -y epel-release

# 2、安装 supervisor
yum install -y supervisor

# 3、将 supervisor 设置为开机启动
systemctl enable supervisord
```

以上就安装完成了，但是还没有启动 supervisor，先不用着急启动。

## Supervisor 配置文件

supervisor 的配置文件默认路径为：`/etc/supervisor/supervisord.conf`，使用 `vim` 命令编辑，大致可以看到如下内容：

```
; supervisor config file

[unix_http_server]
file=/var/run/supervisor.sock   ; (the path to the socket file)
chmod=0700                       ; sockef file mode (default 0700)

[supervisord]
logfile=/var/log/supervisor/supervisord.log ; (main log file;default $CWD/supervisord.log)
pidfile=/var/run/supervisord.pid ; (supervisord pidfile;default supervisord.pid)
childlogdir=/var/log/supervisor            ; ('AUTO' child log dir, default $TEMP)

; the below section must remain in the config file for RPC
; (supervisorctl/web interface) to work, additional interfaces may be
; added by defining them in separate rpcinterface: sections
[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock ; use a unix:// URL  for a unix socket

; The [include] section can just contain the "files" setting.  This
; setting can list multiple files (separated by whitespace or
"/etc/supervisor/supervisord.conf" 28L, 1178C                 8,1           Top
; supervisor config file

[unix_http_server]
file=/var/run/supervisor.sock   ; (the path to the socket file)
chmod=0700                       ; sockef file mode (default 0700)

[supervisord]
logfile=/var/log/supervisor/supervisord.log ; (main log file;default $CWD/supervisord.log)
pidfile=/var/run/supervisord.pid ; (supervisord pidfile;default supervisord.pid)
childlogdir=/var/log/supervisor            ; ('AUTO' child log dir, default $TEMP)

; the below section must remain in the config file for RPC
; (supervisorctl/web interface) to work, additional interfaces may be
; added by defining them in separate rpcinterface: sections
[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock ; use a unix:// URL  for a unix socket

; The [include] section can just contain the "files" setting.  This
; setting can list multiple files (separated by whitespace or
; newlines).  It can also contain wildcards.  The filenames are
; interpreted as relative to this file.  Included files *cannot*
; include files themselves.

[include]
files = /etc/supervisor/conf.d/*.conf
                                                                                                                                                                                          19,36         All
```

如果不一样说明版本不同，通过下面的命令可以查看自己安装的版本：

```
# 查看 supervisor 版本
supervisord -v

# 我下载的版本是 3.3.5
```

需要注意的地方只有最底下的一行：

```
[include]
files = /etc/supervisor/conf.d/*.conf
```

在一些旧的版本这里会有差别，这里的意思是说包含了路径 `/etc/supervisor/conf.d` 文件夹里面所有后缀为 `.conf` 的文件，现在不需要改动这个文件。

`/etc/supervisor/conf.d/` 文件夹下默认是空的，我们要自己创建新的配置文件。

使用命令：`vim /etc/supervisor/conf.d/my.conf`，编辑并保存如下内容：

```
[program:myprogram]
process_name=%(program_name)s_%(process_num)02d
command=/usr/local/bin/php /www/myproject/artisan queue:work --quiet --tries=3 --sleep=3
directory=/www/myproject
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
```

这里我们启动了一个守护进程，用来执行 Laravel 的队列任务：`php artisan queue:work`。

配置项说明：

```
# program:<进程名字>
[program:myprogram]

# 进程的名字规则，按照下面的配置就可以了
process_name=%(program_name)s_%(process_num)02d

# 执行的命令 /usr/local/bin/php 是 PHP 二进制文件位置，相当于在 Laravel 下执行 php artisan 一样
command=/usr/local/bin/php /www/myproject/artisan queue:work --quiet --tries=3 --sleep=3

# 项目所在目录，注意这里一定要填，网上搜的教程很多都忽略了这个字段
directory=/www/myproject

# 是否在 supervisor 启动的时候自动启动进程
autostart=true

# 当进程在 running 状态下 exit 时，是否自动重启
autorestart=true

# 这里一定要填对，如果你是用 Laravel 执行队列任务，那跟我填的一样就可以，如果你执行其他任务，请填写所属的用户组，不然会有权限问题
user=www-data

# 启动多少个子进程，一般启动 1 个就够了
numprocs=1

# 设置为 true 时，将进程报错的输出内容写到 supervisor 的输出文件 stdout 里，建议为 true，这样报错了可以查错误信息
redirect_stderr=true
```

> 注意把上面的 myproject 替换成自己项目的路径

保存好配置文件后，就可以启动 supervisor 了。

```
# 以下两种方法皆可启动 supervisor

# 1、指定配置文件的方式启动 supervisor（推荐）
supervisord -c /etc/supervisor/supervisord.conf

# 2、服务的方式启动
systemctl start supervisord
```

执行成功的情况下，你会看到如下信息：

```
Unlinking stale socket /var/run/supervisor.sock
```

如果不是这个信息，说明你的配置文件有问题，检查一下 `command` 和 `directory` 路径是否填写正确，99% 启动不成功都是这两个字段填写不正确。

```
# 查看 supervisor 进程
supervisorctl status

# 可以看到下面的输出结果
myproject:myprogram                 RUNNING   pid 17, uptime 0:01:40
```

显示为 `RUNNING` 则说明正常运行，如果不是这个状态就是配置文件出错了。

如果修改了配置文件，或者创建了新的配置文件，需要重载才能读取到新的配置，执行如下命令：

```
# 重新读取配置文件
supervisorctl reread

# 更新运行状态
supervisorctl update
```

## Laravel 队列任务无法写入 Log 日志问题

用 supervisor 执行队列任务时发现 Laravel 的日志系统 Log 竟然无法写入日志文件，查了下也没有任何报错信息，问题的原因是所在用户组没有权限。

也就是配置文件中的，user 字段：

```
user=www-data
```

这里一定要填写运行程序的用户组，比如 PHP 的用户组是 `www-data`，如果你填的是 `root`，这样就没有权限操作日志文件了。