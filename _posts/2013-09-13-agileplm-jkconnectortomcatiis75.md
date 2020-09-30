---
title: JK Connector for Tomcat on IIS 7.5
author: Jie Chen
date: 2013-09-13
categories: [AgilePLM]
tags: [tomcat]
---

There are many ways to setup proxy server for Agile File Server. Apache HTTP Web Server, IIS or hardware Load Balancer. If you already have IIS as Proxy for Agile Application Server, then you are fine to install JK Connector on IIS to support Agile File Server, no need to setup other Proxy servers. This document will show how to configure JK Connector on IIS as proxy to service backend Agile File Server. It is also a practical instruction for non-Agile users on how to setup JK Connector 1.2.37 for Tomcat 7.x on IIS 7.5 (Windows 2008R2).

## Platform

We have below platform information.

	OS: Windows 2008 R2 64 bit
	IIS: 7.5
	Apache Tomcat Version 7.0.26
	Tomcat Connector: 1.2.37

We expect the backend Tomcat server to be http://tomcat.internal.com:8080/Filemgr/ , external users to access it from http://server.company.com/Filemgr/ .

## JK Connector Properties

First we create workers.properties file in the JK Connector directory.

	## workers.properties
	worker.list=ajp13,wlb,jkstatus
	worker.ajp13w.type=ajp13
	worker.ajp13w.host=slag9320w8-4.sl.agilesoft.com
	worker.ajp13w.port=8009
	worker.wlb.type=lb
	worker.wlb.balance_workers=ajp13w
	worker.jkstatus.type=status

And create uriworkermap.properties. In this file, we input the App Context here like Filemgr, webdav. Be sure /jkmanager is present

	## uriworkermap.properties
	/Filemgr/*=wlb
	/webdav/*=wlb
	/webdav=wlb
	/jkmanager=jkstatus

Create the third file isapi_redirect.properties in same directory

	## isapi_redirect.properties
	extension_uri=/jakarta/isapi_redirect.dll
	worker_file=C:\IISProxy\Connector\workers.properties
	worker_mount_file=C:\IISProxy\Connector\uriworkermap.properties
	log_file=C:\IISProxy\Connector\isapi_redirect_out.log
	log_level=trace

For research purpose, I set log_level to be "trace" . You can set to "error" to supress too much trace data recorded in this isapi_redirect_out.log file.

Now go back to remote Tomcat machine, make sure AJP/1.3 is present in server.xml file, and the port number is exactly same as the one in workers.properties.

![](/assets/res/troubleshooting_agileplm-jkconnectortomcatiis75-1.jpg)

## IIS Configuration

1, Below we need to manually configure on IIS. First we go to the Server in IIS, go to "ISAPI and CGI Restrictions", add "ISAPI or CGI path". Check "Allow extension path to execute" checkbox.

![](/assets/res/troubleshooting_agileplm-jkconnectortomcatiis75-2.jpg)

2, Go to Web Site, in "ISAPI Filters", add a new "ISAPI Filter"

![](/assets/res/troubleshooting_agileplm-jkconnectortomcatiis75-3.jpg)

3, In the same Web Site, create a Virtual Directory, make sure Alias is lowercase.

![](/assets/res/troubleshooting_agileplm-jkconnectortomcatiis75-4.jpg)

4, Go to "Handler Mappings", click link of "Edit Feature Permissions", select all of "Read", "Script" and "Execute" checkboxes.

![](/assets/res/troubleshooting_agileplm-jkconnectortomcatiis75-5.jpg)

5, Exit IIS Manager, restart Control Panel || Services || "World Wide Web Publishing Service", then open IIS Manager again, restart Server and Web Site

6, Make sure you can access both below links from browser.

	http://tomcat.internal.com:8080/Filemgr/Configuration 
	http://server.company.com/Filemgr/Configuration

## Troubleshooting

1, If the Proxy server does not work, and no isapi_redirect_out.log generated, then issue below command to see if the DLL is loaded. If not, you may have re-configure above steps from scratch.

	C:>tasklist /m isapi*
	Image Name                     PID Modules
	========================= ======== =============================
	w3wp.exe                      4920 isapi.dll, isapi_redirect.dll

2, If isapi_redirect_out.log is generated, you can research it, find any errors and fix it, yourself, by using Google. 



