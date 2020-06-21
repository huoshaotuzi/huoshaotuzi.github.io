---
title: 拆分你的代码
date: 2020-05-26 19:37:21
tags:
 - 架构

description: 随着业务的增长，一个用来实现业务逻辑的代码文件会变得非常大，尤其是在多人协作的开发中，因为每个人的习惯不同，代码中会出现奇奇怪怪的东西，随着人员流动，后期的接盘侠就变得非常痛苦。

---
## 前文概要
本文讲述的是如何把一个大文件拆分成许许多多小文件的方法。

所谓大文件其实没有一个明确定义，暂且规定一个文件如果超过 1000 行就算大文件吧！

当然也不用死脑筋，一看到代码行数多就得拆分，这完全要看情况，就好比玩游戏辅助的位置就一定得跟射手？如果脑袋不会转弯，对以后学习都很不利。死脑筋在一定程度上等同于“杠精”，本文介绍的是拆分文件的方法，而如何使用取决于一个聪明的孩子。

拆分文件的目的是解决混乱的逻辑，因为一个文件只要很大，极有可能是混杂了本来不应该出现的东西。拆分之后的小文件可能只有 100-200 行，相比一个 1000 多行的文件，哪个更容易让人看懂？

（题外话：在我看来辅助是决定游戏成败的关键因素，它的位置与打野一样重，出了辅助装的辅助不会抢经济，也就意味着这个位置可以支援任何一路，帮助中路快速清兵 Gank 上路，或者开局配合队友入侵对面野区，或者在上单被单杀后去守塔并且吃掉本来会被浪费的兵线，或者在打野队友被对面反蓝的时候去支援等等，辅助是很灵活的位置，但是一个死脑筋的人理解范畴就是辅助只有跟了射手才是辅助。）

## 架构演变
技术的演变过程都是朝着分离代码发展。

最早的 PHP 文件前后端代码混合在一起，相信大家最开始学 PHP 的时候都接触过这种代码：

```
// index.php 文件

<html>
// ...此处省略998行
<h1><?php echo "hello world!" ?></h1>
</html>
```

这种文件又被归为：`.phtml` 格式，意思是 PHP 代码与 HTML 混合的文件。

因为 PHP 代码可以嵌入在 HTML 文件中，所以 PHP 又被叫做嵌入式语言。

可以嵌入其他语言感觉很 Cool，但其实这种做法是非常不可取的，试想一下公司忽然来了一个不懂 HTML 的 PHP 实习生，看着混合着 HTML 和 PHP 代码的文件肯定一脸懵逼。

为了维护混合代码的项目需要掌握前端知识，后端开发无形中增加了学习成本。

> 重点知识：HTML 代码与 PHP 代码混合在一起

也就是上面提到的“出现不应该出现的东西”，HTML 代码应该只包含 HTML、JS、CSS 之类的前端方面文件，而用来处理业务逻辑的 PHP 文件里也不应该出现前端的东西。只要出现了本来不应该出现的东西就会导致维护成本增加。

## 传统 MVC 模式
首先介绍一下传统 MVC 模式，

这是早期用来处理代码文件膨胀的解决方案：

将代码文件分成：

- Model（模型）处理数据库操作
- View（视图）渲染 HTML 文件
- Controller（控制器）处理请求和响应

在一定程度上满足了早期的需求，模型文件专门处理数据库的增删改查，而控制器只要实例化模型对象就可以直接调用数据库操作方法，然后把数据传递给视图进行渲染。

每一步都变得更加专业化了。

> 模板视图：包括 PHP 代码与 HTML 代码的特殊文件

但 MVC 结构依然没有摆脱代码混合的问题，而是使用了稍微好一些的模板视图（例如：smart 模板、blade 模板）。

模板视图就是用来处理前端和 PHP 代码，用来最终渲染出 HTML 页面的文件。

以 Laravel 使用的 blade 模板为例：

```
// IndexController.php
class IndexController {
    public function index()
    {
        // 查询数据库，取 ID=1 的 goods
        $goods = Goods::where('id', 1)->first();
        
        return view('index', compact('goods'));
    }
}

// index.blade.php

<html>
// ... 省略998行
{{ $goods->name }}
</html>
```

通过模板引擎编译后生成如下文件：

```
<html>
// ... 省略998行
<?php echo $goods->name; ?>
</html>
```

也就是说我们现在不需要直接在 HTML 上面写代码，而是在模板文件（index.blade.php）上面写代码。

