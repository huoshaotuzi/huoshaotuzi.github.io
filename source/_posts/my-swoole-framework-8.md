---
title: 从零开始搭建自己的Swoole框架（八）路由中间件

date: 2021-02-12 14:57:33
tags:
 - PHP
 - Swoole
 - FireRabbitEngine

categories: 架构

description: 通过 array_reduce 方法实现路由中间件的功能。

---
## 中间件的概念
中间件就是一种系统之间互相连接的“中间的一层”。

通俗的讲类似古代的关口，西游记里唐僧每到一个国家都要取得这个国家的“通关文凭”，如果没有通关文凭就无法离开国界。边关的守卫就可以理解为“中间件”，唐僧就是请求，如果没有通过文凭（即达不到某种要求）就会被拦截在关口。

也就是说，中间件的主要功能是“拦截不符合规范的请求”。

它就是一种 `if-else` 条件判断结构，如果……就……

比如要设计一个活动页面，只有今天晚上 9：00 到 10：00 这个时间段才会进入活动页，如果还不到 9 点就打开这个页面就会显示“活动还未开始”，如果是 10 点之后打开这个页面，就会显示“活动已结束”。

要实现这种功能十分简单，直接用 `if-else` 结构就可以了。

但是这种思想属于面向过程，在框架里可以将判断条件封装为“中间件”实现自动化处理请求，满足要求的就放过，不满足要求的就拦截下来，返回失败的处理。

## 中间件的应用场景
中间件即拦截不符合规范的请求，因此它能用的场景非常多。

例如规定了某个时间段开放、关闭的活动页面；

表单验证、用户登录状态验证等等。

总之，凡是能用“如果……就……”描述的，几乎都可以用中间件实现，因为它本身即是一种条件判断结构。

## Laravel 中的中间件
Laravel 中的中间件的使用非常优雅！

创建一个中间件，用于验证用户是否登录：

```
<?php

namespace App\Http\Middleware;

use Closure;

class AuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 判断用户是否登录状态，如果已登录则进入下一步
        if (auth()->check()) {
            return $next($request);
        }

        // 如果未登录则返回提示页面的视图
        $message = '用户未登录，无法操作，<a href="#">前往登录</a>。';
        return $this->showErrorPage($message);
    }
    
    public functio showErrorPage($message) {
        // ... 返回自定义视图页面
    }
}

```

然后在 Kernel.php 中注册中间件，并且命名：

```
protected $routeMiddleware = [
    'auth.check' => AuthCheck::class,
];
```

最后只要在路由配置中为需要验证用户身份的路由加上中间件即可：

```
$router->middleware('auth.check')->get('/user', 'UserController@index')->name('user.index');
```

只需如此简单的配置即可实现路由拦截。

## Laravel 中间件的原理
一个路由可以有很多中间件，只有满足所有中间件才让请求继续下去，否则就终端请求返回错误的结果。

看起来只需要一个 foreach 循环就能实现中间件了，用伪代码实现思路如下：

```
$flag = true;

$conditions = [条件1, 条件2, 条件3];

foreach ($conditions as $condition) {
    if($condition == false) {
        $flag = false;
        break;
    }
}

if ($flag == true) {
    // 成功，进入下一步
} else {
    // 失败，返回失败页
}
```

好像确实可以，但我出于好奇研究了一下 Laravel 的源码。

### Laravel 源码
在定义 Middleware 类的时候，我发现 Middleware 不需要继承任何框架的基类：

```
<?php

namespace App\Http\Middleware;

use Closure;

class AuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 判断用户是否登录状态，如果已登录则进入下一步
        if (auth()->check()) {
            return $next($request);
        }

        // 如果未登录则返回提示页面的视图
        $message = '用户未登录，无法操作，<a href="#">前往登录</a>。';
        return $this->showErrorPage($message);
    }
    
    public functio showErrorPage($message) {
        // ... 返回自定义视图页面
    }
}
```

只是定义一个 handle 方法，一共接收两个参数，

一个是 Laravel 的 $request，另一个是闭包类型 $next。

如果请求验证成功，则直接返回闭包执行结果 `$next($request)`，

如果请求不符合要求，就自定义一个响应返回。

看来，玄机并不在 Middleware 的定义里。

基于 php-fpm 的框架入口文件基本上都是 index.php，

因此找到 Laravel 的入口文件在 public 目录下面，index.php 的内容如下：

```
<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);

```

这里引入了一个文件，然后得到一个 $app 对象，接着调用 handle 方法执行响应事件，

然后就没有其他代码了，因此这个引入的 app.php 是关键所在：

```
$app = require_once __DIR__.'/../bootstrap/app.php';
```

找到 app.php 发现如下内容：

```
<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
```

这里是注册服务容器的地方，服务容器 Laravel 实例化类的一种设计模式，

具体的原理我也没有搞懂，只要知道这是一个“注册和实例化类”的地方就可以了。

跟 HTTP 请求相关的部分：

```
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
```

