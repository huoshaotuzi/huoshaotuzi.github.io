---
title: Trait - 优雅的复用方法
date: 2020-03-15 00:40:51
tags:
 - PHP
 
categories: PHP

description: 从 PHP 的 5.4.0 版本开始,PHP 提供了一种全新的代码复用的概念，那就是 Trait。

---
## Trait
为了解决单继承问题，从 PHP 5.4 开始新增了 `trait` 关键字来实现代码的复用。`trait` 定义的代码块在类的内部引入，类就能获得由 `trait` 定义的属性及方法。

通过定义一个 `SingletonTrait`，来实现单例模式的类共用代码块：

```
trait SingletonTrait
{
    protected static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}
```

要引用 `Trait` 十分简单，只要在类的内部使用 `use` 关键字即可：

```
class SomeService
{
    use SingletonTrait;

    public function test()
    {
        echo 'ok!';
    }
}

$service = SomeService::getInstance();
$service->test();
```

通过引用 `SingletonTrait` 代码块 `SomeService` 直接获得了 `trait` 定义的方法及属性，通过 `trait` 引用实现复用单例模式方法，而不是直接复制粘贴同样的代码到每个单例的类中，让代码变得更加优雅！

> Trait 的实现原理是简单的把代码块拷贝到类

## 访问修饰符
在类的继承关系中，如果父类的属性或方法使用了 `private` 声明，子类是无法调用的，但是在 Trait 中不同，因为 Trait 相当于把代码引入到类里面，也就是变成了类的一部分，因此当 Trait 声明了私有属性或方法，在类的内部是可以直接使用的。

Trait 定义的代码块同样可以使用 `static`、`abstract` 等修饰符。

## 多个 Trait
一个类可以引用多个 Trait，中间使用逗号隔开。

当引入的多个 Trait 里面存在同名方法时，需要通过两种方式来解决冲突，否则会报出致命异常：

```
PHP Fatal error:  Trait method xxx has not been applied, because there are collisions with other trait methods on Test in xxx
```

### insteadof
使用 `insteadof` 关键字来让其中一个 Trait 的方法覆盖另一个。

```
trait A
{
    public function hello()
    {
        echo 'A:hello' . PHP_EOL;
    }

    public function world()
    {
        echo 'A:world' . PHP_EOL;
    }
}

trait B
{
    public function hello()
    {
        echo 'B:hello' . PHP_EOL;
    }

    public function world()
    {
        echo 'B:world' . PHP_EOL;
    }
}

class Test
{
    use A,B {
        // 使用 A trait 中的 hello 覆盖 B 的 hello
        A:: hello insteadof B;
        // 使用 B trait 中的 world 覆盖 A 的 world
        B:: world insteadof A;
    }
}

$test = new Test();
$test->hello();
$test->world();
```

输出结果：

```
A:hello
B:world
```

### as 方法重命名
如果需要保留两者的方法，可以使用 `as` 重命名，然后再用另一个的方法进行覆盖：

```
trait A
{
    public function hello()
    {
        echo 'A:hello' . PHP_EOL;
    }

    public function world()
    {
        echo 'A:world' . PHP_EOL;
    }
}

trait B
{
    public function hello()
    {
        echo 'B:hello' . PHP_EOL;
    }

    public function world()
    {
        echo 'B:world' . PHP_EOL;
    }
}

class Test
{
    use A, B {
        // 将 A trait 中的方法重命名
        A::hello as ahello;
        A::world as aworld;
        // 再使用 insteadof 关键字覆盖冲突的方法
        B:: hello insteadof A;
        B:: world insteadof A;
    }
}

$test = new Test();
$test->hello();
$test->world();
$test->ahello();
$test->aworld();
```

注意，即使重命名了也需要使用 `insteadof` 覆盖原来的代码，不然同样会产生致命报错。

## Trait 嵌套
Trait 里面也可以引用其他 Trait。


## 优先级
当 Trait 中定义的方法或属性与类或其父类相同时，其优先级如下：

```
子类 > trait > 父类
```

## Trait 的意义

Trait 能实现代码块的复用，但是继承（extends）、实现（implements）同样可以复用父类的方法或实现接口的方法，它们之间有什么区别呢？

我们知道面向对象编程里代码的关联性十分重要，例如继承关系即把子类的属性和方法进行了抽象，接口同样是把相同的东西抽象出来，然后在子类进行实现，这样有时候我们遇到像“水陆两栖动物”这种特殊的类型，既要让它继承水生动物的特性，又要让它继承陆生动物的特性，而 PHP 不支持多继承，要实现这种效果会变得十分麻烦。

而 Trait 定义的代码块，并没有严格意义上的关联性，仅仅只是为了复用代码块而被设计。Trait 的作用更像是一个功能块，不论是谁，只要让其他的类“嵌入”这个功能块就能让它具有对应的效果。

就好比 LOL 里的提莫，你可以出帽子、法穿棒等纯粹的 AP 装让它变成 AP 提莫，也可以出纳什之牙、飓风让提莫变成一个普攻型的 APC（远程输出单位），Trait 的作用类似于装备效果，任何人都可以出这件装备，只要装备了就能得到对应的能力。

在 Laravel 框架里，要实现“软删除”功能十分简单，只需要在数据库迁移中添加字段：

```
Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            // .. 省略其他字段
            $table->softDeletes();
        });
```

然后在 Model 里直接引入 Trait：

```
namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
}
```

即可让一个模型具有软删除的功能。

Laravel 还有许多地方存在此类的应用，通过这样的设计让代码更加优雅！

## 结尾
凡是在开发过程中，只要意识到自己通过 Ctrl+C、Ctrl+V 复制了同一份的代码，就表明这个地方写的不够好，一定存在优化的空间。