但这种方法治标不治本，虽然模板文件是 PHP 文件，但还是夹杂着 HTML。

## 前后端分离
为了分离出 HTML 和 PHP 代码，人们研究出了通过接口调用实现前后端分离的方法。

这种技术叫做 Ajax，前端通过 JavaScript 发起 HTTP 请求，服务器返回查询的数据，前端再渲染数据。

整个过程实现了前后端代码的完全分离。

> 现在我们后端开发人员已经从前端地狱中解放出来了！！

## Service 模式
因为前后端分离，MVC 模式的 View（视图）已经不需要了，

取而代之的是新出现的 Service（服务）。

MVC 模式下，所有的业务逻辑都写在 Controller（控制器）上。

示例代码：

```
class GoodsController
{
    public function index() {
        // 按发布时间倒序获取20条商品数据
        $goods = Goods::orderByDesc('created_at')->limit(20)->get();
        // 拼装接口返回数据
        $items = [];
        foreach($goods as $item) {
            $items['data'][] = [
                'id' => $item->id,
                'title' => $item->title,
                'time' => $item->created_at
            ];
        }
        
        // 返回接口响应
        return api_response($items);
    }
}
```

把所有业务逻辑代码都写在控制器上会导致控制器文件逐渐膨胀。

控制器的作用是处理请求和响应，我们可以让它更加专业化一点，让它只负责处理请求然后给出响应。

至于逻辑处理，可以交给第三方：Service（服务）来处理。

所谓 Service 层只不过是再额外增加一个文件。

示例：

```
class GoodsService {
    
    // 按发布时间倒序获取20条商品数据
    public function getGoodsItems() {
       $goods = Goods::orderByDesc('created_at')->limit(20)->get();
       // 拼装接口返回数据
       $items = [];
       foreach($goods as $item) {
           $items['data'][] = [
               'id' => $item->id,
               'title' => $item->title,
               'time' => $item->created_at
           ];
       }
       
       return $goods;
    }
}
```

然后在控制器里只需要实例化出一个 GoodsService 对象，调用方法即可：

```
class GoodsController
{
    public function index() {
        $service = new GoodsService();
        $items = $service->getGoodsItems();
        
        // 返回接口响应
        return api_response($items);
    }
}
```

控制器里面只负责处理请求和给出响应，把逻辑处理交给 Service 处理，这样 Controller 的代码几乎已经没有多少了，以后某个接口出问题可以立即找到对应的控制器方法，大大减少排查问题的时间。

（把业务逻辑写在控制器里是一个非常让人吐血的事情，作为接盘了不少项目的我深有体会）

## 更多的划分方法
机智的你一定发现，即使增加了 Service 层，业务逻辑代码还是很多啊，把本来囤积在 Controller 的代码移到 Service 有什么意义？

意义之一就是让每个结构更加专业化，Controller 只处理请求和响应，因为 Controller 如果有太多代码的话，如果一个接口出问题了，你要在 IDE 上面拖动滚轮才能定位到你想要的那个方法上面，然后直接在 Controller 层排查问题。而且 Controller 层如果囤积了太多业务代码，还有其他小伙伴也在修改这个控制器就很容易发生代码冲突造成不必要的麻烦。

Goods（商品）的相关业务逻辑就交给 GoodsService 处理；User（用户）的业务逻辑就交给 UserService 处理，这样你要修改哪个模块的业务逻辑也很方便不是吗？

但还是没有解决 Service 层代码囤积问题。

这里凭经验之谈，其中一个方法就是数据库操作的逻辑移动到 Model 层里面去处理，示例：

```
class UserModel extends Model {
    // 添加一名新用户
    public function addUser($username) {
        // user 表增加一条新用户记录
        $user = $this->create([
            'username' => $username,
        ]);
        
        // 为用户开通支付账户
        UserAccountModel::create([
            'user_id' => $user->id
        ]);
        
        // ... 其他数据库相关操作
        
        // 返回新创建的 user 对象
        return $user;
    }
}
```

然后 UserService 直接调用 Model 的方法：

```
$data = request()->all();
$model  = new UserModel();
$user = $model->addUser($data);
// ... 处理其他逻辑
```

这样 Service 层的代码一部分就转移到了 Model 层里去了！

把所有数据库增删改查的逻辑全部转移到 Model 层，

而 Service 只要实例化对应的 Model，调用方法（负责处理整体的逻辑）。