按住 Ctrl 点击 `App\Http\Kernel::class` 可以跳转到类定义的地方，

结果发现跳转到中间件配置的地方了：

```
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
//            'throttle:60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    ];
}
```

看来离想要找到的结果不远了，这个配置也没什么奇怪的地方，

接着发现这个类继承了另一个类：

```
use Illuminate\Foundation\Http\Kernel as HttpKernel;
```

于是我们继续前往这个类，发现这个类有很多方法，

我就直接截取关键部分了：

```
/**
 * Handle an incoming HTTP request.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function handle($request)
{
    try {
        $request->enableHttpMethodParameterOverride();

        $response = $this->sendRequestThroughRouter($request);
    } catch (Throwable $e) {
        $this->reportException($e);

        $response = $this->renderException($request, $e);
    }

    $this->app['events']->dispatch(
        new RequestHandled($request, $response)
    );

    return $response;
}
```

handle 方法？也就是说最开始入口文件执行的便是这个方法了。

根据注释：Handle an incoming HTTP request

可以知道这里确实是处理进来请求的地方。

第一行执行的方法：`enableHttpMethodParameterOverride`，即 Laravel 重写请求方法的地方，

在 Laravel 除了 GET 和 POST 之外，还定义了 PUT、DELETE 等方法，

这里就是判断 `_method` 变量生成特殊请求方法的地方。

接着查看 `sendRequestThroughRouter`：

```
/**
 * Send the given request through the middleware / router.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
protected function sendRequestThroughRouter($request)
{
    $this->app->instance('request', $request);

    Facade::clearResolvedInstance('request');

    $this->bootstrap();

    return (new Pipeline($this->app))
                ->send($request)
                ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                ->then($this->dispatchToRouter());
}
```

这里的代码应该就是我想要找的了，

```
return (new Pipeline($this->app))
                ->send($request)
                ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                ->then($this->dispatchToRouter());
```

send 方法非常简单：

```
 /**
 * Set the object being sent through the pipeline.
 *
 * @param  mixed  $passable
 * @return $this
 */
public function send($passable)
{
    $this->passable = $passable;

    return $this;
}
```

并不是处理中间件的逻辑，接着看 through：

```
/**
 * Set the array of pipes.
 *
 * @param  array|mixed  $pipes
 * @return $this
 */
public function through($pipes)
{
    $this->pipes = is_array($pipes) ? $pipes : func_get_args();

    return $this;
}
```

也不是，最后的 then：

```
/**
 * Run the pipeline with a final destination callback.
 *
 * @param  \Closure  $destination
 * @return mixed
 */
public function then(Closure $destination)
{
    $pipeline = array_reduce(
        array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
    );

    return $pipeline($this->passable);
}
```

也只有短短数行的代码，难道最后也没找到中间件的实现逻辑？

而且……这个 `array_reduce` 是什么鬼？

仔细的研究了一番，发现这里的代码虽然只有 4 行，可真的不简单！

其中，最关键的部分是这个叫做 carry 的方法：

```
/**
 * Get a Closure that represents a slice of the application onion.
 *
 * @return \Closure
 */
protected function carry()
{
    return function ($stack, $pipe) {
        return function ($passable) use ($stack, $pipe) {
            try {
                if (is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe($passable, $stack);
                } elseif (! is_object($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                $carry = method_exists($pipe, $this->method)
                                ? $pipe->{$this->method}(...$parameters)
                                : $pipe(...$parameters);

                return $this->handleCarry($carry);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    };
}
```

### array_reduce
如果猜的没错，Laravel 应该就是使用 `array_reduce` 来实现中间件的。

查了一下 PHP 的官方文档，它对 `array_reduce` 的描述是：

```
array_reduce — 用回调函数迭代地将数组简化为单一的值
```

嗯……不愧是官方文档，说了跟没讲一样。

还是通过实战来了解一下什么是 `array_reduce`：

```
$params = ['a', 'b', 'c'];
$result = array_reduce($params, function ($carry, $item) {

    var_dump('carry=' . $carry);
    var_dump('item=' . $item);

    return $carry . $item;
});

var_dump($result);
```

声明一个数组 $params 且包含三个字符串，

然后通过 array_reduce 传入数组参数，同时还有一个闭包，

闭包接收两个参数 $carry, $item，然后试着打印这两个参数以及最终结果：

```
string(6) "carry="
string(6) "item=a"
string(7) "carry=a"
string(6) "item=b"
string(8) "carry=ab"
string(6) "item=c"
string(3) "abc"
```

也就是说，一开始 $carry 的值是空的（Null），然后随着循环，

$carry 会逐渐合并数组的每一个元素。

`array_reduce` 可以说是如下代码构成的：

```
$params = ['a', 'b', 'c'];
$result = null;

foreach ($params as $param) {
    $result .= $param;
}

var_dump($result);
```

循环遍历数组的每一个元素，然后保持一个不变的值。

与官方文档的描述对应起来了！**将数组简化为一个单一的值。**

也就是说通过 `array_reduce` 最终会返回一个值作为处理的结果。

