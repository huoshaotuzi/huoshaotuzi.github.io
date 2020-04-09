---
title: MySQL占用内存过高优化记录
date: 2020-04-09 20:57:07
tags:
 - MySQL
 - 架构
 
categories: MySQL

description: MySQL 占用内存高达 34% 优化解决方案。

---

## 前言
在腾讯云购买的 1 核 1G 主机，使用 Docker 搭建的 MySQL 内存占用竟然高达 34%！再加上 Docker 其他容器运行起来和系统本身程序占用的内存，总内存高达 80+%，由于其他程序使用了 Redis 作为缓存，1G 内存就剩下 10%+ 可用内存（100MB+），一不小心可能就会让服务器卡成渣甚至直接 GG。

由于服务器一天的访问量并不多，并发访问也不高，并不需要把 MySQL 优化的多好，只要能正常运行就够了，加上有 Redis 缓存这一层，几乎很少会跑到 MySQL 查询。 

所以综合起来，MySQL 压根不用担心它会挂掉，可以把默认的优化方案修改一下，以便把占用的内存释放出来。

优化后的结果如下图：

![MySQL优化内存](https://ae01.alicdn.com/kf/H8de4ff5c012d438f96db914a35d0e207i.png)

## 查询内存占用
使用 `top` 命令查询当前程序的内存占用情况。

我们需要关注的部分如下：

```
  PID USER      PR  NI    VIRT    RES    SHR S %CPU %MEM     TIME+ COMMAND                                                                                                          
 1751 root      20   0  740212  11140   1476 S  1.2  1.1 106:46.57 barad_agent                                                                                                      
 1750 root      20   0  164512   8648   1312 S  0.6  0.9  20:52.41 barad_agent                                                                                                      
29245 root      20   0  155148   2380    660 S  0.6  0.2   0:24.18 sshd                                                                                                             
31044 root      20   0  135552   6028   1468 S  0.6  0.6   0:11.02 YDService                                                                                                        
    1 root      20   0   43656   2764   1364 S  0.0  0.3   2:19.11 systemd  
```

- `PID`：进程 ID
- ` %CPU`：CPU 占用百分比
- `%MEM`：内存占用百分比
- `COMMAND`：程序命令名称

如果发现某个不需要的进程占用了过高的内存或 CPU，可以直接使用 `kill <PID>` 杀掉进程。

进程杀掉程序就停了，我们不能直接杀掉 MySQL，具体解决思路见下一个步骤。

## 解决思路
MySQL 在启动的时候，会占用一部分的内存来作为缓冲区，这样做的原因是可以优化查询速度，我们可以发现只要查询过一次 MySQL，然后用相同的语句再次查询，第二次查询会比第一次更快，这其中就用到了 MySQL 自身的缓存系统。

MySQL 的缓存机制是当某一个连接访问某张表时，MySQL 会先检查访问的表是否在缓存区中，如果这张表已经在缓存区中打开，那就会直接访问缓存区从而加快查询速度，如果这张表不在缓存区，那就会从实际的数据库文件进行查询，然后再把这张表加入缓存区，以便后续查询加快速度。

由于这个机制我们的 MySQL 在运行过程占用的内存会逐渐增加，1G 的内存不适合用来做 MySQL 的优化，我们要做的就是去掉 MySQL 用来加快查询的各种机制。

## 解决方案
修改 MySQL 配置文件 `my.cnf`，找到 `[mysqld]` 下添加如下内容：

```
[mysqld]
// 此处省略其他配置，添加如下内容
table_open_cache=200
table_definition_cache=400
performance_schema_max_table_instances=400
performance_schema=off
```

保存然后重启 MySQL，OK！内存已经降到 10%+ 了。

各个配置项的具体用途：

|  字段   | 用途  |
|  ----  | ----  |
| table_open_cache  | 高速缓存的大小，每当访问一个表时，如果在表缓冲区中还有空间，该表就被打开并放入其中，下次查询该表时首先从高速缓存区查询，如果表在缓存中则直接从缓存查询，从而大幅提高查询速度。 |
| table_definition_cache  | 定义了内存中可打开的表结构数量。 |
| performance_schema_max_table_instances | 检测的表对象的最大数目。 |
| performance_schema | 主要用来收集 MySQL 性能参数，启用 performance_schema 之后，server 会持续不间断地监测。【罪魁祸首】 |

通过调整前面 3 个配置项的值，占用内存均有 1~3% 程度的降低，罪魁祸首便是 `performance_schema`，将其设置为 off 之后，内存直接降低了 20%！

其详细介绍可参考 MySQL 官方文档：[MySQL Performance Schema](https://dev.mysql.com/doc/refman/5.6/en/performance-schema.html)

当然除了上面几个配置项之外，MySQL 仍有许多可以优化的配置项，但是现在既然已经实现了自己的目的，就暂时不进行扩展阅读了，以后如果需要更深入的优化，到时候再学也不迟（日均 IP 100+ 根本不用考虑什么优化嘛~）。