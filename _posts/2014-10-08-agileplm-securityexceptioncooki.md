---
title: java.lang.SecurityException caused by Cookie Sharing
author: Jie Chen
date: 2014-10-08
categories: [AgilePLM]
tags: []
---

There is a very common issue which could impact all Agile PLM customers' integration that Agile SDK fails to create session and hits error " java.lang.SecurityException: User: e0FFxxxxxxx, failed to be authenticated". This article describes the scenario and call your attention to the fact that it is another issue than described in previous article Agile 9.3.2 URL PX error javax.security.auth.login.LoginException in Tomcat 6/7.

Here is the scenario. There are two different system set up in same domain, TEST environment with server plmtest.sl.agilesoft.com and PRODUCTION environment with server plmprod.sl.agilesoft.com. TEST server has "cookie.domain=.sl.agilesoft.com" in agile.properties while PRODUCTION has same or "cookie.domain=.agilesoft.com" (In this article, we use .agilesoft.com for the example). User integrates URL-PX for PRODUCTION. Now end user first visit TEST server's WebClient and logon, then in the same browser he switches to PRODUCTION SERVER to logon and triggers the URL-PX. The URL-PX will randomly throws error like below.

	Error code : 60062
	Error message : Invalid username or password
	Root Cause exception : javax.security.auth.login.LoginException: java.lang.SecurityException: 
			User: e0FFUzoxMjh9QkQ3M0JFNTEzRjA1M0YxNDhCRjYwMDBERkJEMTYyRUQwMTdD, failed to be authenticated.
		at com.agile.api.common.WebLogicAuthenticator.login(WebLogicAuthenticator.java:78)
		at com.agile.api.pc.Session.authenticate(Session.java:1144)
		at com.agile.api.pc.Session.(Session.java:227)
		at sun.reflect.NativeConstructorAccessorImpl.newInstance0(Native Method)
		at sun.reflect.NativeConstructorAccessorImpl.newInstance(NativeConstructorAccessorImpl.java:57)
		at sun.reflect.DelegatingConstructorAccessorImpl.newInstance(DelegatingConstructorAccessorImpl.java:45)
		at java.lang.reflect.Constructor.newInstance(Constructor.java:525)
		at com.agile.api.AgileSessionFactory.createSession(AgileSessionFactory.java:994)
	
"User: e0FFUzoxMjh9QkQ3M0JFNTEzRjA1M0YxNDhCRjYwMDBERkJEMTYyRUQwMTdD" is a j_username from cookie because in URL-PX we use below sample code to retrieve browser session's cookie to connect to Agile.

![](/assets//res/troubleshooting-agileplm-websecurityexception-1.png)

To analyze the issue we capture all the HTTP data from access of TEST and PRODUCTION WebClient and we find below behavior.

When to logon TEST WebClient, we have these cookie settings for j_username and j_password.

![](/assets//res/troubleshooting-agileplm-websecurityexception-2.jpg)

	j_username=e0FFUzoxMjh9QkQ3M0JFNTEzRjA1M0YxNDhCRjYwMDBERkJEMTYyRUQwMTdD; 
	j_password=JSUle0FFUzoxMjh9MDFBRDExQTM5MzdERUJCMjA2REU3MEI2ODRDN0ZEMDdDNEU3MTY4NzZDQkRFRDQ0OTIyNzg3RkYzQzdGMzI4RTA5MENERDkxNzQ5MEExRDM3Q0ZBNTFGRDNFOUJEREJGMDAzQTVBNURDRjE1MEM2N0U4NTIwQURFREExNjg4MDI1NzI1JSUl
	domain=.sl.agilesoft.com

While to logon PRODUCTION WebClient, we have new cookies settings

![](/assets//res/troubleshooting-agileplm-websecurityexception-3.jpg)

	j_username=e0FFUzoxMjh9RDRENzM1QTM5MjdDMUZDNTRFQjFGMzg5QkQ2RTk4RDQ2ODg1
	j_password=JSUle0FFUzoxMjh9RDM3RTU5QjQ2REY2MTUyRjY1RTZBMjZDN0M4MUUxODhGMEYyMjIxOTBBOTkwMzRBRDlENUNFNDFDN0U2MDkwMTk3QjA4NEJERERFQkNEMzM0QkE5REIxMkYzQzBGMTE2ODU2RkY2MzdBMzlCQUQzMzY1MkFFNjg4MjYyM0I0MjhCNTNFJSUl
	domain=.agilesoft.com

Above two groups of j_username/j_password are correct. However the browser will keep these two groups of cookies in the same HTTP Session as demonstrated in below screenshot. And if URL-PX is triggered from PRODUCTION server, above source code will get a wrong group of cookie. The root cause is all the browsers have almost the same feature that browser will share cookie across different windows.

![](/assets//res/troubleshooting-agileplm-websecurityexception-4.jpg)

There are some workaround for IE, Chrome and Firefox to disable cookie sharing, but the recommended soluton is to divide different Agile servers into different subdomains, for example one in .sl.agilesoft.com and one in .pd.agilesoft.com, meanwhile set cookie.domain to its own, corresponding subdomain value in agile.properties. 
