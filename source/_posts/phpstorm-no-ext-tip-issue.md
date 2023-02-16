---
title: PHPStorm逼死强迫症患者解决方法
date: 2020-03-31 00:40:47
tags:
 - PHP
 
categories: PHP

description: PHPStorm 在使用某些扩展提供的函数时，会报扩展在 composer.json 缺失的问题，如使用 json 相关的函数会提示 “ext-json missing in composer.json”，虽然并不是真正的报错，但是这种提示对于强迫症来说实在太难受了，看完这篇文章，强迫症患者就舒服了。

---
## PHPStorm 
在使用 JSON 函数的时候，会报如下提示：

```
ext-json missing in composer.json
```

![image.png](https://i.loli.net/2019/11/26/sYow5WKkvbRdXOV.png)

花花绿绿看得猛男落泪，简直要逼死强迫症啊！

原因可能是因为使用了 Docker 或者其他的环境，由于扩展是安装在虚拟机上就导致本机无法正确识别 PHP 的扩展，并不是本机上没有安装这个扩展，代码没有问题也不会报错，就是看得难受。

## 解决方案
所幸，这里提供了两种方法可以根治此问题。

### 方案一
针对缺失的扩展，在 `composer.json` 文件的 `require` 字段进行添加。

例如：`"ext-json": "*"`：

![image.png](https://i.loli.net/2019/11/26/IH3GgJQB4XWpz8y.png)

如果没有 `composer.json` 文件，在项目根目录下自行创建一个即可。

这种方法针对特定的扩展，除了 JSON 扩展，常见的还有 CURL 扩展也无法正常识别，用相同的方法即可解决，扩展不多的时候可以用这种方法，要是使用了诸多第三方扩展而 PHPStorm 无法识别，可以参考下面的方案二一次性解决问题。

### 方案二
关闭 PHPStorm 缺失扩展提示。

打开左上角 `File->Settings` 搜 PHP，然后找到下方一行的 `Inspections`，在右侧搜索 `extension`，找到 `Extension is mission in composer.json` 取消勾选保存即可，见下图：

![image.png](https://i.loli.net/2019/11/26/bB9RVYwCf6DtmGL.png)

以上方法任选一种即可。

![image.png](https://i.loli.net/2019/11/26/UZdEPz9Hg5MkXyt.png)

猛的一顿操作之后——舒服多了！！