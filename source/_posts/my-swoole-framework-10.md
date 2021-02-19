---
title: 从零开始搭建自己的Swoole框架（十）数据库迁移
date: 2021-02-14 00:34:09
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 利用 phinx 生成数据库迁移。

---
## 前言
通常情况下我们要创建 MySQL 数据库的表需要手动创建 SQL 语句。

然而这样一方面是很不方便，另一方面也不安全，如果修改表结构的时候不小心改错了，就会造成无法挽回的后果，而且最关键的是还不知道是谁干的！

之前上班的时候同事就遇到这种情况，有一个同事不小心删了另一个同事要用的表，结果不言而喻……

## Phinx
官方网站：[https://book.cakephp.org/phinx/0/en/install.html](https://book.cakephp.org/phinx/0/en/install.html)

Phinx 是一个数据库迁移插件，使用它可以通过 PHP 代码来创建表或者修改表结构。

如此一来就不需要手动使用 SQL 语句去修改数据库了。

## 安装 Phinx
使用命令：`require robmorgan/phinx`

完成安装后，再执行 `vendor/bin/phinx init`：

```
/www/blog# vendor/bin/phinx init
Phinx by CakePHP - https://phinx.org.

created /www/blog/phinx.php
```

可以发现它在项目根目录自动创建了一个文件。

## 配置 Phinx
打开上一步得到的 phinx.php：

```
<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'production_db',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'development_db',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'testing_db',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];

```

这个就是数据库的配置表，在这里填上自己的数据库账户和密码。

这里有不同的开发环境配置：production（线上环境）、development（开发环境）、testing（测试环境）。

我们暂且只要配置：development 即可。

paths 字段是数据库迁移文件的存放位置，默认是在项目根目录下的 db 文件夹：

```
'paths' => [
    'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
    'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
],
```

数据库迁移文件属于项目的一部分，因此我把它修改成了在 app 目录下：

```
'paths' => [
    'migrations' => '%%PHINX_CONFIG_DIR%%/app/database/migrations',
    'seeds' => '%%PHINX_CONFIG_DIR%%/app/database/seeds'
],
```

然后创建对应的文件夹即可。

## 创建表
配置好之后就可以使用命令来创建表了：

```
vendor/bin/phinx create User
```

上述命令生成了 User 表的数据库迁移文件，

可以发现在 app/database/migrations 目录下多出了一个文件：

```
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class User extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {

    }
}
```

在 change 方法里添加代码：

```
public function change()
{
    // create the table
    $table = $this->table('users');
    $table->addColumn('name', 'string', ['limit' => 32])
        ->addColumn('password', 'string', ['limit' => 64])
        ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
        ->create();
}
```

上述代码创建了一张 users 表，包括名称、密码和注册日期。

然后打开 MySQL 数据库，创建一个名字叫做 blog 的数据库。

> 注意！这里的数据库名字要与 phinx.php 配置文件对应

## 创建表
数据库迁移文件写好之后，就可以用命令执行数据库迁移了。

```
vendor/bin/phinx migrate
```

执行完成之后再返回查看 blog 数据库，可以发现 users 表已经创建好了。

除了 users 表之外，还有一张 phinxlog 表，这是用来保存迁移记录的。

## 后言
数据库迁移属于项目单独引用的，以后再考虑封装到框架里面。
