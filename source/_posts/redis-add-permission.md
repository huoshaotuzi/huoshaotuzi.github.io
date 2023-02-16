---
title: Redis添加密码验证
date: 2020-03-31 01:24:22
tags:
  - Redis
  
categories: Redis

description: Redis 如果没有设置密码，那么任何人都能通过端口访问到服务器上的 Redis，这是一个极大的安全隐患。
  
---

## 为什么要为 Redis 添加密码验证？
默认情况下，`redis` 不需要使用密码即可连接，而由于默认开放端口为 6379（用户一般不会去修改这个端口），导致基本信息完全暴露给试图攻击服务器者。

> IDCE.COM 建站之初未设置 redis 密码，结果被注入了挖矿病毒 %>_<%

没有任何密码验证，意味着任何人都能访问到服务器的 Redis 服务，可能造成重要的信息泄露或者被访问者恶意删除造成严重后果！

总而言之，如果要使用 Redis 服务，那么最好为 `redis` 加上密码验证可以增加系统的安全性。

## 开启 redis 密码验证
以 `Linux` 系统为例，首先找到 `redis.conf` 配置文件，如果不知道文件在哪可以使用命令 `find / -name redis.conf` 找到。

添加 `requirepass` 字段，后面即你需要设置的密码，建议生成一个足够长的随机字符串来作为密码。

![image.png](https://i.loli.net/2019/08/29/l7msnYqtQeOiUNp.png)

完成后保存，重启 `redis` 即可！

## Redis-cli 验证密码

在 `cli` 模式下，如果设置了密码需要验证之后才能执行 `redis` 操作，进入 `redis` 服务，然后执行 `auth 密码` 即可。

![image.png](https://i.loli.net/2019/08/29/VqJUpRx25HbELGA.png)

## PHP 验证 Redis 密码

```
$redis = new Redis(); 
$redis->connect('127.0.0.1', 6379); //连接Redis
$redis->auth('123456'); //密码验证
$redis->select(2);//选择数据库2
$redis->set( "testKey" , "Hello Redis"); //设置测试key
echo $redis->get("testKey");//输出value
```