`array_reduce` 可以接收第三个参数，即初始值：

```
$params = ['a', 'b', 'c'];
$result = array_reduce($params, function ($carry, $item) {
    return $carry . $item;
}, 'init');

var_dump($result);
```

最终会输出：`initabc`

如果不设置第三个参数，那么初始值就会默认为 Null。

最开始，我以为会是返回 true 或者 false 来判定中间件的执行结果，

但是 Laravel 的设计却令人惊叹！

```
/**
 * Get a Closure that represents a slice of the application onion.
 *
 * @return \Closure
 */
protected function carry()
{
    return function ($stack, $pipe) {
        return function ($passable) use ($stack, $pipe) {
            try {
                if (is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe($passable, $stack);
                } elseif (! is_object($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                $carry = method_exists($pipe, $this->method)
                                ? $pipe->{$this->method}(...$parameters)
                                : $pipe(...$parameters);

                return $this->handleCarry($carry);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    };
}
```

上面的代码十分生涩难懂，简直如同“天书”，

因此我自己尝试实现相同的逻辑并且让代码变成“说人话”。

### 对暗号游戏
接下来我开始参考着 Laravel 中间件的代码实现一个“对暗号”的“游戏”，

比如在一个军营里，一共有 A、B、C 三个巡逻队，

为了避免整个暗号泄露出去，规定每一个巡逻队都只持有暗号的其中一句，

今晚的暗号是：“上山打老虎”，

那么三个巡逻队分别得到的暗号是：

A：上山

B：打

C：老虎

而你半夜出去嘘嘘，刚好被巡逻队给碰上了……

于是，你必须说出你的口令，否则就会被当做奸细就地正法……

三只巡逻队可以抽象成“巡逻队”概念，即定义一个 Middleware 作为父类，

他们都有核对口号的方法 handle，以及自己的密令：

```
abstract class Middleware
{
    public $keyword;

    public function handle($value, Closure $closure)
    {
        var_dump('暗号：' . $this->keyword);

        // 包含指定关键词的口令视为核对成功
        if (strstr($value, $this->keyword) != false) {
            return $closure($value);
        }

        return '口令核对失败';
    }
}
```

接着创建三个巡逻队，继承基类并且拥有独立的口令：

```

class Middleware_A extends Middleware
{
    public $keyword = '上山';
}

class Middleware_B extends Middleware
{
    public $keyword = '打';
}

class Middleware_C extends Middleware
{
    public $keyword = '老虎';
}
```

接下来实现具体的逻辑，声明一个包含 N 只巡逻队的数组（可以是 0-3 个）：

```
function middlewares()
{
    $params = ['Middleware_A', 'Middleware_B', 'Middleware_C'];
    $params = array_reverse($params);

    return $params;
}
```

这里增加了一个 `array_reverse` 将数组反转的方法，下文会解释。

然后实现核对密令的逻辑，比如你遇到巡逻队 A，那就核对巡逻队 A 的密令，

如果同时遇到两只巡逻队，A+B 或者 A+C 或者 B+C，那就应该核对两个巡逻队的密令，

如果你非常不幸的同时遇到三只巡逻队，那就要核对 ABC 的密令：

```
function carry()
{
    return function ($stack, $pipe) {
        return function ($passable) use ($stack, $pipe) {

            if ($pipe instanceof Closure) {
                return $pipe($passable, $stack);
            } elseif (!is_object($pipe)) {
                $pipe = new $pipe;
            }

            return $pipe->handle($passable, $stack);
        };
    };
}

function init()
{
    return function ($destination) {

        var_dump($destination);

        return 'ok';
    };
}


$response = array_reduce(middlewares(), carry(), init());

var_dump($response('小鸡炖蘑菇'));
var_dump($response('上山打野鸡'));
var_dump($response('上山打老虎'));
```

上面的代码虽然很短，但是要理解起来非常不易。

`array_reduce` 可以接收三个参数：

第一个参数是数组，即要遍历的数组；

第二个参数是一个方法/闭包（匿名函数），即执行遍历的逻辑；

第三个参数是初始值。

初始值是最终想要实现的结果，当满足所有条件后，就会返回初始值函数里的代码。

而 `middlewares` 是最开始定义巡逻队的地方，很不幸你同时遇到三只巡逻队：

```
function middlewares()
{
    $params = ['Middleware_A', 'Middleware_B', 'Middleware_C'];
    $params = array_reverse($params);

    return $params;
}
```

`array_reverse` 这里的反转数组与接下来的堆栈调用有关，

栈结构是先进后出，会导致乱序，我们希望的结果是按照 A、B、C 的顺序执行。

`init` 方法定义了最终希望输出的值，如果满足所有条件的话，就返回这个值。

```
function init()
{
    return function ($destination) {

        var_dump($destination);

        return 'ok';
    };
}
```

`carry` 是整个逻辑最关键的部分：

