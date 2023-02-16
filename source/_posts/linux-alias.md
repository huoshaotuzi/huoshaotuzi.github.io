---
title: Linux自定义别名——大幅提高工作效率！
date: 2020-03-31 01:29:34
tags:
  - Linux
  
categories: Linux

description: Linux 系统需要使用繁多的命令，有时候你可以把常用的命令设置为别名（相当于缩写），大大提高工作效率。

---
## Linux 系统命令
在 `Linux` 系统中，诸如 `ls`、`top`、`ps` 此类为 `Linux` 系统内置的命令，我们希望通过自定义命令来作为某些复杂命令的组合，如自定义 `ll` 为 `ls -alF` 的简写，通过简写可以大幅缩短输入命令的时间，还可以避免输错命令，何乐而不为呢？

## 自定义 Linux 命令 / 添加别名 Alias
自定义命令其实就是添加一个别名，执行如下命令创建别名：

```
cd ~
vim .bash_profile
```

在这个文件里输入需要创建的别名，例如 `ll`：

```
alias ll='ls -alF'
```

然后保存，再执行：

```
source .bash_profile
```

现在，使用 `ll` 等价于输入 `ls -alF`。

可以将常用的命令组合简写，例如我们常用来查看进程的命令：

```
alias psp='ps -ef|grep'
```

以后只需要输入：

```
# 查看 PHP 进程
psp php

# 输出结果
FireRabbitdeMacBook-Pro:~ firerabbit$ psp php
  501 89356     1   0 二07下午 ??        29:02.94 /Applications/PhpStorm.app/Contents/MacOS/phpstorm
  501 99412 99183   0  9:20下午 ttys005    0:00.00 grep php
```

对于记不住命令的小金鱼们来说，利用好别名可以事半功倍哟！

## 注意事项！
别名的使用只有定义者自己知道，因此不适合多人协作的工作。在日常的开发中（如在个人的 Mac），可以自定义一些常用的别名来提高自己工作的效率。
