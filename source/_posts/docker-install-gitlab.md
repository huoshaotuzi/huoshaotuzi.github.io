---
title: 再战！Docker 安装 Gitlab
date: 2020-03-07 22:21:32
tags:
 - docker
 - gitlab
 
categories: docker

description: Docker环境搭建Gitlab。

---
## 前言
上一次因为服务器配置太低翻车了，现在重新挑战一次。

## Gitlab 硬件要求
Gitlab 十分吃机器的配置，Gitlab 官方推荐 2核 4G，最低建议 1 核 2G，再低的话可能会像我上次那样直接翻车或者运行起来十分卡顿。

由于是个人学习，就不打算将其部署到线上的服务器上了，这一次我采用在本地安装的方式搭建 Gitlab。

### 下载镜像

拉取 Gitlab 官方最新版本镜像：

```
docker pull gitlab/gitlab-ce:latest
```

由于镜像较大，建议换成国内镜像提高下载速度。

### 运行镜像

下载完成后，再执行 `run` 启动容器：

```
sudo docker run --detach \
    --hostname localhost \
    --publish 4443:443 --publish 999:80 --publish 22:22 \
    --name gitlab \
    --restart always \
    --volume ~/docker/gitlab/config:/etc/gitlab \
    --volume ~/docker/gitlab/logs:/var/log/gitlab \
    --volume ~/docker/gitlab/data:/var/opt/gitlab \
    gitlab/gitlab-ce:latest
```

`run` 参数说明：

```
--detach：让容器在后台运行
--hostname：主机地址，本地使用 localhost，可以换成域名
--publish：宿主机的端口映射到容器的端口，由于我本地已经有其他容器使用了 443 和 80 端口，因此我改成了 4443 和 999 端口。
--name：自定义容器的名称
--restart：容器重启策略，在退出时容器应该如何重启或不应该重启，always 始终重启 
--volume：宿主机映射到容器的卷，用来做容器数据的持久化，这里我将卷的目录设置为 ~/docker/gitlab
```

`run` 命令执行后，可以执行如下命令：

```
cd ~/docker/gitlab
ls
```

可以看到自动生成了以下几个文件：

```
config	data	logs
```

这些文件就是宿主机与容器之间通过卷映射的文件（容器数据持久化）。

执行 `docker ps` 可以看到容器运行状态：

![image](https://user-images.githubusercontent.com/28209810/64143262-d0a23c00-ce41-11e9-81d3-4b727313dacf.png)

### Gitlab 初始化
容器启动后，需要稍等几分钟，然后再访问 `127.0.0.1:999`，如果直接访问的话可能 Gitlab 还未完全启动，这个时候是访问不了的。

第一次访问时，需要设置管理员（root 用户）密码：

![image](https://user-images.githubusercontent.com/28209810/64143324-10692380-ce42-11e9-98a9-f7372dc57b02.png)

初始化密码设置完成后，返回到登录页面，使用账户 root 以及刚才设置的密码进行登录：

![image](https://user-images.githubusercontent.com/28209810/64143342-31317900-ce42-11e9-98df-6ea83f67a14a.png)

登录成功后，就可以操作界面啦！

![image](https://user-images.githubusercontent.com/28209810/64143494-dba99c00-ce42-11e9-864a-861c4a2adacc.png)

### 添加 SSH key
在开始使用之前，需要添加 `ssh key` 才能拉取或者推送到仓库，执行命令：

```
cd ~/.ssh
ls
```

查看是否生成过 `ssh key`，如果当前目录下没有文件，则需要创建新的 `ssh key`，执行以下命令生成 `ssh key`，`-C` 参数后面是你的邮箱地址：

```
ssh-keygen -t rsa -C "your_email@example.com"
```

完成后在当前目录下会生成 `ssh key`，包含两个文件，这是一对密匙：

```
id_rsa		id_rsa.pub
```

其中，`id_rsa.pub` 是公钥，我们需要的就是这个文件：

```
cat id_rsa.pub
```


将输出的密匙字符串复制下来（注意是把所有的内容都复制下来，包括邮箱）。

![image](https://user-images.githubusercontent.com/28209810/64162429-dca5f200-ce71-11e9-8812-5d3a965ea569.png)

接着返回 Gitlab，点击左上角个人头像，选择 `Settings`：

![image](https://user-images.githubusercontent.com/28209810/64161700-a320b700-ce70-11e9-8444-2370d88bff56.png)

选择左侧菜单 `SSH Keys`，将你的 `ssh key` 黏贴到方框内，然后点击 `Add key` 即可：

![image](https://user-images.githubusercontent.com/28209810/64162574-27276e80-ce72-11e9-95e6-e2e23ca57098.png)

接下来就可以愉快的开始使用 Gitlab 啦！

## 为什么安装后的 Gitlab 可以直接访问？
这是由于 Gitlab 内置了 nginx 服务器，所以才能在安装完成后通过地址进行访问。

完结撒花～ ҉٩(*´︶`*)۶҉ ??