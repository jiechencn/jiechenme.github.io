---
title: LDAP集成故障中的案例一则
author: Jie Chen
date: 2014-07-28
categories: [Weblogic]
tags: []
---

企业应用中经常会需要将业务应用和公司IT内的LDAP做集成，以便使用现成的用户数据。在集成过程中LDAP认证难免会出现错误。这篇文章通过一个案例来分析如何使用第三方的工具对LDAP故障进行诊断。

通常我们可以使用Wireshark（Linux上可以使用TCPDUMP）来收集TCP通信过程中的数据包。比如下图中，可以获得用户信息首先判断用户是否输入了正确的LDAP登录密码。

![](/assets//res/troubleshooting_ldap_err1_tcp1.png)

请求数据包7702的应答包为7703，它包含了LDAP服务器认证的授权应答。在这个案例中它显示“invalidCredentials(49)”，标识LDAP服务器内部拒绝该用户授权。

![](/assets//res/troubleshooting_ldap_err1_tcp1.png)

同时错误数据标识为 data 531

	LDAPMessage bindResponse(1) invalidCredentials (80090308: LdapErr: DSID-0C0903A9, comment: AcceptSecurityContext error, data 531, v1db1)

错误的data 531是LDAP服务器定义的错误类型。可以查看相关的支持文档分析错误代号的含义。比如这个案例中的LDAP是微软的Active Directory服务器。查看微软文档   http://www.microsoft.com/en-us/download/details.aspx?id=985 获取错误说明。

	err 0x531

	# for hex 0x531 / decimal 1329 :
	  ERROR_INVALID_WORKSTATION                                     winerror.h
	# Logon failure: user not allowed to log on to this computer.
	# 1 matches found for "0x531"

错误解释中的“computer”并不是指的是用户的机器，而是应用服务器所在的机器。这个错误表示LDAP服务器拒绝用户从应用服务器登录。之所以拒绝从这台机器登录，一定是LDAP服务器上做了什么设置。仔细查看微软对于AD属性的说明 http://msdn.microsoft.com/en-us/library/ms680868(v=vs.85).aspx  发现一个参数很有可能。

	User-Workstations attribute

	Contains the NetBIOS or DNS names of the computers running Windows NT Workstation 
	or Windows 2000 Professional from which the user can log on. 
	Each NetBIOS name is separated by a comma. Multiple names should be separated by commas.

使用第三方的LDAP浏览工具Softera LDAP Browser ，获取当前用户的所有用户信息，发现了这项设置。

![](/assets//res/troubleshooting_ldap_err1_view.png)

可见，LDAP服务器管理人员有意地设置了UserWorkstations 参数只准许用户从设置的机器上登录LDAP。将该参数的值清空就能解决问题。




 