```
function carry()
{
    return function ($stack, $pipe) {
        return function ($passable) use ($stack, $pipe) {

            if ($pipe instanceof Closure) {
                return $pipe($passable, $stack);
            } elseif (!is_object($pipe)) {
                $pipe = new $pipe;
            }

            return $pipe->handle($passable, $stack);
        };
    };
}
```

这个方法涉及了闭包的递归调用，**最终的返回结果依然是一个闭包。**

carry 方法传入两个参数 $stack, 

$stack 即遍历过程中持续引用的值，而 $pipe 则是当前元素。

回忆一下上面的代码：

```
$params = ['a', 'b', 'c'];
$result = array_reduce($params, function ($carry, $item) {
    return $carry . $item;
}, 'init');

var_dump($result);
```

应该不难理解，这里就是循环遍历一个数组，依次取值进行计算，最终返回一个结果而已。

接下来分析代码：

```
return function ($stack, $pipe) {
    return function ($passable) use ($stack, $pipe) {

        if ($pipe instanceof Closure) {
            return $pipe($passable, $stack);
        } elseif (!is_object($pipe)) {
            $pipe = new $pipe;
        }

        return $pipe->handle($passable, $stack);
    };
};
```

carry 返回一个闭包，同时它内层的代码也是返回一个闭包，并且接收一个 $passable 作为参数。

> $passable 的作用就是递归函数中不断传给下一次调用的值

在最内层，是一个条件判断语句：

```
if ($pipe instanceof Closure) {
    return $pipe($passable, $stack);
} elseif (!is_object($pipe)) {
    $pipe = new $pipe;
}

return $pipe->handle($passable, $stack);
```

如果传来的值不是 Closure（闭包类型），则判断它是否是一个对象，

如果不是对象则根据这个元素的名字实例化出对象来：

```
if (!is_object($pipe)) {
    $pipe = new $pipe;
}
```

最终调用实例化对象的 handle 方法，并且把 $passable 和 持续保留的那个值 $stack 传给 handle。

再看一次执行的逻辑，并且模拟每一次执行的结果：

```
return function ($stack, $pipe) {
    return function ($passable) use ($stack, $pipe) {

        if ($pipe instanceof Closure) {
            return $pipe($passable, $stack);
        } elseif (!is_object($pipe)) {
            $pipe = new $pipe;
        }

        return $pipe->handle($passable, $stack);
    };
};
```

假设调用方法：

```
// array_reduce 返回的是一个闭包，可以当做函数调用
$response = array_reduce(middlewares(), carry(), init());

// 传入一个用来验证的口令
$result = $response('上山打野鸡');

// 打印出验证结果
var_dump($result);
```

第一次遍历：

通过 init 方法赋值：

```
// 第一步：$stack 赋值，init 方法也是一个闭包，接收一个 $destination 参数
// $stack 的初始值即 init 方法返回的闭包，所以是：

$stack = function ($destination) {
     var_dump($destination);
     
     return 'ok';
 };

// 接着，取出数组的第一个元素
$pipe = 'Middleware_A';

// 第二步，进入闭包：function ($passable) use ($stack, $pipe)
// 这里的 $passable 就是上面调用时传入的值：“上山打野鸡”
// 执行判断语句

if ($pipe instanceof Closure) {
    return $pipe($passable, $stack);
} elseif (!is_object($pipe)) {
    $pipe = new $pipe;
}

// 很明显 $pipe 此时只是一个字符串，因此不满足 $pipe instanceof Closure
// 于是进入 else 条件 !is_object($pipe) 它并不是一个对象，因此满足此条件
// 所以将 $pipe = new $pipe; 实例化成对象
// 此处的代码即：$pipe = new Middleware_A();
// 实例化出巡逻队A的对象，然后调用他的 handle 方法并返回

return $pipe->handle($passable, $stack);

// Middleware_A 继承了父类 Middleware，因此 handle 为：

public function handle($value, Closure $closure)
{
    var_dump('暗号：' . $this->keyword);

    // 包含指定关键词的口令视为核对成功
    if (strstr($value, $this->keyword) != false) {
        return $closure($value);
    }

    return '口令核对失败';
}

// 此时 value 的值为：上山打野鸡，巡逻队A的暗号是：上山
// 因此巡逻队A验证成功，将这个值传给闭包然后返回

return $closure($value);

// 接收到参数的 $closure 就是 $stack，也就是我们最开始定义的 init 方法

```

第二次遍历：

```
// 此时已经不会经过 init 初始化了，
// $stack的值是第一步返回的 return $pipe->handle($passable, $stack);
// 也就是说init方法

// 第二次遍历$pipe就会取第二个巡逻队：Middleware_B
// 继续进入 function ($passable) use ($stack, $pipe) 执行判断语句

if ($pipe instanceof Closure) {
    return $pipe($passable, $stack);
} elseif (!is_object($pipe)) {
    $pipe = new $pipe;
}

// 同样Middleware_B只是一个字符串，因此会被实例化成类
// 然后与第一步一样，进行比对暗号，Middleware_B的暗号是：打
// 因此：上山打野鸡包含了这个字符，就符合巡逻队B的暗号
// 又经过父类的方法：

public function handle($value, Closure $closure)
{
    var_dump('暗号：' . $this->keyword);

    // 包含指定关键词的口令视为核对成功
    if (strstr($value, $this->keyword) != false) {
        return $closure($value);
    }

    return '口令核对失败';
}

// init 方法继续被传递给下一个执行的对象

```

