---
title: Nginx SSL证书配置问题
date: 2020-03-31 00:57:16
tags:
 - Nginx

categories: Nginx

description: Nginx 配置 SSl 证书时提示：the "ssl" directive is deprecated, use the "listen ... ssl" directive instead in /etc/nginx/conf.d/xxx.conf:57

---

## SSL 问题

Nginx 重启时，报错信息：

```
nginx: [warn] the "ssl" directive is deprecated, use the "listen ... ssl" directive instead in /etc/nginx/conf.d/xxx.conf:57
```


通常我们会在 server 有如下 Nginx 配置：

```
    ssl on;
    ssl_certificate   /etc/nginx/ssl/xxx.com.pem;
    ssl_certificate_key /etc/nginx/ssl/xxx.com.key;
    ssl_session_timeout 5m;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE:ECDH:AES:HIGH:!NULL:!aNULL:!MD5:!ADH:!RC4;
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
    ssl_prefer_server_ciphers on;
```

访问网页提示错误代码 `ERR_SSL_PROTOCOL_ERROR`。

![image.png](https://i.loli.net/2019/12/05/R3GCLPZ8tsbpzS2.png)

原因在于 Nginx 升级到 1.15 版之后，SSL 的配置不再使用 `ssl on`，把这一句去掉就可以。

这时再执行：`nginx -s reload` 平滑重启 Nginx 就不会报错了。

## CURL 无法正常访问
按照上面的步骤重启 Nginx 后，使用 curl 尝试连接到网站，结果依然报错：

```
curl: (35) SSL received a record that exceeded the maximum permissible length.
```

这其实也是升级后配置发生了变化，第一个步骤的提示信息已经给与了提示：`use the "listen ... ssl"`，再次编辑 `xxx.conf`，将 `listen 443` 修改为：`listen 443 ssl`：

```
server {
    listen       443 ssl;
    server_name  idce.com;
    // ...此处省略
}
``` 

保存，然后再执行重启，OK！
