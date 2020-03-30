---
title: Docker更换镜像源
date: 2020-03-31 01:21:03
tags:
  - Docker
  
categories: Docker

description: 更换 Docker 默认的镜像源为国内镜像，可以大幅提高镜像的下载速度。

---
## 国内镜像源

### Docker 官方镜像中国区
https://registry.docker-cn.com

### 网易
http://hub-mirror.c.163.com

### 中科大
https://docker.mirrors.ustc.edu.cn

### 阿里云
阿里的镜像异常麻烦，本着求真的角度，还是把这一部分补充了 %>_<%。

首先需要注册成为阿里开发者，前往：[阿里云开发者中心](https://dev.aliyun.com/search.html)。

注册并登陆后，点击右上角的**控制台**：

![image](https://user-images.githubusercontent.com/28209810/64141265-f297c080-ce39-11e9-92ca-1c89280fe717.png)

想吐槽阿里云的界面设计，阿里云是一个超级聚合体……里面的服务实在太多，如果不写这样一个图文教程很难找到自己想要的功能，操作步骤如下图所示：

![image](https://user-images.githubusercontent.com/28209810/64141431-91242180-ce3a-11e9-87d2-6edc56597f8c.png)

进入容器镜像服务，点击左侧菜单的**镜像中心-镜像加速器**：

![image](https://user-images.githubusercontent.com/28209810/64141511-e5c79c80-ce3a-11e9-8fe4-037f95849052.png)

根据阿里的提示操作即可：

```
sudo mkdir -p /etc/docker
sudo tee /etc/docker/daemon.json <<-'EOF'
{
  "registry-mirrors": ["https://93m46zjd.mirror.aliyuncs.com"]
}
EOF
sudo systemctl daemon-reload
sudo systemctl restart docker
```

这里的 `https://93m46zjd.mirror.aliyuncs.com` 是我的个人镜像源加速地址，建议自己申请一个。

## 更换 Docker 镜像源

### Linux 系统
Docker 使用 `daemon.json` 作为配置文件，如果没有的话则创建，编辑 `daemon.json`：

```
vim /etc/docker/daemon.json
```

添加仓库地址（以中科大镜像源为例）：

```
{
  "registry-mirrors": ["https://docker.mirrors.ustc.edu.cn"]
}
```

完成并保存，重启 docker：

```
service docker restart
```

作为一个学府，中科大还十分贴心的写了帮助文档：[Docker 镜像使用帮助](https://lug.ustc.edu.cn/wiki/mirrors/help/docker)

### Windows 系统
Docker for Window 可以直接通过右键右下角小鲸鱼，选择 Settings，选择 Daemon 选项卡，在右下角的 Registry mirrors 添加对应的镜像源地址即可。

### Mac 系统
如果你是下载了 Docker 桌面版的 Mac 系统用户，启动 Docker，选择右上角的小鲸鱼图标，选择菜单中的 `Preferences`：

![image.png](https://i.loli.net/2019/09/03/PeijKIqDGrZVgct.png)

在选项卡中选择 Daemon，点击 `Registry mirrors` 下方的加号，输入需要添加的国内镜像源地址，完成后点击底部的 `Apply & Restart` 应用配置并重启 Docker：

![image](https://user-images.githubusercontent.com/28209810/64140700-f4f91b00-ce37-11e9-8e02-e5c4d2b1682c.png)