第三步，也就是最后的一个巡逻队了，这里产生了一个分歧点，即最后一个暗号不符合要求：

```
// 第三次遍历$pipe就会取第三个巡逻队：Middleware_C
// 继续进入 function ($passable) use ($stack, $pipe) 执行判断语句

if ($pipe instanceof Closure) {
    return $pipe($passable, $stack);
} elseif (!is_object($pipe)) {
    $pipe = new $pipe;
}

// 一样是实例化的过程，然后分歧点出现了
// 第三个巡逻队的暗号是：老虎，而此时给出的却是：上山打野鸡
// 不包括“老虎”两个字

public function handle($value, Closure $closure)
{
    var_dump('暗号：' . $this->keyword);

    // 包含指定关键词的口令视为核对成功
    if (strstr($value, $this->keyword) != false) {
        return $closure($value);
    }

    return '口令核对失败';
}

// 不符合结果就直接返回了一个字符串“口令核对失败”
// 这个返回的值会被当做$stack的值
// 最后就跟递归函数一样层层返回，将“口令核对失败”作为array_reduce将数组简化的唯一值

// 也就是说，$response('上山打野鸡') 最后返回的是“口令核对失败”

// array_reduce 返回的是一个闭包，可以当做函数调用
$response = array_reduce(middlewares(), carry(), init());

// 传入一个用来验证的口令
$result = $response('上山打野鸡');

// 打印出验证结果
var_dump($result);
```

如果是传入正确的口令：上山打老虎呢？

```
// 巡逻队C核对口令正确，就会继续把参数传给闭包
public function handle($value, Closure $closure)
{
    var_dump('暗号：' . $this->keyword);

    // 包含指定关键词的口令视为核对成功
    if (strstr($value, $this->keyword) != false) {
        return $closure($value);
    }

    return '口令核对失败';
}

// 此时三个巡逻队已经遍历完了，还记得一直传下来的$stack的值是什么吗？
// 答案是：init
// 你可以重新返回去查看第一步到第三步，只要是验证口令成功的时候，
// init 方法都会被当做下一个闭包传递下去，init 闭包即 $stack 的值
// 所以最终返回的 $stack 即 init 方法
```

> 注意！上面的 array_reduce 执行完毕后并不是真的执行了代码，而是返回一个层层嵌套的递归函数（闭包），只有在调用的时候才会一层一层的执行，因而最先调用的中间件反而会变成最后执行（栈结构先进后出），所以我们才会在最开始反转数组，以保证执行顺序。

至此，Laravel 中间件验证路由请求的原理也就搞清楚了。

捋顺之后只剩下久久的深思，一段简单的代码却蕴藏着如此精深的奥妙。

可是……写完了如此长篇的文章，我的框架的中间件却还没有开始着手……

## 为框架添加中间件
### 中间件原理
中间件其实跟路由的原理类似，即创建一个专门保存命名和映射关系的配置文件：

```
// 键值对数组的键即中间件名称，值即对应的中间件
'auth' => 'App\\Middleware\\AuthMiddleware'
```

只需要用一个简单的名称字符串即可映射到对应的中间件类。

由于一个路由可以有很多个中间件，所以路由配置里需要添加一个数组用来存储中间件的名称。

### 优化路由模块
在之前的设计中，Router 的 $routes 设计为静态变量，

其实只要修改 http_server.php 修改引入方式即可：

```
<?php
require './vendor/autoload.php';
require_once './app/route/web.php';

$http = new Swoole\Http\Server('0.0.0.0', 9527);

$http->on('request', function ($request, $response) use ($router) {

    var_dump('请求URI：' . $request->server['request_uri']);

    $router->handle($request, $response);
});

$http->start();
```

而路由配置文件 web.php 只要返回 $router 即可：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Controller\\Home\\',

])->group(function () use ($router) {

    $router->get('/user', 'IndexController@index')->name('index');
    
});

return $router;
```

这样 Router 的 $routes 就不再需要设置为静态变量了。

### 路由添加中间件
在路由配置的时候，期望效果是可以通过如下两种方式配置中间件：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Controller\\Home\\',
    'middleware' => ['auth']

])->group(function () use ($router) {

    $router->get('/user', 'IndexController@index')->name('index')->middleware(['auth']);
    
});

return $router;
```

第一种是在分组的时候，配置全组共用的中间件，

第二种是在单个路由配置的时候，可以自定义该路由的中间件，

