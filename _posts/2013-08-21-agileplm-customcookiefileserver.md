---
title: Custom Cookie impacts Agile File Server operations
author: Jie Chen
date: 2013-08-21
categories: [AgilePLM]
tags: [cookie]
---

Many customers use Web Proxy or hardware Load Balancer in front of Agile Application Server to implement the function of proxy and failover. LoadBalancer may be configured to insert its own cookies to keep session stickiness across different back end Application Servers. It is for technical purpose. While Web Proxy also may be added customized cookie for business uses for customers' own. Each of case about custom cookie may cause File Server fail to upload/download files on IE browser. Let's see how it happens and how to resolve.

## Simulation

I have no hardware Load Balancer, to simulate the problem I just use Apache HTTP Server 2.2. First I add several custom cookies to Apache HTTP. Below is my input in httpd.conf. You may wonder why I add them into Apache HTTP. It is for customer specific that someone may need these customized cookies to develop custom SDK program.

	Header add Set-Cookie: Jie_Cookie_1=hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie;
	Header add Set-Cookie: Jie_Cookie_2=hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie;
	Header add Set-Cookie: Jie_Cookie_3=hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie;
	Header add Set-Cookie: Jie_Cookie_4=hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie;
	Header add Set-Cookie: Jie_Cookie_5=hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie;
	Header add Set-Cookie: Jie_Cookie_6=hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie;

Then I config this Apache HTTP as Web Proxy to one Agile Application Server only. Now I open IE to login the Web Proxy and try to download a file attachment, nothing happens. Try to add attachment, IE browser says "Internet Explorer cannot display the webpage" and we notice that the URL is very longer than 2100 characters.

	http://xxxx.com:8080/Filemgr/AttachmentServlet?ssoToken=%7BAES%3A128%7D1B881B176946D1C28FB5BADF946C04EA72A46F977A78A3DBAED4CF....

I find there is no any error message in both App and FileServer log. However when I switch to Firefox and Chrome, everything work fine even with the long URL.

So definitely it is IE specific and it brings us to a known IE issue when it handles long URL.

http://support.microsoft.com/kb/208427

But if we remove above cookies from httpd.conf, the URL of AttachmentServlet will much shorter.

## Why

Below attachment is cookie section collected from HTTP header when do request to Agile Application Server.

![](/assets/res/troubleshooting_agileplm-customcookiefileserver-1.jpg)

Agile persists them on App server forever. When user uploads/downloads files, Agile will get all these cookies and their values, then encrypt them, encode them, again append them as a part of URL to AttachmentServlet?ssoToken, in order to ask FileServer to retrieve the session data on itself side to get authenticated. So more cookies are included, longer AttachmentServlet URL is. Once the length is longer than 2083, IE then fail to handle.

From below DEBUG log (DEBUG for com.agile.webfs in agile.properties), we can see how these cookies are included by Agile.

	<2013-08-20 05:49:40,111>get agile.properties
	<2013-08-20 05:49:40,112>added cookie: CognosCookie, Value: e0FFUzoxMjh9QTU3RkFFMjg4M0Y5NTBENjQ0QzZGNUE0NTM3NjAyRjEyQTEx
	<2013-08-20 05:49:40,113>added cookie: Jie_Cookie_1, Value: hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie
	<2013-08-20 05:49:40,113>added cookie: Jie_Cookie_2, Value: hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie
	<2013-08-20 05:49:40,114>added cookie: Jie_Cookie_3, Value: hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie
	<2013-08-20 05:49:40,114>added cookie: Jie_Cookie_4, Value: hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie
	<2013-08-20 05:49:40,115>added cookie: Jie_Cookie_5, Value: hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie
	<2013-08-20 05:49:40,116>added cookie: Jie_Cookie_6, Value: hellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookiehellocookie
	<2013-08-20 05:49:40,116>added cookie: JSESSIONID, Value: FP4MSTlb0B1v1QJdmnSvhdmvm32Hnzz1MPpVDvpMtczwfLlSH3cr!-973579987
	<2013-08-20 05:49:40,117>added cookie: jsDebug, Value: 0
	<2013-08-20 05:49:40,117>added cookie: invalidate_session, Value: false
	<2013-08-20 05:49:40,117>added cookie: j_username, Value: e0FFUzoxMjh9QTU3RkFFMjg4M0Y5NTBENjQ0QzZGNUE0NTM3NjAyRjEyQTEx
	<2013-08-20 05:49:40,118>added cookie: j_password, Value: JSUle0FFUzoxMjh9RTY1RURCNDM3NjhBMUZCMzM4NEJGMzI3ODg2QjExMkM2ODNDN0RDN0U0NjlCMURDQzk2QkJEMTJBRjE0NDU4N0REM0U4RUZBNTkwRTg0RURDRDA2OUQ4NjEwNUYwNDI5RjNENDU4NkZCMDg2NzNCMDQwODYwQjU3MUEzRUNDNUE0NkRFJSUl

## Solution

We can exclude these cookies in agile.properties at APP level (it will not impact Web Proxy and Load Balancer session stickiness behavior). So Agile will not include them again when to assemble ssoToken for AttachmentServlet.

	excluded.cookie.names=Jie_Cookie_1,Jie_Cookie_2,Jie_Cookie_3,Jie_Cookie_4,Jie_Cookie_5,Jie_Cookie_6

DEBUG log agains shows the custom cookies are excluded as expected.

	<2013-08-20 05:20:25,704>get agile.properties
	<2013-08-20 05:20:25,705>excluded cookie name in apcm: JIE_COOKIE_1, JIE_COOKIE_2, JIE_COOKIE_3, JIE_COOKIE_4, JIE_COOKIE_5, JIE_COOKIE_6
	<2013-08-20 05:20:25,706>added cookie: CognosCookie, Value: e0FFUzoxMjh9QkQ3M0JFNTEzRjA1M0YxNDhCRjYwMDBERkJEMTYyRUQwMTdD
	<2013-08-20 05:20:25,706>added cookie: JSESSIONID, Value: Xh5qSTpW2jtLhTCd8ThFmpWwf5Kh1XbPdSMWWNjgsWYmD9XzL8sr!683789495
	<2013-08-20 05:20:25,707>added cookie: invalidate_session, Value: false
	<2013-08-20 05:20:25,707>added cookie: j_username, Value: e0FFUzoxMjh9QkQ3M0JFNTEzRjA1M0YxNDhCRjYwMDBERkJEMTYyRUQwMTdD
	<2013-08-20 05:20:25,708>added cookie: j_password, Value: JSUle0FFUzoxMjh9NTZDRTJGMDUwNDFDMkUyM0YwRjk1MUZGQ0I0MTkzQzQxRTRBNERDNzA2NjlFNTc1NTMxNzdBRDdDNURBNjlCNEY5ODhCOTk5NjgwMjRBQTc3MUM4RTkzRDExMjhBMDU1MTg2RTU1ODk5QkM3NDRFQkEyRDk0Njc1RkFENjg3MjE3QjZBJSUl
	<2013-08-20 05:20:25,708>added cookie: jsDebug, Value: 0







