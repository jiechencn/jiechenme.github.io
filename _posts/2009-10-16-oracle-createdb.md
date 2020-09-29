---
title: Oracle数据库学习-手动创建数据库
author: Jie Chen
date: 2009-10-16
categories: [Oracle]
tags: [database]
---

Oracle提供的DBCA隐藏了许多细节，通过手动的创建instance，可以帮助用户更好地理解instance的创建步骤。本文的例子都是在Windows完成，相应的Linux或其他操作系统请参考操作系统文档。

## 1：确定需要创建的instance名字

在命令模式下定义环境变量，假设要创建的名字为zigzag

	set oracle_sid=zigzag

## 2：设置数据库访问权限的验证方式

Oracle提供了2种访问方式

* 以操作系统级的用户作为ora_dba，在windows上，安装完Oracle之后会有一个ora_dba的dba用户组，只要将新加的用户加入该组，即可拥有dba权限。
* 以密码文件的授权形式。用orapwd命令，见如下

		orapwd file=pwd_filename password=my_password entries=number force=y/n

  *	file - 明码文件的全路径，必填项,
  *	password - sys账户的密码，必填 （system的密码默认为manager，可在后面修改）
  *	entries - 密码尝试次数，可选
  *	force - 是否需要覆盖原密码文件，可选

	比如
	
		orapwd file=D:\oracle\product\10.2.0\db_1\database\pwdzigzag.ora 
			password=oracle entries=10 force=y

## 3：创建数据库初始化参数

可以从提供的sample数据库下拷贝一份init.ora到database目录下， 如

	Copy D:\oracle\product\10.2.0\db_1\admin\sample\pfile\init.ora 
	D:\oracle\product\10.2.0\db_1\database\initzigzag.ora

同时修改该initzigzag.ora文件以符合实际需求，如：

	db_name = zigzag  ##修改
	Global_Names = TRUE
	db_domain = ""
	compatible = 9.2.0

	undo_management = AUTO
	undo_tablespace = UNDO_TS   ##注意，必须和原始的init.ora保持一致
	log_checkpoint_interval = 10000
	log_checkpoint_timeout = 0
	processes =  1000
	open_cursors = 1000
	dml_locks = 200	
	max_dump_file_size = 10240 
	remote_login_passwordfile = exclusive  ##设为exclusive表示采用密码文件的方式授权访问
	nls_length_semantics=CHAR

	# Define at least three control files by default, 
	# change the location to appropriate folder where you want to store control files, 
	# make sure that each control file is a separate disk
	control_files = (D:\oracle\product\10.2.0\oradata\zigzag\control01.ctl, 
				D:\oracle\product\10.2.0\oradata\zigzag\control02.ctl, 
				D:\oracle\product\10.2.0\oradata\zigzag\control03.ctl)

	background_dump_dest=D:\oracle\product\10.2.0\admin\zigzag\bdump
	core_dump_dest=D:\oracle\product\10.2.0\admin\zigzag\cdump
	user_dump_dest=D:\oracle\product\10.2.0\admin\zigzag\udump

鉴于在initzigzag.ora文件中指定了目标路径，必须在相应的地方创建这些文件夹，否则在后面的Create Database命令中报路径错误。

	D:\oracle\product\10.2.0\oradata\zigzag
	D:\oracle\product\10.2.0\admin\zigzag\bdump
	D:\oracle\product\10.2.0\admin\zigzag\cdump
	D:\oracle\product\10.2.0\admin\zigzag\udump

## 4：新建Instance

	oradim -new -sid zigzag -startmode manual

## 5：创建instance的服务参数

以sysdba身份登录instance

	sqlplus /nolog
	sql> conn sys/oracle as sysdba

	sql> CREATE SPFILE='D:\oracle\product\10.2.0\db_1\database\spfilezigzag.ora' 
	FROM PFILE='D:\oracle\product\10.2.0\db_1\database\initzigzag.ora';

关闭instance以使下次启动时能启用spfilezigzag.ora作为启动参数

	sql> shutdown
	sql> exit
	sqlplus /nolog
	sql> conn sys/oracle as sysdba
	sql> STARTUP NOMOUNT #启动实例

## 6：创建数据库

给出下面一个示例：

	sql> Create database zigzag
	datafile 'D:\oracle\product\10.2.0\oradata\zigzag\system01.dbf' 
		size 300M reuse autoextend on next 10240K maxsize unlimited extent management local
	sysaux datafile 'D:\oracle\product\10.2.0\oradata\zigzag\sysaux01.dbf' 
		size 120M reuse autoextend on next 10240K maxsize unlimited
	default temporary tablespace temp
	  tempfile 'D:\oracle\product\10.2.0\oradata\zigzag\temp01.dbf' 
	  size 20M reuse autoextend on next 640K maxsize unlimited
	undo tablespace UNDO_TS
	  datafile 'D:\oracle\product\10.2.0\oradata\zigzag\undotbs01.dbf' 
	  size 200M reuse autoextend on next 5120K maxsize unlimited
	logfile
	  group 1 ('D:\oracle\product\10.2.0\oradata\zigzag\redo01.log') size 10240K,
	  group 2 ('D:\oracle\product\10.2.0\oradata\zigzag\redo02.log') size 10240K,
	  group 3 ('D:\oracle\product\10.2.0\oradata\zigzag\redo03.log') size 10240K

## 7：为数据对象增加额外的表空间

给出下面一个示例：

	sql> CREATE TABLESPACE users LOGGING
	DATAFILE 'D:\oracle\product\10.2.0\oradata\zigzag\users01.dbf'
	SIZE 25M REUSE AUTOEXTEND ON NEXT 1280K MAXSIZE UNLIMITED
	EXTENT MANAGEMENT LOCAL;
	-- create a tablespace for indexes, separate from user tablespace
	CREATE TABLESPACE indx LOGGING
	DATAFILE 'D:\oracle\product\10.2.0\oradata\zigzag\indx01.dbf'
	SIZE 25M REUSE AUTOEXTEND ON NEXT 1280K MAXSIZE UNLIMITED
	EXTENT MANAGEMENT LOCAL;

## 8：其他额外的必须的操作

以sys身份，创建数据字典

	sql> @D:\oracle\product\10.2.0\db_1\RDBMS\ADMIN\catalog.sql
	sql> @D:\oracle\product\10.2.0\db_1\RDBMS\ADMIN\catproc.sql

以system/manager身份，运行pupbld.sql，该sql用来限制某些用户的sqlplus的执行权限。

	sql> @D:\oracle\product\10.2.0\db_1\sqlplus\admin\pupbld.sql

## 9：创建schema

到此，一个简略的数据库zigzag已经创建完成，可以开始创建schema了。