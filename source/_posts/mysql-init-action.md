---
title: MySQL新增用户、修改密码、设置权限
date: 2020-06-21 17:42:53
tags:
 - MySQL
 
description: MySQL 部署必备命令合集。

---
## 前言
每次部署新项目都要重新设置 MySQL 的用户及权限，但这些基础命令又不经常用到，每次都要重新查找，于是整合了一下发成博文以便后续直接 **复制粘贴**。

网上搜索的大都是低版本 MySQL 或者根本就是错误的代码，不知道他们这样直接复制粘贴别人的代码但又没试过的是什么心态，简直浪费别人的时间。（尤其点明某 CSDN）

错误的范例：

```
grant all privileges *.* to '要创建的用户'@'localhost' identified by '自定义密码';
```

> 上述代码在 MySQL 8 直接报错

本文记录的命令以最新版的 MySQL 8 为基准。

## 用户介绍
这一段科普 MySQL 用户知识，如需直接得到问题的解决答案可直接跳过。

MySQL 的所有用户均在 mysql 这个数据库里面保存。

```
mysql> use mysql;
Reading table information for completion of table and column names
You can turn off this feature to get a quicker startup with -A

Database changed
mysql> select user,host from users;
ERROR 1146 (42S02): Table 'mysql.users' doesn't exist
mysql> select user,host from user;
+------------------+-----------+
| user             | host      |
+------------------+-----------+
| mysql.infoschema | localhost |
| mysql.session    | localhost |
| mysql.sys        | localhost |
| root             | localhost |
+------------------+-----------+
4 rows in set (0.00 sec)
```

其中，`authentication_string` 为加密后的密码。

```

mysql> select user,host,authentication_string from user;
+------------------+-----------+------------------------------------------------------------------------+
| user             | host      | authentication_string                                                  |
+------------------+-----------+------------------------------------------------------------------------+
| mysql.infoschema | localhost | $A$005$THISISACOMBINATIONOFINVALIDSALTANDPASSWORDTHATMUSTNEVERBRBEUSED |
| mysql.session    | localhost | $A$005$THISISACOMBINATIONOFINVALIDSALTANDPASSWORDTHATMUSTNEVERBRBEUSED |
| mysql.sys        | localhost | $A$005$THISISACOMBINATIONOFINVALIDSALTANDPASSWORDTHATMUSTNEVERBRBEUSED |
| root             | localhost |                                                                        |
+------------------+-----------+------------------------------------------------------------------------+
5 rows in set (0.00 sec)

```

当然，即使是 MySQL 系统创建的数据库同样适用 SQL 语句，你可以直接通过 `update` 命令修改字段：

```
mysql> update user set host="%" where user="root";
Query OK, 1 row affected (0.01 sec)
Rows matched: 1  Changed: 1  Warnings: 0

mysql> flush privileges;
Query OK, 0 rows affected (0.01 sec)
```

上述命令是有效的，它的作用是把 `root` 用户的权限修改为 %（通配符），即除了 localhost（本地连接）之外，任何主机都可以通过该用户访问 MySQL。

当然这是不安全的，因为我们现在还没有给 root 用户设置密码，也就是说任何人都可以无密码访问到 MySQL。

虽然我们可以通过 `update` 直接修改 `authentication_string` 字段，但是这个字段是要经过加密的，不能直接修改成某个密码的值。

具体操作方法见下面的介绍。

## 新增用户
非本地连接时，最好不要使用 root 登录，而是创建一个新用户并且指定权限。

MySQL 创建新用户命令：

```
create user '用户名'@'权限' identified by '密码';
```

其中，需要设置三个字段的值：

- 用户名：即登录的用户名，如：root2
- 权限：可选值为 % 或者 localhost 或指定具体 IP，如果要通过第三方软件（如 Navicat）必须将这个值设置为 % 或者某个主机 IP，推荐使用 %，表示所有 IP，如果设置为 localhost（本地）表示只有这台服务器才能访问。
- 密码：设置登录的密码

示例：

```
create user 'huotu'@'%' identified by 'huotublog';
```

上述代码表示创建一个具有远程登录权限的用户 huotu 且密码为 huotublog。

> 刷新权限命令：flush privileges;

## 修改密码
如果需要修改用户的密码可以使用如下命令，不能直接使用 update 更新：

```
Alter user 'huotu'@'%' identified by '新密码';
```

修改完成后需要刷新权限：

```
flush privileges;
```

## 设置权限
如果不设置权限，那么新增的用户就无法操作表。

在 MySQL 中所有用户都拥有某些数据库和表的某些权限，比如 root 用户具有最高权限，拥有操作所有数据库和表的能力。

一个用户的权限可以是一整个数据库，也可以对应许多个数据库，也可以是一个数据库中的某个表。

命令如下：

```
grant all privileges on *.* to 'huotu'@'%' with grant option;
```

上述命令赋予 `huotu` 用户所有数据库的表所有操作（增删改查）。

`with gran option` 表示该用户可给其它用户赋予权限，但不可能超过该用户已有的权限。

`all privileges` 对应具体的权限，可换成 select,update,insert,delete,drop,create 等操作。

示例：

```
grant select,insert,update on *.* to 'huotu'@'%';
```

表示 `huotu` 只有对数据库中的表查询、插入和修改的权限，因此他无法进行删除操作。

`*.*` 这里的 * 指的是通配符，第一个 * 的位置指定数据库，设置为 * 表示所有数据库，也可以指定某个数据库，比如：myblog.*。（myblog 是一个数据库，表示拥有 myblog 数据库对所有表的操作权限）

第二个 * 指的是数据库中的某个表，设置为 * 表示对数据库所有表都有权限，也可以指定具体的表：myblog.articles。

## 查看授权信息
查询某个用户的操作权限。

```
show grants for 'root'@'localhost';
```

## 撤销权限
删除某个用户的全部权限。

```
revoke all privileges on *.* from 'huotu'@'%';
```

## 删除用户
将用户数据删除。

```
drop user 'huotu'@'%';
```

## 疑点解析
上述所有命令基本都是 `'huotu'@'%'`，在看到这些命令的时候我在想后面的权限是有必要的吗？

因为已经有用户名了，附带权限不是多此一举？

其实只要测试一下就知道了，我们可以通过 `create` 命令创建一个 user 字段相同的用户，比如 root，但是权限不同，即 `%`，此时查询出来这两个同名的用户是并存的。

```
mysql> create user 'root'@'%' identified by '123';
Query OK, 0 rows affected (0.00 sec)

mysql> select user,host,authentication_string from user;
+------------------+-----------+------------------------------------------------------------------------+
| user             | host      | authentication_string                                                  |
+------------------+-----------+------------------------------------------------------------------------+
| root             | %         | *23AE809DDACAF96AF0FD78ED04B6A265E05AA257                              |
| mysql.infoschema | localhost | $A$005$THISISACOMBINATIONOFINVALIDSALTANDPASSWORDTHATMUSTNEVERBRBEUSED |
| mysql.session    | localhost | $A$005$THISISACOMBINATIONOFINVALIDSALTANDPASSWORDTHATMUSTNEVERBRBEUSED |
| mysql.sys        | localhost | $A$005$THISISACOMBINATIONOFINVALIDSALTANDPASSWORDTHATMUSTNEVERBRBEUSED |
| root             | localhost | *05F9DE4C759F49574C4400083F80107567B47C2E                              |
+------------------+-----------+------------------------------------------------------------------------+

```

如果不添加权限那这两个同名用户就无法区分了，也就是说 user 字段加上 host 字段组成唯一键，而不是 user 字段唯一。