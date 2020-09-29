---
title: Oracle数据库学习-数据库的启动与关闭
author: Jie Chen
date: 2009-10-27
categories: [Oracle]
tags: [database]
---

在不同的场合下，必须选择正确而有效的启动与关闭的方式。本文详细讲述在SQLPLUS模式下启动与关闭的各种方式。

## 1. 启动的初始化参数

Oracle启动时必须读取初始化配置的文件，该文件可以是一个二进制的Server Parameter File (SPFILE)，也可以是一个文本格式的PFILE文件。初始化配置文件的路径在Windows下位于ORACLE_HOME/database/目录下面（Unix位于ORACLE_DATABASE/dbs/下）。

Oracle按照如下查找规则来定位它要读取的配置文件。

1. spfile$ORACLE_SID.ora
2. spfile.ora
3. init$ORACLE_SID.ora

### 指定PFILE参数文件

如果要让Oracle优先读取init$ORACLE_SID.ora，可以在启动参数中指定PFILE。如：

	STARTUP PFILE = D:/oracle/product/10.2.0/db_1/database/initZigzag.ora

### 指定SPFILE文件

如果要读取一个其他路径下的SPFILE，可以采用如下方式：

1. 在database下创建一个PFILE，spf_init.ora文件，内容包含：

	SPFILE = D:/myspf/new_spf.ora
	//指向另一个SPFILE

2. 指定Oracle读取该PFILE：

	STARTUP PFILE = D:/oracle/product/10.2.0/db_1/database/spf_init.ora

## 2：连接数据库

以sysdba身份打开SQLPLUS并连接到数据库（未登录）

	SQLPLUS /NOLOG  
	SQL> CONNECT username AS SYSDBA #一般以sys用户

## 3：启动数据库

不同的参数决定了数据库的启动方式

### 3.1：启动、加载并打开数据库

	SQL> STARTUP
	#可以指定具体的参数文件PFILE或者SPFILE

使用范围：

* 正常的启动方式

### 3.2：启动但不加载数据库

	SQL> STARTUP NOMOUNT

使用范围：

* 创建数据库的过程中

### 3.3：启动、加载但不打开数据库

	SQL> STARTUP MOUNT

使用范围：

* 对redo的log文件进行处理时
* 对数据进行恢复操作时

### 3.4：受限模式下启动、加载并打开数据库

在受限模式下，只有拥有CREATE SESSION和ALTER SESSION的管理员级的用户才能访问数据库，且只能以从本地登录。

	SQL> STARTUP RESTRICT

使用范围：

* 使用export或者import操作时
* 临时限制其他用户访问时
* 数据库移植或升级时
* 在正常启动下，也可以通过ALTER命令来修改数据库的受限状态

		SQL> ALTER SYSTEM DISABLE RESTRICTED SESSION;
		或者
		SQL> ALTER SYSTEM ENABLE RESTRICTED SESSION;

### 3.5：强制启动数据库

建议不要轻易使用此命令。

	SQL> STARTUP FORCE

使用范围：

* 当无法正常启动数据库时
* 当无法通过SHUTDOWN NORMAL, SHUTDOWN IMMEDIATE,或者 SHUTDOWN TRANSACTIONAL正常关闭时
* 如果数据库已经处于启动状态，此时执行STARTUP FORCE，Oracle会先使用ABORT的方式强制关闭数据库再打开。

### 3.6：载体恢复方式启动

	SQL> STARTUP OPEN RECOVER

使用范围：

* 需要恢复数据库时

## 4：改变数据库状态 

加载数据库

	SQL> ALTER DATABASE MOUNT;

### 4.1：打开已关闭的数据库

	SQL> ALTER DATABASE OPEN;

使用范围：

* mount命令已成功

### 4.2：修改读写模式

	SQL> ALTER DATABASE OPEN READ ONLY;
	或者
	SQL> ALTER DATABASE OPEN READ WRITE;

### 4.3：修改受限模式

	SQL> ALTER SYSTEM DISABLE RESTRICTED SESSION;
	或者
	SQL> ALTER SYSTEM ENABLE RESTRICTED SESSION;

## 5：关闭数据库

### 5.1：正常关闭

	SQL> SHUTDOWN NORMAL

受其影响：

* 新的连接请求将被拒绝
* 等待所有已存在的连接陆续断开

### 5.2：立即关闭

	SQL> SHUTDOWN IMMEDIATE

使用范围：

* 应用程序出错，必须断开全部的连接
* 服务器电源马上断电时

受其影响：

* 新的连接请求将被拒绝，新的已连接的事务也被拒绝
* 未提交的事务将被rollback
* Oracle不会等待已连接的用户主动退出，而是强制断开所有连接，并回滚所有的事务。

### 5.3：事务提交后关闭

	SQL> SHUTDOWN TRANSACTIONAL

使用范围：

* 计划中的数据库关闭
* 不影响当前事务的执行

受其影响：

* 新的连接请求将被拒绝，新的已连接的事务也被拒绝
* 已提交的事务将被正确执行，之后，该用户将被动地断开连接

### 5.4：强制关闭

小心使用此命令，容易造成重启时数据文件不一致而奔溃。

	SQL> SHUTDOWN ABORT

使用范围：

* 服务器马上要断电时
* 无法正常启动数据库时

受其影响：

* 新的连接请求将被拒绝，新的已连接的事务也被拒绝
* 当前正在执行的SQL将强制停止
* 未提交的事务反而不会被rollback
* Oracle不会等待已连接的用户主动退出，而是强制断开所有连接。

## 6：数据库静默

将数据库设为静默状态，限制性很大，绝大部分用户都会被拒绝访问。

	SQL> ALTER SYSTEM QUIESCE RESTRICTED;
	或者恢复
	SQL> ALTER SYSTEM UNQUIESCE;

使用范围：

* 当只允许sys和system用户访问、操作数据库时
* 用DML需要改动数据库的表、视图等结构时，防止其他用户同步访问

受其影响：

* 除了sys和system的其他所有用户，都无法连接
* 已连接的session将被inactive

## 7：挂起、恢复数据库

	SQL> SQL> ALTER SYSTEM SUSPEND;
	或者
	SQL> ALTER SYSTEM RESUME;

使用范围：

* 当需要备份数据库而不受影响时

受其影响：

* Suspend时，进行中的I/O操作将直到完成
* 所有的后续的数据访问将被列入队列，直到resume

## 8：查询当前状态

	SQL> SELECT DATABASE_STATUS FROM V$INSTANCE;

  
 