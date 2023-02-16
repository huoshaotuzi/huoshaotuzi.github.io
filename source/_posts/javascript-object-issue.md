---
title: Javascript使用对象必须知道的一件事
date: 2020-04-01 16:32:49
tags:
  - JavaScript

categories: JavaScript

description: JavaScript中，对象即引用。

---

## JavaScript 的对象
JavaScript 中，对象即引用。

我们知道引用的特性：

将对象赋值给另一个变量，另一个变量的值如果改变，原对象的值也会改变。

引用即是内存地址的指向，将对象赋值给另一个变量，相当于另一个变量也指向了同一块内存地址，因此改变值时，原对象的值也会跟着变。通过下面的例子来了解一下引用的特性。

示例：

```
// 声明一个json对象
let json = {
    "aa": "Im aa",
    "bb": "Im bb"
};
// 将json对象赋值给另一个变量
let data = json;
data.aa = "no ok!";
// 输出两个对象
console.log(json, data);
```

在控制面板上的输出结果：

```
> Object { aa: "no ok!", bb: "Im bb" } Object { aa: "no ok!", bb: "Im bb" }
```

在这个示例中，我们原本是希望把 json 变量的值赋值给另一个变量 data，本意是不希望修改 json 的值，但由于 JavaScript 中对象即引用的特性会导致原来的值发生改变。

## 解决方法
可以使用 `Object.assign(target, source)` 方法将 source 对象复制一份给 target 变量，类似 PHP 中的 clone，复制出来的变量会独立占据一片内存空间，而不是原对象的引用。

示例：

```
// 声明一个json对象
let json = {
    "aa": "Im aa",
    "bb": "Im bb"
};
// 复制对象到data
let data = {};
Obejct.assign(data, json);
// 修改aa的值
data.aa = "no ok!";
// 输出两个对象
console.log(json, data);
```

输出结果：

```
> Object { aa: "Im aa", bb: "Im bb" } Object { aa: "no ok!", bb: "Im bb" }
```

可以看到，原来的 json 对象的值没有改变。

在 TypeScript 中，不能直接使用 `Object.assign` 方法，而是要使用 `(<any>Object).assign`。

示例：

```
// 声明一个json对象
let json = {
    "aa": "Im aa",
    "bb": "Im bb"
};
// 复制对象到data
let data = {};
(<any>Object).assign(data, json);
```

## 完结感言
由于 cocos creator 使用的是 JavaScript 和 TypeScript 开发，今天在制作游戏的事件系统，遇到一个神秘的 BUG，排查了好久一直没找到原因，突然想起来以前也遇到过同样的问题，为了涨点记性，特此记录。