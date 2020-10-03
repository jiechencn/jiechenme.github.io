---
title: xSearch 基于正则表达的快速查询插件
author: Jie Chen
date: 2014-11-10
categories: [Works]
tags: []
---

xSearch是一个基于正则表达式来定义和集中管理公司内部不同系统（或搜索引擎）的查询规则的Chrome浏览器扩展应用，它把所有系统查询集中地统一定义，避免了用户在不同系统中来回搜索切换的繁琐。

## 场景使用

假设公司内部有多个系统，比如HR、薪资、项目计划、BUG管理等系统，HR系统查询员工John Smith，返回的结果地址为：http://hr.company.com/search.php?firstname=John&lastname=Smith 。在工资系统中假设查询员工号123456，那么返回地址为：http://salary.company.com/s.cgi?empid=123456 ; 而在项目计划系统中，如果输入员工的域帐号比如john.smith，则会查询到此员工的项目信息，地址为http://project.company.com/index.jsp?name=john.smith&showProject=y 。多个系统采用不同的查询条件，xSearch避免了繁复的系统切换，采用正则表达式精确定义并匹配查询关键字，用户只需要一个查询界面即可。

上述场景中的三个查询可以使用如下的正则表达式区分并定义。如果URL Token为%%%，我们可以有以下三种定义：

	Name: Search HR
	Syntax: ^[A-Z][a-z]+\s[A-Z][a-z]+$
	Target URL: http://hr.company.com/search.php?firstname=%%%1&lastname=%%%2

 .

	Name: Search Salary
	Syntax: ^\d{5,8}$
	Target URL: http://salary.company.com/s.cgi?empid=%%%1

 .

	Name: Search Project
	Syntax: ^[a-z]+\.[a-z]+$
	Target URL: http://project.company.com/index.jsp?name=%%%1&showProject=y


正则定义全部保存到浏览器的本地存储中，如果已经登录了Google Chrome帐号，则也会自动同步到不同电脑的Chrome浏览器中。


## 屏幕截图
![](/assets//res/xsearch_options.png)



![](/assets//res/xsearch_searchbox.png)


## 下载安装
- <a href="https://chrome.google.com/webstore/detail/xsearch/hngjmebjcfiablepngbnchlchchkpcci" target="_blank">xSearch Google Web Store</a>
