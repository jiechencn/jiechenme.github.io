---
title: Oracle数据库学习-数据库的服务进程
author: Jie Chen
date: 2009-11-04
categories: [Oracle]
tags: [database]
---

简单讨论了在10g中的服务进程Dedicated Server Process和Shared Server Process。

## 服务进程的类型

Oracle的服务进程用来处理连接到当前实例的用户进程的一系列请求。在10g中包括以下两种类型

* Dedicated专注模式
* Shared共享模式

### Dedicated专注模式

Oracle所默认的即是专注模式下的服务进程，观察下图可知一个Server Process为一个用户进程服务。

![](/assets/res/oracle_dba_intro_3_dedicated_server.gif)

### Shared共享模式

在共享模式下，Oracle维持一定数量的服务进程，并指派当前处于闲置状态的服务进程响应队列中的用户请求。如下图：

![](/assets/res/oracle_dba_intro_3_shared_server.gif)

1. 转发器Dispatcher接受到用户端的请求
2. 请求被置入Request队列，并建立了类似电路的一条回路Circurt用来标识请求是来自哪个Client。
3. 某个闲置的Server Process开始处理队列中的请求
4. SGA内存分配
5. 将处理的结果置入Response队列
6. 结果从Response Queue返回给Dispatcher
7. Dispatcher将结果最终返回给当初的Client

### Shared与Dedicated的内存比较

假设一个应用程序访问Oracle的每个Session需要400KB的内存，每个服务进程的内存需要4MB，所分配的共享服务进程数为100个。现在有5000个客户连接，

则在Dedicated模式下：

	内存=5000 * (400KB+4MB) = 22GB
  
Shared模式下：

	内存=5000 * 400KB + 100 * 4MB = 2.5GB
	
## 配置Dedicated Server

Oracle默认的Server Process是Dedicated，因此无需特殊配置，只要在Client的连接方式上声明Server为DEDICATED。比如Oracle Client的tnsname.ora文件声明如下：

	ZIGZAG =
	  (DESCRIPTION =
		(ADDRESS_LIST =
		  (ADDRESS = (PROTOCOL = TCP)(HOST = 10.204.104.195)(PORT = 1521))
		)
		(CONNECT_DATA =
		  (SERVER = DEDICATED)
		  (SERVICE_NAME = zigzag)
		)
	  )

## 配置Shared Server

### 初始化参数

在SPFile或者PFile的初始化文件中，指定了如下参数：

* SHARED_SERVERS: 必须的参数，指定Oracle要保持的最小服务进程数。
* MAX_SHARED_SERVERS:
* SHARED_SERVER_SESSIONS: Shared Server模式下指定用户session的总数，目的是为使用Dedicated的用户保留一部分会话所需的资源。
* DISPATCHERS: 转发器数量
* CIRCUITS: 指定回路数量

### 启用Shared

如果在SPFile或者PFile中没有指定SHARED_SERVERS或者值为0，表示Oracle没有启用Shared模式。可以通过以下命令动态修改：

connect sys/oracle as sysdba
SQL> alter system set shared_servers = 5;

### 配置Dispatcher

如果没有指定DISPATCHERS的值但是Shared Server被enable了，则DISPATCHERS默认为1，即Oracle自动创建一个Dispatcher

Dispatcher有如下属性可以动态Alter

* ADDRESS: 网络协议的地址，如：(ADDRESS=(PROTOCOL=TCP)(PORT=5000)
* DESCRIPTION: 略
* PROTOCOL: TCP还是TCPS
* DISPATCHERS: 初始启动的数量
* CONNECTIONS: 每个转发器的最大连接数
* SESSIONS: 每个转发器的最大会话数
* TICKS: 连接池超时
* LISTENER: 
* MULTIPLEX: 
* POOL:连接池on还是off
* SERVICE: 

动态修改Dispatcher最好需要事先预估所要配置的数量。大致计算方式为：

	CEIL ( max. concurrent sessions / connections for each dispatcher )

查看当前所配置的Dispatcher

	SQL> select count(*) from v$dispatcher_config;

	  COUNT(*)
	----------
			 2
		 
增加一个Dispatcher

	SQL> ALTER SYSTEM SET DISPATCHERS = '(INDEX=2)(PROT=tcp)(DISP=4)(POOL=on)';
	System altered.

查看具体的Dispatcher的配置

	SQL> select conf_indx, dispatchers, sessions, pool, connections ,network from v$dispatcher_config;

	 CONF_INDX DISPATCHERS   SESSIONS POOL CONNECTIONS  NETWORK
	---------- ----------- ---------- ---- -----------------------------------
			 0           3       1002 OFF         1002  (ADDRESS=(PARTIAL=YES)(PROTOCOL=TCP))
			 1           1      16383 BOTH        1002  (ADDRESS=(PARTIAL=YES)(PROTOCOL=TCP))
			 2           4      16383 BOTH        1002  (ADDRESS=(PARTIAL=YES)(PROTOCOL=TCP))

停止某个Dispatcher

	SQL> ALTER SYSTEM SHUTDOWN IMMEDIATE 'D002';

这里的D002是Dispatcher的唯一主键，可以通过查询V$DISPATCHER获得。

### 检查当前Session连接的服务模式

下面的例子用来测试如何查看当前的Session连接的服务模式。在Client的tnsname.ora中分别设置两个descriptor，ZIGZAG1和ZIGZAG2，分别声明以Dedicated和Shared连接。

	ZIGZAG1 =
	  (DESCRIPTION =
		(ADDRESS_LIST =
		  (ADDRESS = (PROTOCOL = TCP)(HOST = 10.204.104.195)(PORT = 1521))
		)
		(CONNECT_DATA =
		  (SERVER = DEDICATED)
		  (SERVICE_NAME = zigzag)
		)
	  )

	ZIGZAG2 =
	  (DESCRIPTION =
		(ADDRESS_LIST =
		  (ADDRESS = (PROTOCOL = TCP)(HOST = 10.204.104.195)(PORT = 1521))
		)
		(CONNECT_DATA =
		  (SERVER = SHARED)
		  (SERVICE_NAME = zigzag)
		)
	  )
  
分别以sys和jerry两个用户登录zigzag1和zigzag2，在闲置状态下查看当前会话：

	SQL> select schemaname, server, program from v$session;
	SCHEMANAME                     SERVER	  PROGRAM
	------------------------------ ---------------------
	SYS                            DEDICATED  sqlplus.exe
	JERRY                          NONE       sqlplus.exe  注意此处的NONE

让用户jerry的会话处于active状态，比如连续插入100万个记录

	SQL> create table table01(
	  2    id number(10),
	  3    name varchar2(200)
	  4  );

表已创建。

	SQL> begin
	  2    for i in 1..500000 loop
	  3      insert into table01 values (i, 'test');
	  4    end loop;
	  5    commit;
	  6    end;
	  7  /
  
以Sys用户查看session

	SQL> select schemaname, server, program from v$session;

	SCHEMANAME                     SERVER	  PROGRAM
	------------------------------ ---------------------
	SYS                            DEDICATED  sqlplus.exe
	JERRY                          SHARED     sqlplus.exe

可以看到jerry当前是由Shared Server Process服务。

### Resident Connection Pooling

在11g中，新增了一个Resident Connection Pooling模式的服务进程，请查阅相关Oracle Document。
 