---
title: Tomcat Service在Windows上的JVM参数位置
author: Jie Chen
date: 2015-08-18
categories: [Java]
tags: []
---

Tomcat如果以命令行的方式启动，JVM以及程序参数的设置非常简单，只要修改命令文件中的参数就可以了。偶尔接触到Tomcat在Windows上以Service的形式启动，找到JVM设置的位置，花了不少的冤枉时间，因为Tomcat文档几乎没有提到。

## JVM以及程序参数

Tomcat被注册为Service之后，相应的所有启动参数全部写到注册表中，具体的路径就是：

	HKEY_LOCAL_MACHINE
		|_____________SOFTWARE
						|______Wow6432Node
									|______Apache Software Foundation
														|_______________Procrun 2.0
																			|________Service名称
																						|_______Parameters
																										|_______Java
																										|_______Log
																										|_______Start
																										|_______Stop
																							
如图所示

![](/assets/res/troubleshooting_tomcat_service_jvm.png)

### JAVA_HOME

JAVA_HOME的设置超出我的想象，它是以jvm.dll路径的设置来查找JAVA_HOME的。所以，只要设置Jvm路径。

### MS/MX

这两项单独设置，分别是JvmMs和JvmMx

### 其他参数

设置JVM的其他参数或者程序参数，都设置在Options键值中，每个参数必须分行写。比如

	-XX:MaxPermSize=512M 
	-XX:NewSize=1300M
	-Dcatalina.base=C:\Agile\Agile932\FileManager
	-Dcatalina.home=C:\Agile\Agile932\FileManager
	-Dagile.fileServer.config.file=C:\Agile\Agile932\agileDomain\config\server.conf
	-Djava.io.tmpdir=C:\Agile\Agile932\FileManager\temp


> 注意：每个参数行的最后不能留有任何空格，否则启动服务时Windows Event会报参数错误。


## 日志

### Stdout

以StdOutput为auto的键值，日志名为serviceName-stdout.log

### Stderr

以StdError为auto的键值，日志名为serviceName-stdout.log

> 如果不设置上述两项，只会有唯一一个catalina.log的日志。











 