这样 Service 层也可以变得更加专业化。

> Model：喵喵喵？把锅甩给我！？

新的问题又来了，Model 层膨胀了！

OK，用同样的逻辑，我们再分出一个 Action（操作）层。

新建 Action 类，每一个操作对应一个类文件：

```
class UserAddAction {

    // 创建新用户
    public function add($data) {
        $model = new UserModel();
        $user = $model->create($data);
        
        return $user;
    }
}

class UserAccountAddAction {

    // 开通用户支付账户
    public function add(UserModel $user) {
        $model = new UserAccountModel();
        $data = [
            'user_id' => $user->id,
            // ... 省略其他数据
        ];
        $account = $model->create($data);
        
        return $account;
     }
}
```

接下来在 Model 里面只要实例化 Action，然后调用方法就可以了：

```
class UserModel extends Model {
    // 添加一名新用户
    public function addUser($username) {
        // user 表增加一条新用户记录
        $userAddAction = new UserAddAction();
        $data = [
            'username' => $username,
            // .. 其他数据
        ];
        $user = $userAddAction->add($data);
        
        // 为用户开通支付账户
        $userAccountAddAction = new UserAccountAddAction();
        $data = [
            'user_id' => $user->id,
            // .. 其他数据
        ];
        $account = $userAccountAddAction->add($data);
        
        // ... 其他数据库相关操作
        
        // 返回新创建的 user 对象
        return $user;
    }
}
```

每一个 Action 只对应一个方法，这样 Action 就不会膨胀了。

业务调用关系如下：

> Controller -> Service -> Model -> Action

但代码拆分的缺点就是会让文件变得越来越多，每一个数据库操作就要写一个 Action，以后可能会出现上千个文件，而我们知道 PHP 程序运行时需要加载全部的文件，无疑会降低性能。

代码变得更好维护的成本是降低性能，代价未免太大了。

所以优化也要适度，回到标题，如何选择是要看情况进行灵活变通的。

## 微服务架构
微服务架构同样是为了拆分代码，

把一套系统的各个功能模块拆分成独立的模块，

每一个小模块都是一个新的项目。

例如一套系统有用户模块、商品模块、订单模块，

主项目负责处理请求，然后调用相应的模块，再将数据返回。

主服务器的作用类似于 Controller 层，

只负责处理请求和响应，但具体的实现都交给 Service（子模块）去处理。

把每一个功能都单独立项可以使项目更加专一化，原本一个项目包括了所有模块。

> “单体应用”模式

单体应用只需要部署一套项目，项目里就已经包含了完整的模块。

所有开发人员都在一个项目上进行开发工作。

单体应用的缺点是随着业务增加会越来越难以维护（因为所有的代码都集中在一个地方）。

而所有人都在共同维护一套代码，每个人习惯不同，技术水平也不同，

代码质量参差不齐，新加入的小伙伴也不敢轻易修改老员工的代码。

（有时候虽然知道老员工写了一个可以直接调用的方法，但新员工依然会产生不信任感，总归不是自己写的代码，万一被原主人改了怎么办？完全不能放心调用。）

> 单体应用排查 BUG 困难

有时候新来的小伙伴为了找一个 BUG 可能要遍历用户模块、订单模块、商品模块……

几乎把所有的逻辑代码查了个遍。如果把每个模块都单独拆成小项目交给一个小团队去维护，大家各扫门前雪，用户模块出问题了就让负责用户模块的人去处理，这样大家互不影响，不会出现你删了别人代码，导致别人一脸懵逼的排查了一整天都没找到原因。

项目拆分成小项目，每个项目的运行速度就会变得更快，大大提高了系统性能。

> 理想很美好！现实很残酷……

到公司你还是要当打杂的角色，不可能让一个人只负责一个项目，

往往就是用了微服务架构，你一个人要负责全部的项目（工作量反而增加了）。

> 不是技术负责人要压榨劳动力……而是他要为项目负责任。

如果他招来的人跑路了，那负责这个模块的功能就没人维护了，所以当你加入微服务架构里，每个团队成员至少也要负责 2 个以上的项目，万一其中一个人跑路还有另一个可以顶替。

项目变多，单个项目维护成本降低，

但总体维护成本也会提高，尤其是架构师跑路，谁来接盘就是一个严重问题。

回到最开始的地方，我们最终还是没能解决接盘侠的问题……

总之，接盘侠是无可避免的要痛苦的。