如果使用第二种方法，并且该路由在一个分组里，该路由不仅有分组的中间件，还有自己单独添加的中间件。

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Controller\\Home\\',
    'middleware' => ['auth']

])->group(function () use ($router) {

    // 这个路由的中间件为：[auth, other]
    $router->get('/user', 'IndexController@index')->middleware(['other']);

    // 这个路由的中间件为：[auth]
    $router->get('/admin', 'IndexController@index');
    
});

return $router;
```

上面的 /user 路由额外添加了一个中间件 other，而 /admin 路由不会受到影响。

中间件的合并顺序为：分组>自定义

即优先执行分组设置的全局中间件，然后再执行自定义中间件。

middleware 方法必须放在 get/post/any 方法之后。

修改 name 方法，让该方法也返回 $this，这样就可以链式调用了。

然后为 Router 添加 middleware 方法，该方法接收一个数组参数：

```
// 新增属性(全局中间件)
protected $middlewares = [];

/**
* 路由添加中间件
*
* @param array $middlewares
* @return Router
*/
public function middleware(array $middlewares)
{
    if ($this->lastHandleRouteIndex === null) {
        return $this;
    }

    // 合并中间件，优先级为：分组>单个路由自定义配置
    $middlewares = array_merge($this->middlewares, $middlewares);
    // 去除重复中间件
    $middlewares = array_unique($middlewares);
    // 找到最后一个添加的路由
    $route = $this->routes[$this->lastHandleRouteIndex];
    $route->middleware = $middlewares;

    $this->routes[$this->lastHandleRouteIndex] = $route;
}
```

然后是分组的配置：

```
/**
* 设置参数
* @param $key
* @param $value
*/
protected function createConfig($key, $value)
{
    switch ($key) {
        case 'namespace':
            $this->namespace = $value;
            break;
        case 'middleware':
            $this->middlewares = $value;
            break;
    }
}
```

分组配置时将中间件加入全局的中间件数组。

在调用结束的时候，应该把这个数组清空：

```
/**
* 路由分组
* @param $func
*/
public function group($func)
{
    $func();

    // 执行完成后将参数初始化
    $this->namespace = '';
    $this->middlewares = [];
}
```

给路由增加中间件的功能就完成了。

现在 RouteParams 路由配置对象里已经可以取到 middleware 属性的值了。

### 封装请求与响应
框架的请求和响应是 swoole 的对象，内置的方法无法满足框架的需求，

因此需要将请求和响应进行封装，在框架的 module 目录新建文件夹 Http，

Http 模块用于实现 Http 请求相关的处理类，新建两个类：Request 和 Response 用于封装请求和响应：

```
# Request.php

<?php

namespace FireRabbitEngine\Module\Http;

use FireRabbitEngine\Module\Route\RouteParams;

class Request
{
    protected $request, $route;

