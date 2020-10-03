---
title: Weblogic和LDAP集成的验证请求分析
author: Jie Chen
date: 2017-07-04
categories: [Weblogic]
tags: [ldap]
---

Weblogic和LDAP比如Active Directory的集成登录比较简单。分析集成过程中的LDAP通信，就能很直观地看出他们的工作原理。

所有的LDAP的登录都遵循2个步骤，我画了一张简单的图来表示过程。

1. 查询： SearchRequest/SearchResEntry
2. 验证: BindRequest/BindResponse

![](/assets/res/weblogic-ldap-authenticate-1.png)

下面的分析通过Active Directory来做举例。 假设Weblogic中绑定的用户为user2@mycompany.com 。这个用户是用来做LDAP初始连接，后续的请求都要依赖这条连接。

![](/assets/res/weblogic-ldap-authenticate-3.png)

假设Weblogic配置完LDAP后，应用程序需要通过另一个用户user8来登录。期间的过程为：

1. Weblogic通过user2绑定的连接来查询AD服务器上是否存在user8这个对象
2. AD返回代表user8的DN (Distinguished Name)
3. Weblogic发送DN和用户输入的密码，传给AD要求做验证
4. AD返回验证结果

这个过程中有一些细节很重要。

## SearchRequest

这是第一步，查询是否存在user8。而user8代表的是用户名name属性？还是mail属性？还是一个title属性？AD是不知道的。所以Weblogic在发送这个Search请求的时候，必须明确地告诉AD我是怎么匹配这个user8。 这就用到了 “User Name Attribute” 和 “User From Name Filter”。

### User From Name Filter

这个属性定义了如何设置匹配规则。如果Weblogic中没有指定，也会有个默认值生成。这个在Weblogic初始启动时可以在日志中看到。 比如下面的日志输出就是我没有填写这个属性时Weblogic给我产生的默认值。

	<Created LDAPAtnDelegate = LDAPAtnDelegate: null, realm = null
		user: user,sAMAccountName,null
		userDN: ou=people,dc=mycompany,dc=com, scope: subtree
		userFilters: (&(sAMAccountName=%u)(objectclass=user)) ,(objectclass=user)
		groupDN: ou=groups,dc=mycompany,dc=com, scope: subtree
		groupFilters: (&(cn=%g)(objectclass=group)) ,(objectclass=group)
		sgroup: group,cn,member
		sgroupFilters: (&(member=%M)(objectclass=group))
		dgroup: null,null,null
	com.bea.common.security.utils.LDAPServerInfo@6512a01> 

这张表是Weblogic针对目前主流的LDAP服务器所提供的默认的 User From Name Filter 值。

![](/assets/res/weblogic-ldap-authenticate-4.png)

### User Name Attribute

这个属性是配合User From Name Filter如何精确定义user8所代表的对象？是一个name？mail？还是phone？在这个例子中可以从config.xml截图中看到，user8所代表的 User Name Attribute是sAMAccountName。它是用来唯一标识LDAP中对象标识符。每个不同LDAP提供商都有不同的唯一标识符。

所以Weblogic发出查询请求条件为：(&(sAMAccountName=user8)(objectclass=user))，正如日志所输出的那样。

	<LDAP Atn Login username: user8> 
	<authenticate user:user8> 
	<getConnection return conn:LDAPConnection {ldaps://10.64.204.161:389 ldapVersion:3 bindDN:"user2@mycompany.com"}> 
	<getDNForUser search("ou=people,dc=mycompany,dc=com", "(&(&(sAMAccountName=user8)(objectclass=user))(!(userAccountControl:1.2.840.113556.1.4.803:=2)))", base DN & below)> 

如果从TCP DUMP中看LDAP通信，则更加直观。而且还能看出这个请求要求返回user8对象的额外两个属性： objectguid和sAMAccountName。

![](/assets/res/weblogic-ldap-authenticate-5.png)

## SearchResEntry

AD收到查询请求后，会根据(&(sAMAccountName=user8)(objectclass=user))查找是否存在user8对象，返回DN值已经另外两个额外的objectguid和sAMAccountName值。

	<objectguid is binary> 
	<Retrieved stringized guid:\93\f4\fc\35\58\e8\bd\4d\b8\1f\c3\41\0d\00\ae\c7> 
	<DN for user user8: CN=user8 chen,OU=people,DC=mycompany,DC=com> 

分析TCP DUMP明确地显示了返回结果。

![](/assets/res/weblogic-ldap-authenticate-6.png)

## BindRequest
Weblogic收到查询请求的结果后，则会发送一个验证请求，要求AD对DN和密码进行验证。这次的请求里，发送的不是sAMAccountName，而是 DN，值为“CN=user8 chen,OU=people,DC=mycompany,DC=com”。

	<returnConnection conn:LDAPConnection {ldaps://10.64.204.161:389 ldapVersion:3 bindDN:"user2@mycompany.com"}> 
	<authenticate user:user8 with DN:CN=user8 chen,OU=people,DC=mycompany,DC=com> 
	<getConnection return conn:LDAPConnection {ldaps://10.64.204.161:389 ldapVersion:3 bindDN:"user2@mycompany.com"}> 

在TCP DUMP中可以看到使用了simple验证方式，密码为Oracle1。

![](/assets/res/weblogic-ldap-authenticate-7.png)

## BindResponse

这是AD对验证请求处理后返回的结果。日志显示是否验证成功。

	<authentication succeeded> 
	<returnConnection conn:LDAPConnection {ldaps://10.64.204.161:389 ldapVersion:3 bindDN:"CN=user8 chen,OU=people,DC=mycompany,DC=com"}> 
	<LDAP Atn Authenticated User user8>
	<login succeeded for username user8> 

TCP DUMP也是如此。

![](/assets/res/weblogic-ldap-authenticate-8.png)

其实在验证完user8后，Weblogic会发送额外的请求，要求查询包含user8的所有Group作为user8的附属属性。这些和验证没有多少关系了。

分析LDAP验证之类的问题，最有力的工具就是使用TCP DUMP，捕获tcp信息中LDAP协议通信过程。而类似Weblogic的ATN DEBUG信息，理解起来比较混乱而且容易搞错，因为Weblogic的日志输出毕竟都是程序员写的，比较杂乱。而LDAP协议作为标准就非常规范了。