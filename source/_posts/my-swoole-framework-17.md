---
title: 从零开始搭建自己的Swoole框架（十七）发送邮件 
date: 2021-02-18 16:09:29 
tags:
- PHP
- Swoole
- FireRabbitEngine

categories: 架构

description: 邮件发送系统。

---

## 前言

个人站长一般很难支付起短信的费用，因此邮件认证比较适合个人站长。

## 准备工作

发送邮件是完全免费的，只要搭建一台用于发送邮件的服务器即可，

但是搭建邮件服务器的成本太昂贵了，因此我选择使用第三方提供的邮箱服务。

市面上的各大邮箱基本都是免费注册的，比如 QQ 邮箱，163 邮箱等等。

每种邮箱配置大同小异，我选择网易的 163 邮箱作为演示。

网易邮箱：[https://www.163.com/](https://www.163.com/)

右上角即可免费注册，注册成功后，进入个人中心，点击上方的“设置”，然后可以看到 **POP3/SMTP/IMAP**。

下方有两个可以选择的：

```
IMAP/SMTP服务已关闭 | 开启
POP3/SMTP服务已关闭 | 开启
```

选择 `POP3/SMTP` 右边的“开启”按钮，网易会要求你发送短信进行认证，认证后就可以开通了。

然后会获得一段用于验证的“神秘代码”，要把这个代码存下来，一旦关闭页面就无法再次查看了（但是可以重新创建）。

这样就申请好一个可以发送邮件的邮箱了。

## 安装插件包

PHP 内置的方法也可以发送邮件，但是我选择使用一个比较成熟的插件包：`phpmailer/phpmailer`。

在框架目录下执行：`composer require phpmailer/phpmailer`

即可完成安装。

## Mailer

插件安装完成后，需要封装成方便调用的形式。

在框架目录新建一个 Mail 文件夹用来保存邮件发送相关代码，并且创建 Mailer 类：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2021/2/18
 * Time：14:29
 **/


namespace FireRabbitEngine\Module\Mail;


use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    protected static $mail;
    protected static $config;

    /**
     * 轮询计数器
     * @var int
     */
    protected static $sort = 0;

    /**
     * 邮件节点
     * @var array
     */
    protected static $pool = [];

    protected $subject, $body, $altBody, $reciverMail;

    public static function setConfig($config)
    {
        self::$config = $config;
        self::$pool = $config['pool'];

        self::$mail = new PHPMailer();
        self::$mail->isSMTP();
        self::$mail->SMTPAuth = true;
        self::$mail->SMTPDebug = $config['debug'];
        self::$mail->isHTML($config['html']);
        self::$mail->SMTPSecure = $config['secure'];
    }

    public function subject($title)
    {
        $this->subject = $title;
        return $this;
    }

    public function body($html)
    {
        $this->body = $html;
        return $this;
    }

    public function altBody($text)
    {
        $this->altBody = $text;
        return $this;
    }

    public function address($mail)
    {
        $this->reciverMail = $mail;
        return $this;
    }

    public function send()
    {
        $node = self::$pool[self::$sort];

        self::$sort++;

        if (self::$sort >= count(self::$pool)) {
            self::$sort = 0;
        }

        // 载入节点配置
        self::$mail->Host = $node['host'];
        self::$mail->Port = $node['port'];
        self::$mail->Username = $node['user'];
        self::$mail->Password = $node['password'];
        self::$mail->setFrom($node['user'], $node['name']);
        self::$mail->addReplyTo($node['user'], $node['name']);

        // 生成邮件信息
        self::$mail->addAddress($this->reciverMail);
        self::$mail->Subject = $this->subject;
        self::$mail->Body = $this->body;
        self::$mail->AltBody = $this->altBody ?? '';

        self::$mail->send();
    }
}
```

Mailer 类重新封装了插件包发送邮件的代码，外部调用起来方便多了。

## 加载配置

一个 163 邮箱大约每天只能发送 500-1500 封邮件，

一旦超过这个数，网易就会限制该账户继续发送邮件。

为了避免被限制导致业务无法正常执行，通常我们需要申请很多个邮箱（毕竟注册免费）。

然后类似负载均衡一样轮询多个邮箱，所以在 Mailer 类有一个用来控制轮询的变量 `$sort`，

并且在 `send` 方法动态获取节点配置。

因此需要配置足够多的邮箱以供邮件系统调用，编辑 app.php，添加邮件相关配置：

```
Constant::MAIL_CONFIG => [
    'debug' => 1,
    'html' => true,
    'secure' => 'ssl',
    'pool' => [
        [
            'host' => 'smtp.163.com',
            'port' => 465,
            'user' => 'huotu_001@163.com',
            'name' => '火兔博客1号',
            'password' => 'xxxx',
        ],[
            'host' => 'smtp.163.com',
            'port' => 465,
            'user' => 'huotu_002@163.com',
            'name' => '火兔博客2号',
            'password' => 'xxxx',
        ],[
            'host' => 'smtp.163.com',
            'port' => 465,
            'user' => 'huotu_003@163.com',
            'name' => '火兔博客3号',
            'password' => 'xxxx',
        ],
    ],
],
```

通过增加 `pool` 内邮箱的数量，即可实现一天发送成千上万封邮件。

而且邮箱的配置完全是独立的，不仅可以在这里配置 163 邮箱，QQ 邮箱同样可以。

只要注册多个平台的多个邮箱，这个邮件系统的稳定性就越强，一般而言，个人博客配置 2-5 个邮箱就差不多了。

具体数量根据博客的功能决定，如果发送邮件的场景只有注册和找回密码，那配置 2 个就差不多了；

如果你想要在发布新文章的时候，同时发送一封邮件通知博客的订阅者，那就要多准备一些了（反正申请邮箱不要钱）。

## 实战调用
在需要发送邮件的场景，调用 Mailer 提供的方法：

```
<?php
/**
 * Created by PhpStorm
 * Author：FireRabbit
 * Date：2/9/21
 * Time：1:17 PM
 **/

namespace App\Http\Controller\Home;

use FireRabbitEngine\Module\Controller\Controller;
use FireRabbitEngine\Module\Mail\Mailer;

class IndexController extends Controller
{
    public function test()
    {
        $mail = new Mailer();
        $mail->subject('测测')
            ->body('bbb')
            ->altBody('xxxx')
            ->address('874811226@qq.com')
            ->send();

        $this->showMessage('ok');
    }
}
```

经过测试，邮件确实可以正常发送。

值得一提的是，这里的 `body` 方法可以接收 HTMl 代码，

因此可以结合 blade 模板发出十分美观的邮件。
