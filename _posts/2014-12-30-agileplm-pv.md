---
title: PrivilegeViewer权限浏览工具
author: Jie Chen
date: 2014-12-30
categories: [Works]
tags: []
---

PrivilegeViewer能帮助Agile PLM管理员从全局的角度预览所有用户和用户组的Role、Privilege以及Criteria。
> 如果看到Agile PLM系统中的权限结构和PrivilegeViewer中展示的不一致，则说明你的Agie存在错误的Admin数据。

## 截图
![](/assets//res/pv_main.png)


## 图标
 - <img src="/assets//res/pv_usergroup.png"/> User Group
 - <img src="/assets//res/pv_usergroup2.png"/> User Group (Deleted)
 - <img src="/assets//res/pv_user.png"/>  User
 - <img src="/assets//res/pv_user2.png"/>  User (Deleted)
 - <img src="/assets//res/pv_role.png"/>  Role
 - <img src="/assets//res/pv_role2.png"/>  Role (Disabled)
 - <img src="/assets//res/pv_privilege.png"/>  Privilege
 - <img src="/assets//res/pv_privilege2.png"/>  Privilege (Disabled)
 - <img src="/assets//res/pv_criteria.png"/>  Criteria
 
## 使用


1. 修改setEnv.cmd文件，指向正确的JAVA_HOME路径
2. 运行generatePwd.bat给数据库连接密码加密

		D:\pv0.1>generatepwd.bat hello
		D:\pv0.1>"C:\jdk1.6\bin\java" -cp .;pv.jar;lib/crypto.jar xinfo.agile.util.ED hello
		Encrypted:374286F930A3AB

3. 修改PV_HOME/conf/agile.properties文件。其中db.password是经第2步加密后的字符串
4. 通过你的公司邮箱，将修改正确的agile.properties文件内容发送给 xwiz
5. 我们将发送给你一个pv.properties文件，覆盖PV_HOME/conf/文件夹中
6. 运行run.bat


## 下载
- PrivilegeViewer 0.1 本网下载 <a href="/lab/pv0.1.zip" target="_blank">/lab/pv0.1.zip</a>

> PrivilegeViewer是免费的。
