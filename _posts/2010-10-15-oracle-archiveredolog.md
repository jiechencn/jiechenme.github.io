---
title: Oracle数据库学习-Archive Redo Log
author: Jie Chen
date: 2010-10-15
categories: [Oracle]
tags: [database]
---

Archive Log保存了所有数据库执行过程中的操作，是对当前Inactive状态的Redo Log的备份，用于备份和数据恢复。

## NONARCHIVE和ARCHIVE的区别

在NONARCHIVE模式下，Redo Log不会被备份，会被LGWR进程覆盖，即发生日志切换时（alter system switch logfile），LGWR后台进程可以覆盖非活动的（INACTIVE）的Redo Log。在数据库备份时，只能使用冷备份，即必须先关闭数据库，然后才能备份所有的物理文件。此模式只能保护数据库免于Instance失败，但不能保护数据库免于磁盘介质的错误。由于不会产生Archive Log文件，所以无须额外考虑Archive Log存储空间。
在Archive模式下，ARCn进程首先对Redo Log进行Archive，然后才会覆盖旧的Redo Log。此模式下可以对数据库联机热备份（无须Shutdown）。数据文件损坏时，除System表空间的数据文件外，其他数据文件都可以恢复。恢复时，还可以指定恢复到哪个特定的时间点。但是由于有大量的Archive Log，所需磁盘空间比较大。

### 查看当前Archive模式

	SQL> archive log list;
	数据库日志模式             非存档模式
	自动存档             禁用
	存档终点            USE_DB_RECOVERY_FILE_DEST
	最早的联机日志序列     51
	当前日志序列           53

### 切换到Archive模式

对Archive的切换和参数的操作，必须保证实例处于mount而非open状态。

	SQL> shutdown
	数据库已经关闭。
	已经卸载数据库。
	ORACLE 例程已经关闭。

	SQL> startup mount
	ORACLE 例程已经启动。

	Total System Global Area  289406976 bytes
	Fixed Size                  1248600 bytes
	Variable Size              92275368 bytes
	Database Buffers          192937984 bytes
	Redo Buffers                2945024 bytes
	数据库装载完毕。

	SQL> alter database archivelog;

	数据库已更改。

## Archive参数设置

### Archive Log路径

有两种设置方法：LOG_ARCHIVE_DEST_n，n为1到10的整数，表示n个Archive Log的目录。或者只设置LOG_ARCHIVE_DEST与LOG_ARCHIVE_DUPLEX_DEST，只有2个目录。

	SQL> alter system set log_archive_dest_1='LOCATION=D:\oracle\zigzag\archive\arch1'
						  log_archive_dest_2='LOCATION=D:\oracle\zigzag\archive\arch2'
						  log_archive_dest_3='LOCATION=D:\oracle\zigzag\archive\arch3';

系统已更改。

### ARCn后台进程参数

最多只能设置为10个进程。

	SQL> show parameter log_archive_max_processes

	NAME                      TYPE    VALUE
	------------------------------------------
	log_archive_max_processes integer 2

	SQL> alter system set log_archive_max_processes=3;

	系统已更改。

### 是否自动归档

	SQL> show parameter log_archive_start

	NAME                TYPE          VALUE
	-----------------------------------------
	log_archive_start   boolean       FALSE

	SQL> ALTER SYSTEM SET LOG_ARCHIVE_START=TRUE SCOPE=SPFILE

	系统已更改。

你也可以手动归档，不用等到当前组的Redo Log被写满。

	SQL> ALTER SYSTEM ARCHIVE LOG ALL;

	系统已更改。

### Archive确保成功的最小数目

此参数强制数据库在重写Redo Log时必须确保成功Archive的数目。如果采用的是LOG_ARCHIVE_DEST与LOG_ARCHIVE_DUPLEX_DEST，则数目不能超过2。

	SQL> show parameter log_archive_min_succeed_dest;

	NAME                         TYPE      VALUE
	--------------------------------------------------------
	log_archive_min_succeed_dest integer   1

	SQL> alter system set log_archive_min_succeed_dest=2;

	系统已更改。

### Archive Log的文件格式

* %s表示Log Sequence Number
* %t为ARCn线程号
* %r为resetlog ID (?待解，尚不明确)

比如：

	SQL> show parameter log_archive_format;

	NAME                  TYPE         VALUE
	------------------------------------------------
	log_archive_format    string       ARC%S_%R.%T


	SQL> alter system set log_archive_format = 'arch_%t_%s_%r.arc' scope=spfile;

	系统已更改。

### 重新启动Instance

	SQL> shutdown immediate;
	数据库已经关闭。
	已经卸载数据库。
	ORACLE 例程已经关闭。

	SQL> startup
	ORACLE 例程已经启动。

	Total System Global Area  289406976 bytes
	Fixed Size                  1248600 bytes
	Variable Size              92275368 bytes
	Database Buffers          192937984 bytes
	Redo Buffers                2945024 bytes
	数据库装载完毕。
	数据库已经打开。

	SQL> archive log list;
	数据库日志模式            存档模式
	自动存档             启用
	存档终点            D:\oracle\zigzag\archive\arch3
	最早的联机日志序列     51
	下一个存档日志序列   53
	当前日志序列           53
