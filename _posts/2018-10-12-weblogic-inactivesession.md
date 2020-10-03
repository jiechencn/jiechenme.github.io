---
title: 一个JDBC未使用连接池导致的数据库INACTIVE SESSOIN
author: Jie Chen
date: 2018-10-12
categories: [Weblogic]
tags: []
---



本周有个客户报数据库出现大量的INACTIVE SESSION，数据库无法及时清除，导致超出进程限制，应用的其他请求无法得到响应。整个排错的思路很清晰，记录之。

	System parameters with non-default values:
	processes                = 1000
	  
	ORA-00020: maximum number of processes (1000) exceeded

排查他们的数据库，进程数提高2000后，发现还是有大量的连接请求持续不断地产生，慢慢逼近2000。

	> SELECT DISTINCT username, osuser,  COUNT(*) FROM v$session GROUP BY username, osuser ORDER BY COUNT(*) DESC; 

	USERNAME                       OSUSER                      COUNT(*)
	------------------------------ ------------------------- ----------
	AGILE                          agp36                          1452
								   oracle                           63
	SVC_AGILE_EWB                  deadmin                          24
	RCUPROD_OPSS                   agp36                             9
	RCUPROD_IAU_VIEWER             agp36                             9
	RCUPROD_IAU_APPEND             agp36                             9
	AGILE_LINK_ALCON               agile                             5
	SYS                            oracle                            2
	DBSNMP                         oracle                            2
	AGILE                          gssrvarg                          1
	AGILE                          gssvemad                          1
	AGILE                          gsssgaya                          1
	AGILE                          agile                             1

查询session的状态，大量的都是INACTIVE。
	
	> SELECT DISTINCT username, status, COUNT(*) FROM v$session WHERE username='AGILE' GROUP BY username, status ORDER BY status, count(*) DESC;

	USERNAME                       STATUS        COUNT(*)
	------------------------------ ------------  --------
	AGILE                          INACTIVE         1435
	AGILE                          ACTIVE             21

应用程序如果通过连接池来托管连接请求，不可能会出现大量的新的请求。检查Weblogic的连接池，用户设置了最大值96。那么这1000多个连接请求来自哪里呢？

	<jdbc-connection-pool-params>
		<initial-capacity>10</initial-capacity>
		<max-capacity>96</max-capacity>
		<min-capacity>10</min-capacity>

继续通过v$session视图查找客户程序的相关信息，发现请求都来自Weblogic的四个节点服务器。下面的SQL只列出了5行记录。由于正常的连接池请求在完成一次数据后也会出于INACTIVE状态，所以这个SQL也会返回正常的连接信息，可能会干扰分析。可以改变 ROWNUM 来查看更多。

这个SQL可以找出客户程序通过JDBC Driver，发出了大量连接请求，然后未关闭，或者没有正确关闭。

	> SELECT  username, osuser, machine, port, program, status FROM v$session WHERE status='INACTIVE' AND ROWNUM=5;

	USERNAME  OSUSER    MACHINE      PORT      PROGRAM              STATUS
	--------- --------- ------------ --------- -------------------- ---------
	AGILE     agp36     sacvl437     49431     JDBC Thin Client     INACTIVE 
	AGILE     agp36     sacvl434     49434     JDBC Thin Client     INACTIVE 
	AGILE     agp36     sacvl436     49435     JDBC Thin Client     INACTIVE 
	AGILE     agp36     sacvl435     49436     JDBC Thin Client     INACTIVE 
	AGILE     agp36     sacvl435     49443     JDBC Thin Client     INACTIVE 

根据端口号在4个节点上找出相应的进程。比如在sacvl435节点上运行：
	
	$ netstat -napl | grep -E '49436|49443' 

	tcp        0      0 ::ffff:192.168.1.4:49436    ::ffff:192.168.1.1:1521     CLOSE_WAIT 9590/java
	tcp        0      0 ::ffff:192.168.1.4:49443    ::ffff:192.168.1.1:1521     CLOSE_WAIT 9590/java

这两个端口都是同一个Java进程打开的。继续查看详细的Java进程信息。

	$ ps -ef | grep 9590 
	
出乎意料的是，这个进程就是Weblogic节点进程。可是上面已经确认了Weblogic的连接池数最大配备了96个连接。

	$ ps -ef | grep 9590 
	
	agp36    9590  9538 48 02:37 pts/0    00:43:07 /ag6/app/oracle/jdk/bin/java -server ... -Dweblogic.Name=sacvl435-Managed1 weblogic.Server

经过仔细和客户确认，客户最近自定义了一个多线程的扩展程序，这些线程使用了JDBC Driver进行了直接的连接，并未使用任何连接池方式。



