---
title: Mac系统安装Pecl
date: 2020-03-31 01:15:19
tags:
  - PHP
  
categories: PHP

description: PECL 是 PHP 扩展的存储库，可以使用 pecl install 命令来安装被 PECL 库收录的 PHP 扩展。

---
## PECL 简介
PECL（The PHP Extension Community Library）是 PHP 扩展的存储库，为 PHP 所有的扩展提供提供托管和下载服务。

一些常用及优秀的 PHP 扩展均被收录在 PECL 中，如：yaf、swoole 等等，我们可以方便的使用 pecl 命令来安装这些扩展。

PECL 支持的扩展：[PECL All Packages](http://pecl.php.net/)

在 Mac 系统或 Linux 系统中可能没有默认安装 pear，因此无法使用 pecl 命令，本文将介绍如何安装 pear。

## PEAR 安装方法
官方文档：[Getting and installing the PEAR package manager](https://pear.php.net/manual/en/installation.getting.php)

### 1、下载 PEAR
使用 curl 命令下载即可：

```
curl -O https://pear.php.net/go-pear.phar
```

### 2、 安装 PEAR
下载完成后，执行下面命令进行安装：

```
sudo php -d detect_unicode=0 go-pear.phar
```

### 3、配置
安装过程需要配置参数：

```
Below is a suggested file layout for your new PEAR installation.  To
change individual locations, type the number in front of the
directory.  Type 'all' to change all of them or simply press Enter to
accept these locations.

 1. Installation base ($prefix)                   : /usr
 2. Temporary directory for processing            : /tmp/pear/install
 3. Temporary directory for downloads             : /tmp/pear/install
 4. Binaries directory                            : /usr/bin
 5. PHP code directory ($php_dir)                 : /usr/share/pear
 6. Documentation directory                       : /usr/docs
 7. Data directory                                : /usr/data
 8. User-modifiable configuration files directory : /usr/cfg
 9. Public Web Files directory                    : /usr/www
10. System manual pages directory                 : /usr/man
11. Tests directory                               : /usr/tests
12. Name of configuration file                    : /private/etc/pear.conf

1-12, 'all' or Enter to continue: 
```

修改安装时的根目录，输入 1，再输入 `/usr/local/pear`，回车；

修改命令的安装目录，输入 4，再输入 `/usr/local/bin`，回车；

其它选项使用默认即可，一路回车。

### 4、测试是否安装成功
输入命令 `pear version`，如果成功安装将会看到类似如下信息：

```
PEAR Version: 1.10.9
PHP Version: 7.1.23
Zend Engine Version: 3.1.0
Running on: Darwin hongjiahuangdeMac-mini.local 18.6.0 Darwin Kernel Version 18.6.0: Thu Apr 25 23:16:27 PDT 2019; root:xnu-4903.261.4~2/RELEASE_X86_64 x86_64
```

接下来就可以使用 `pecl install <扩展名称:版本号>` 安装各种被 PECL 收录的扩展了。

