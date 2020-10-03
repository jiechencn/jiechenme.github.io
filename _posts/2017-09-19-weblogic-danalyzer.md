---
title: DAnalyzer for Weblogic+Agile LDAP集成验证工具
author: Jie Chen
date: 2017-09-19
categories: [Works]
tags: [weblogic,ldap]
---

DAnalyzer设计的意图是用LDAP协议来模拟Weblogic、Agile PLM与Directory Server之间的访问。你也可以在软件启动参数中设置<kbd>-Dinclude.agile=false</kbd>来纯粹地使用于Weblogic。

<a href="/assets/res/danalyzer-1.png" target="_blank">![](/assets/res/danalyzer-1.png)</a>

## 功能介绍

### 校验连接

根据不同的Directory Server类型，内置了默认的参数（也可以修改他们使用自己的参数）。只需要填入其他空白参数，就可以验证LDAP的集成。

![](/assets/res/danalyzer-2.png)

Validate后一旦连接成功，Danalyzer会自动把满足条件的User和UserGroup全部读取过来。默认最多读取1001个用户或者组。如果需要读入更多，可以修改启动参数：<kbd>-Dldap.countlimit=1001</kbd>

![](/assets/res/danalyzer-3.png)

### 获取用户LDIF

在User或者UserGroup列表中，右键单击选择 Export LDIF，就能获取当前对象的LDAP属性值。

![](/assets/res/danalyzer-4.png)

### 检查登录

在下方输入User ID和密码，就能验证在当前的LDAP设置下，用户是否能登录Agile PLM、是否能approve变更。

![](/assets/res/danalyzer-5.png)

### 输出报表

Helper菜单中输出报表至HTML文件，能非常直观地看到LDAP通信协议里的参数请求和应答结果。根据这些结果，DAnalyzer给出了相应的解决方法。比如<a href="/lab/danalyzer-report-sample.html" target="_blank">样例列表</a>。

## 软件下载

- <a href="/lab/DAnalyzer1.2.zip" target="_blank">DAnalyzer 1.2</a> , JDK 1.8以上

## 历史版本

- DAnalyzer 1.2 [2018年4月25日]

	> * 增加了Agile应用中的强制字段的校验
	> * 重新格式化HTML Report输出
	> * 增加实时HTML Report的查看
	> * 修复了直接运行jar文件导致默认参数丢失的bug

- DAnalyzer 1.1 [2017年9月15日]

	> * 重构了整个代码架构，提炼出API接口供Agile ACollect工具使用
	> * 重新设置了默认参数
	> * 美化HTML Report输出

- DAnalyzer 1.0 [2017年7月28日]

	> * 初始版本