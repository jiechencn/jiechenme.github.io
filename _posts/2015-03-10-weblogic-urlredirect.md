---
title: Weblogic的URL重定向
author: Jie Chen
date: 2015-03-10
categories: [Weblogic]
tags: []
---


在Weblogic中发布的app，通常是以上下文路径的方式来访问的，比如只能访问http://server/myapp，但无法访问http://server。很多情况下我们希望通过直接访问http://server/的方式来调用app。重新定义context path在生产环境中几乎是无法实现的。解决方法是安装第三方的代理服务器，或者你也可以采用下面的技巧。

> 下面的内容，和Proxy代理无关。

假设有一个开发好的app名字叫myapp。常规情况下只能访问http://server/myapp 。如果访问http://server ， weblogic能帮我们直接重定向到http://server/myapp 。实现的原理是新建一个单独的app取名为DummyWeb，设置它的context-root为根。这样当我访问http://server的时候，就能自动转移到http://server/DummyWeb，再通过DummyWeb重定向到myapp。

## weblogic.xml

	<?xml version="1.0" encoding="UTF-8"?>
	<weblogic-web-app xmlns="http://www.bea.com/ns/weblogic/weblogic-web-app">
	<context-root>/</context-root>
	</weblogic-web-app>

## web.xml
为了模拟这个实现，可以创建一个非常简单的web.xml文件，只包换welcome页面的定义。

	<?xml version="1.0" encoding="UTF-8"?>
	<web-app xmlns="http://java.sun.com/xml/ns/javaee"
			   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			   xsi:schemaLocation="http://java.sun.com/xml/ns/javaee 
			   http://java.sun.com/xml/ns/javaee/web-app_2_5.xsd"
			   version="2.5">
	<display-name>DummyWeb</display-name>
		<welcome-file-list>
			<welcome-file>index.html</welcome-file>
		</welcome-file-list>
	</web-app>

## index.html

定义首页，使用http-equiv元中的"refresh"，实现重定向。

	<html>
	<head>
	<meta http-equiv="refresh" content="0;url=/myapp">
	</head>
	</html>


## 测试

只要浏览器访问 http://server ， Weblogic就能自动地重定向到DummyWeb's 的默认首页index.html, 再经由index.html的refresh实现重定向。




