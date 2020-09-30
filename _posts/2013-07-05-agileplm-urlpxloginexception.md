---
title: Agile 9.3.2 URL PX error javax.security.auth.login.LoginException in Tomcat 6/7
author: Jie Chen
date: 2013-07-05
categories: [AgilePLM]
tags: [sdk]
---


We have a published Knowledge Document (Note 1549998.1) describing one strange issue that with the correct usage of cookie authentication of URL PX deployed in Tomcat6/7 againt Agile PLM 9.3.2.0 we MAY continuously see below error.

	Error code : 60062
	Error message : Invalid username or password
	Root Cause exception : javax.security.auth.login.LoginException: java.lang.SecurityException: User: cee71a234165ffc3:-5926181d:13fa9e51af6:-7ffd::e0FFUzoxMjh9REU3NDAyNjI4RENCOTYxMTExRkNCMDUwQzIwNjkxNzFCMkEx, failed to be authenticated.
		at com.agile.api.common.WebLogicAuthenticator.login(WebLogicAuthenticator.java:78)
		at com.agile.api.pc.Session.authenticate(Session.java:1123)
		at com.agile.api.pc.Session.(Session.java:216)
		...
		at com.agile.api.AgileSessionFactory.createSession(AgileSessionFactory.java:927)
		at org.apache.jsp.login_jsp._jspService(login_jsp.java:91)
		...
		at org.apache.catalina.core.ApplicationFilterChain.internalDoFilter(ApplicationFilterChain.java:305)
		at org.apache.catalina.core.ApplicationFilterChain.doFilter(ApplicationFilterChain.java:210)
		...
		at java.lang.Thread.run(Thread.java:619)
		
The Note describes that was originally introduced by parameter agile.sso.checkOneTimePXToken, which is used to increase the security of Agile authentication from external. "checkOneTimePXToken" will make Agile to use a different encode method to encrypt the cookie token, it may append a "=" symbol in the encrypted j_password cookie value. However by default, Tomcat 6/7 will ignore the "=" symbol and treat it as a second cookie.

Below we will discuss how we identify the problem. We focus on how we think/analyze, not what the solution is.

First let us code JSP page like below to create Agile session in URL PX which is deployed in Tomcat 6 or 7.

![](/assets/res/troubleshooting-agileplm-urlpxloginexception-1.png)

Now we login Agile WebClient and use Wireshark to capture the TCP data, narrow to cookie section. As the cookies string is too long, Wireshark may truncate it. We can copy the value into notepad and get the whole cookie array like below.

![](/assets/res/troubleshooting-agileplm-urlpxloginexception-2.png)

	JSESSIONID=A9812A7FF1BDC8C65B26456AEDE35729
	invalidate_session=false
	j_username=e0FFUzoxMjh9REU3NDAyNjI4RENCOTYxMTExRkNCMDUwQzIwNjkxNzFCMkEx
	j_password=JSUle0FFUzoxMjh9ODgzQjI0RDM1Qjc0QzA5M0NDQUU0NUZFNjJBODU5QkYzNjFCMDMxQjQ2RjQwM0ZDRDVENTJBODMyNDIwOTBDRTgwQkRDQkREMDhEQkNGRkY4RDRDQzE4QjNCNDRFNzZBMTJGN0M2REQ1QzM3NTI1NEE0OUFGNDRFMTZBODRGODQ0ODQxOUZERTkzMzE3MjFGMEUwQUYzQjM2MTJGNTU1QzJCMTE=JSUl
	
We notice there is a "=" in the tail of cookie "j_password".

Then we trigger the URL PX, check the JSP page, we see below.

	j_username=e0FFUzoxMjh9REU3NDAyNjI4RENCOTYxMTExRkNCMDUwQzIwNjkxNzFCMkEx 
	j_password=JSUle0FFUzoxMjh9ODgzQjI0RDM1Qjc0QzA5M0NDQUU0NUZFNjJBODU5QkYzNjFCMDMxQjQ2RjQwM0ZDRDVENTJBODMyNDIwOTBDRTgwQkRDQkREMDhEQkNGRkY4RDRDQzE4QjNCNDRFNzZBMTJGN0M2REQ1QzM3NTI1NEE0OUFGNDRFMTZBODRGODQ0ODQxOUZERTkzMzE3MjFGMEUwQUYzQjM2MTJGNTU1QzJCMTE 
	Invalid username or password 

Absolutely "=JSUl" is lost from javax.servlet.http.Cookie value. This is Tomcat's behavior to ignore them intentionally. We can add below parameter to TOMCAT/conf/catalina.conf to avoid this. It is described in link http://tomcat.apache.org/tomcat-7.0-doc/config/systemprops.html

	org.apache.tomcat.util.http.ServerCookie.ALLOW_EQUALS_IN_VALUE=true

In above link, there are another two parameter reminding us that some special characters also could be ignored if they are not enabled, these could be <kbd>/</kbd> , <kbd><</kbd> and <kbd>></kbd> .

	org.apache.tomcat.util.http.ServerCookie.ALLOW_HTTP_SEPARATORS_IN_V0
	org.apache.tomcat.util.http.ServerCookie.FWD_SLASH_IS_SEPARATOR


