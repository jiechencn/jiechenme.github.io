---
title: Oracle数据库学习-Control File
author: Jie Chen
date: 2010-03-05
categories: [Oracle]
tags: [database]
---

Control File是一个二进制文件，存放了当前数据库instance的物理结构信息。数据库open状态下，Control File必须处于可读写的状态。Control File一旦破坏，数据库将无法启动。

Control File可以有多个，但database启动的时候永远只读取第一个Control File，其他的Control作为备份文件只作写操作，因此所有的Control都包含相同的二进制信息。

Control包含的信息如数据库名，创建时间，系统平台信息，checkpoint点等。可以通过字典v$database来获取Control File包含的内容。

如

	SQL> select name, created, platform_name,CHECKPOINT_CHANGE# from v$database;

	NAME      CREATED    PLATFORM_NAME       CHECKPOINT_CHANGE#
	--------- ----------------------------------------------------------------------------------------
	ZIGZAG11  14-DEC-09  Microsoft Windows IA (32-bit)  7946114

## 一、首次创建Control

最初的Control包含在Create database的语句中。创建完成后可通过如下方式查询当前Control路径。

	SQL> select name from v$controlfile;

	NAME
	----------------------------------------------------------

	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL01.CTL
	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL02.CTL
	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL03.CTL

出于安全考虑，原则上，Control文件不能放在同一个物理磁盘上，否则一旦磁盘坏损，所有的Control也就全部丢失。

## 二、修改Control

修改Control一般只在备份的时候，需要重新修改control_files启动参数。

假设database是由spfile方式启动，现在需要增加一个control。粗略的步骤如下：

### 第一步，修改control_files参数，scope为spfile

	SQL> alter system set control_files=
	'D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL01.CTL',
	'D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL02.CTL',
	'D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL03.CTL',
	'D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL04.CTL' scope = spfile;

### 第二步，关闭数据库

### 第三步，手动拷贝一个control文件

比如拷贝Control01.ctl文件为Control04.ctl

### 第四步，再启动数据库

重新检查一下生效的control文件。

	SQL> select name from v$controlfile;

	NAME
	----------------------------------------------------------

	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL01.CTL
	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL02.CTL
	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL03.CTL
	D:\ORACLE\PRODUCT\11.1.0\ORADATA\ZIGZAG11\CONTROL04.CTL

以上例子仅作示意，切忌将所有Control文件放在同一个物理磁盘上。参考如下一个真实环境的Control files。

	SQL> select name from v$controlfile;

	NAME
	----------------------------------------------------------

	/m05/oradata/AG93/ctl01AG93.ora
	/m03/oradata/AG93/ctl02AG93.ora
	/u01/oradata/AG93/ctl03AG93.ora