    public function __construct($request, $route)
    {
        $this->request = $request;
        $this->route = $route;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * 获取路由
     * @return mixed
     */
    public function getRoute(): RouteParams
    {
        return $this->route;
    }

    /**
     * 判断该请求是否ajax
     * @return bool
     */
    public function isAjax()
    {
        return 'XMLHttpRequest' == $this->request->header['x-requested-with'];
    }

    /**
     * 获取get参数
     * @param null $key
     * @param null $default
     * @return mixed
     */
    public function getQueryParams($key = null, $default = null)
    {
        if ($key == null) {
            return $this->request->get;
        }

        return isset($this->request->get[$key]) ? $this->request->get[$key] : $default;
    }

    /**
     * 获取post参数
     * @param null $key
     * @param null $default
     * @return mixed
     */
    public function getPostParams($key = null, $default = null)
    {
        if ($key == null) {
            return $this->request->post;
        }

        return isset($this->request->post[$key]) ? $this->request->post[$key] : $default;
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->request->server['request_method'];
    }

    /**
     * 获取请求IP地址
     * @return string | null
     */
    public function getRequestIP()
    {
        return $this->request->header['x-real-ip'] ?? null;
    }

    /**
     * 获取请求头
     * @param $key
     * @return string | null
     */
    public function getHeaders($key = null)
    {
        if ($key == null) {
            return $this->request->header;
        }

        return $this->request->header[$key] ?? null;
    }

    /**
     * 获取cookie
     * @param $key
     * @return string | null
     */
    public function getCookies($key = null)
    {
        if ($key == null) {
            return $this->request->cookie;
        }

        return $this->request->cookie[$key] ?? null;
    }

    /**
     * 获取请求URI
     * @return mixed
     */
    public function getRequestURI()
    {
        return rtrim($this->request->server['request_uri'], '/');
    }
}
```

Request 请求类实现了一些简单方法的封装，后续如有需求还可以继续扩展。

接下来创建 Response 响应类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/12/21
 * Time：11:31 AM
 **/


namespace FireRabbitEngine\Module\Http;


class Response
{
    protected $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function showMessage($message)
    {
        $this->response->header("Content-Type", "text/html; charset=utf-8");
        $this->response->end($message);
    }
}
```

这个类实现了一个简单的输出消息的方法，后续将会增加输出 view 和 API 类型的响应。

现在 Request 和 Response 都有了，但是每次都要分别取这两个对象不太方便，

于是我又定义了一个 Kernel（Http 请求核心类）：

```
<?php

namespace FireRabbitEngine\Module\Http;

class Kernel
{
    protected $request, $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest()
    {
        return $this->request->getRequest();
    }

    public function getResponse()
    {
        return $this->response->getResponse();
    }

    public function getHttpRequest()
    {
        return $this->request;
    }

    public function getHttpResponse()
    {
        return $this->response;
    }
}
```

这个类可以取到 swoole 的请求和响应，也可以取到框架自定义的请求和响应。

这样就把请求和响应封装成一个 Http 核心类了。

### 中间件类
Laravel 的中间件不需要继承任何类，完全由用户自定义，

为了统一规范，我定义了一个中间件的父类：

```
<?php

namespace FireRabbitEngine\Module\Http;

use Closure;

abstract class Middleware
{
    abstract public function handle(Kernel $kernel, Closure $next);
}
```

这里类只有一个抽象方法 handle，所有的中间件继承这个类实现统一的标准。

handle 第一个参数即上文封装的 kernel，在中间件里通过 kernel 来获取参数和返回响应。

再在博客项目的路径下，新建 app/middleware 用来存放中间件 TestMiddlewareA 和 TestMiddlewareB：

```
<?php

namespace App\Middleware;

use Closure;
use FireRabbitEngine\Module\Http\Kernel;
use FireRabbitEngine\Module\Http\Middleware;

class TestMiddlewareA extends Middleware
{
    public function handle(Kernel $kernel, Closure $next)
    {
        $request = $kernel->getHttpRequest();

        if ($request->getQueryParams('a') == 1) {
            $kernel->getHttpResponse()->showMessage('aa');
            return null;
        }

        return $next($kernel);
    }
}
```

TestMiddlewareB：

```
<?php

namespace App\Middleware;

use Closure;
use FireRabbitEngine\Module\Http\Kernel;
use FireRabbitEngine\Module\Http\Middleware;

class TestMiddlewareB extends Middleware
{

    public function handle(Kernel $kernel, Closure $next)
    {
        $request = $kernel->getHttpRequest();

        if ($request->getQueryParams('b') == 1) {
            $kernel->getHttpResponse()->showMessage('bb');
            return null;
        }

        return $next($kernel);
    }
}
```

这两个中间件的逻辑非常简单，就是通过 get 参数来判断是否通过请求，

这样在测试的时候就很方便了，只要在路径上面修改参数即可看到中间件的效果。

> 中间件实际上可以不需要 return null，为了美观后面会对此处的代码进行优化。

### 中间件逻辑
前文通过 array_reduce 来演示 Laravel 中间件的处理逻辑，

现在就要把这个逻辑在框架中进行实现，在 Http 文件夹下新建一个 PipeLine 类：

```
<?php

namespace FireRabbitEngine\Module\Http;

use Closure;

class Pipeline
{
    protected $pipes, $kernel;

    public function send(Kernel $kernel)
    {
        $this->kernel = $kernel;

        return $this;
    }

    public function through($pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then(Closure $destination)
    {
        return array_reduce($this->pipes, $this->carry(), $this->dispatchRouter($destination));
    }

    function carry()
    {
        return function ($stack, $pipe) {

            return function ($passable) use ($stack, $pipe) {

                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                } elseif (!is_object($pipe)) {
                    $pipe = new $pipe;
                }

                return $pipe->handle($passable, $stack);
            };
        };
    }

    function dispatchRouter($destination)
    {
        return function ($passable) use ($destination) {
            $destination($passable);
        };
    }
}
```

这个类就是用来处理中间件逻辑的地方，具体逻辑与前文“对口令游戏”一样。

这个类通过 send 方法接收上面封装好的 Http 核心类 Kernel，

Kernel 类具有获取请求参数和返回响应的权限，它会被传到中间件里。

> 中间件要根据请求参数判断是否符合条件，在中间件还可以直接返回响应

中间件的逻辑类也完成了，接下来就要修改 RouteParams 解析路由实例化控制器的地方。

将原来创建控制器实例的方法抽取出来，封装为 routeResponse：

```
/**
* 执行路由响应
* @return \Closure
*/
protected function routeResponse()
{
    return function ($kernel) {

        // 实例化类
        $controllerName = $this->getFullControllerName();
        $controllerObject = new $controllerName($kernel);
        $this->uri = rtrim($this->request->server['request_uri'], '/');

        $params = $this->getRouteParams();

        // 执行方法时，路径参数作为方法的参数
        call_user_func_array([$controllerObject, $this->action], $params);
    };
}
```

这个方法返回的是一个闭包，也就是说返回值是一个匿名函数。

接下来修改原来的 createResponse 方法，现在可以直接实例化 PipeLine 来调用中间件：

```
/**
    * 执行路由
    * @param $request
    * @param $response
    */
public function createResponse($request, $response)
{
    // 判断请求方法是否正确
    if ($this->method != RequestMethod::ANY && $request->server['request_method'] != $this->method) {
        (new MethodErrorResponse())->response($request, $response, $this);
        return;
    }

    // 判断方法是否存在
    $controllerName = $this->getFullControllerName();
    if (!class_exists($controllerName)) {
        (new ClassNotFoundResponse())->response($request, $response, $this);
    }

    $action = $this->action;

    // 不存在方法则返回404
    if (!method_exists($controllerName, $action)) {
        (new ActionNotFoundResponse())->response($request, $response, $this);
        return;
    }

    $this->request = $request;
    $this->response = $response;

    // 测试用
    $pipes = ['App\\middleware\\TestMiddlewareA', 'App\\middleware\\TestMiddlewareB'];

    $pipeline = new Pipeline();

    $kernel = new Kernel(new Request($request, $this), new Response($response));

    $routeResponse = $pipeline->send($kernel)
        ->through(array_reverse($pipes))
        ->then($this->routeResponse());

    $routeResponse($kernel);
}
```

上述代码中使用：

```
$pipes = ['App\\Middleware\\TestMiddlewareA', 'App\\middleware\\TestMiddlewareB'];
```

手动声明了两个中间件，然后访问任意路由就可以看到中间件的效果了。

测试之后发现中间件正常运行。

### 添加映射关系
框架现在没有中间件名称和类名的映射关系，所以才只能用上面的测试代码来调试。

接下来创建一个配置中间件映射关系的文件，在博客目录下创建 app/Middleware/Kernel.php：

```
<?php

namespace App\Middleware;

class Kernel
{
    /**
     * 实例化的中间件
     *
     * @var [Middleware]
     */
    protected static $instances;

    protected static $middlewares = [
        'a' => TestMiddlewareA::class,
        'b' => TestMiddlewareB::class,
    ];

    public static function getMiddlewareInstance($name)
    {
        // 从已实例化的对象数组中取
        if(isset(self::$instances[$name])) {
           return self::$instances[$name]; 
        }

        // 未实例化的创建新对象
        $middlewareName = self::$middlewares[$name] ?? null;

        if($middlewareName == null) {
            self::$instances[$name] = null;
        } else {
            self::$instances[$name] = new $middlewareName;
        }

        return self::$instances[$name];
    }
}
```

在这里我用一个静态变量来保存实例化的中间件，因为中间件的对象是固定的，

没必要每次调用的时候都重新创建一次，一旦实例化之后就直接放进内存，这样可以提高效率。

这样就完成整个中间件的功能了。

## 测试中间件
编辑 web.php，添加两个测试路由：

```
<?php

$router = new \FireRabbitEngine\Module\Route\Router();

$router->setConfig([

    'namespace' => 'App\\Controller\\Home\\',
    'middleware' => ['a']

])->group(function () use ($router) {

    // 这个路由的中间件为：[auth, other]
    $router->get('/user', 'IndexController@index')->middleware(['b']);

    // 这个路由的中间件为：[auth]
    $router->get('/admin', 'IndexController@index');
    
});

return $router;
```

通过访问上述定义的路由，然后修改 a 和 b 参数的值即可看到中间件的拦截功能。

## 修改记录
### 中间件配置化
修改时间：2020-02-13 22:47

突然发现 PipeLine 方法调用 Kernel 类十分不合理。

框架的代码不应该依赖项目的代码，因此需要优化。

在 app/config 目录下创建 middleware.php 用来保存中间件的名称映射关系：

```
<?php

return [
    'a' => App\Middleware\TestMiddlewareA::class,
    'b' => App\Middleware\TestMiddlewareB::class,
];
```

接着将原本放在 app/Middleware 下面的 Kernel 删掉，

并且在框架 module/Http 目录新建一个 Middleware 目录，将 Middleware.php 移到这个目录下。

同时重新创建一个 Kernel 类：

```
<?php

namespace FireRabbitEngine\Module\Http\Middleware;

class Kernel
{
    /**
     * 实例化的中间件
     *
     * @var [Middleware]
     */
    protected static $instances;

    protected static $middlewares = [];

    /**
     * 读取配置文件
     * @param $middlewares
     */
    public static function setConfig($middlewares)
    {
        self::$middlewares = $middlewares;
    }

    public static function getMiddlewareInstance($name)
    {
        // 从已实例化的对象数组中取
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        // 未实例化的创建新对象
        $middlewareName = self::$middlewares[$name] ?? null;

        if ($middlewareName == null) {
            self::$instances[$name] = null;
        } else {
            self::$instances[$name] = new $middlewareName;
        }

        return self::$instances[$name];
    }
}
```

中间件的配置不再直接写在这个类里，而是通过 `setConfig` 读取配置参数。

接着在修改文件 http_server.php，加入一行代码：

```
\FireRabbitEngine\Module\Http\Middleware\Kernel::setConfig(require './app/config/middleware.php');
```

这样框架和项目之间就不再有直接的依赖关系了。
