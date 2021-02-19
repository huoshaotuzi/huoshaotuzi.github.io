---
title: 从零开始搭建自己的Swoole框架（十六）JWT用户认证
date: 2021-02-18 12:52:13
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: JSON Web Token（JWT）——为框架添加用于验证用户身份的 token。

---

## 前言
用户认证模块也是网页中非常重要的一个环节，

比如接口无法使用 session，因此只能传一个特殊的参数 "token"，

token 是一个加密的字符串，在服务端进行解密，如果没问题就代表认证成功。

由于自己写的加密系统不安全，所以直接使用比较成熟的加密系统——JWT。

## 安装
jwt 模块集成在框架里，因此要进入框架目录进行安装，而不是直接安装在博客系统里面。

执行命令：`composer requiire firebase/php-jwt`。

## Auth
上一个步骤已经安装了 jwt 插件包，用户只需要关注加密和解密，

对 jwt 具体是怎么实现的，则不需要了解。

因此我封装了一个 Auth 方法：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：12:19
 **/


namespace FireRabbitEngine\Module\Auth;


use Firebase\JWT\JWT;

class Auth
{
    protected static $config;

    public static function setConfig($config)
    {
        self::$config = $config;
    }

    public static function decode($token)
    {
        try {
            JWT::$leeway = self::$config['leeway'];
            $decoded = JWT::decode($token, self::$config['key'], [self::$config['alg']]);
            $data = (array)$decoded;

            return $data['data'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function encode($data, $expired)
    {
        $currentTimestamp = time();
        $key = self::$config['key'];

        $token = [
            'iat' => $currentTimestamp,
            'nbf' => $currentTimestamp,
            'exp' => $currentTimestamp + $expired,
            'data' => $data,
        ];

        return JWT::encode($token, $key, self::$config['alg']);
    }
}
```

用户只需要调用 Auth 暴露的加密和解密方法即可。

## 加载配置
在 app.php 添加新的配置参数：

```
Constant::JWT_CONFIG => [
    'key' => 'password',
    'alg' => 'HS256',
    'leeway' => 60,
],
```

其中 `key` 是加密字符串，`alg` 是加密方法，

`leeway` 是时间偏差值，意思是说这个 token 在这个偏差的时间内都可以算作认证成功(防止服务器时钟偏差)。

## 调用方法
在 test 方法添加如下代码：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:17 PM
 **/

namespace App\Http\Controller\Home;

use FireRabbitEngine\Module\Auth\Auth;
use FireRabbitEngine\Module\Controller\Controller;

class IndexController extends Controller
{
    public function test()
    {
        $token = Auth::encode([
            'test' => 123,
        ], 60);

        var_dump($token, base64_decode($token));

        $value = Auth::decode($token);

        $this->showMessage(json_encode($value));
    }
}

```

这里的 `encode` 的参数是一个数组，即用户的信息，可以是用户的 ID，

但绝对不能是密码或者其他敏感信息，因为 jwt 最终生成的 token 使用的是 base64，可以轻松解密。

上述代码打印的结果为:

```
string(17) "请求URI：/test"
string(177) "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2MTM2MjUwMTIsIm5iZiI6MTYxMzYyNTAxMiwiZXhwIjoxNjEzNjI1MDcyLCJkYXRhIjp7InRlc3QiOjEyM319.ygfIeSOkifgPqWyUyIb5rJFLnHlaYMvGTue0WEsTvP4"
string(131) "{"typ":"JWT","alg":"HS256"}{"iat":1613625012,"nbf":1613625012,"exp":1613625072,"data":{"test":123}}��y#����l�Ȇ���K�yZ`��N�XK��"
object(stdClass)#30 (1) {
  ["test"]=>
  int(123)
}
```

至此，JWT 加密模块就完成了。


