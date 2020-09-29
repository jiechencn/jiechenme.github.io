---
title: Oracle数据库学习-Redo Log
author: Jie Chen
date: 2010-09-24
categories: [Oracle]
tags: [database]
---

日志文件记录了对数据库的所有操作记录，为恢复提供了可行的机制。一个数据库实例的Redo Log必须有两组或两组以上的Group，每组Group含一个或一个以上的Redo Log文件供写操作，同一组的成员文件大小必须一致。LGWR进程根据日志组循环地写，同一组中的Redo Log采用同步写的方式，每个文件被分配到一个LSN (Log Sequence Number)。当写完一个Group的时候，Log Switch会被出发，LSN累加1，同时触发Check Point。

## 日志文件与日志组的状态

查找当前Instance的Group状况和每组的Redo Log文件信息，可以通过v$log和v$logfile的视图获取。

	SQL> select group#, status, archived from v$log;

		GROUP# STATUS                           ARCHIV
	---------- -------------------------------- ------
			 1 INACTIVE                         NO
			 2 CURRENT                          NO
			 3 INACTIVE                         NO
		 
Status有6种

* UNUSED: 刚刚新加入的Redo Log组状态
* CURRENT: 当前正在使用
* ACTIVE: Redo中的脏块数据还没有写入datafile之际的状态，用于实例恢复
* ACTIVE: 不需要用于做实例恢复
* CLEARING:
* CLEARING_CURRENT: 与CLEARING都和alter database clear logfile的动作有关。当该命令运行时，显示CLEARING的状态，一旦执行完毕且成功，则显示UNUSED状态，如果失败或命令被意外中断，则显示CLEARING_CURRENT

查看每个Group内的日志文件

	SQL> select group#, status, member from v$logfile;

		GROUP# STATUS         MEMBER
	---------- -------------- --------------------------------------------------
			 1                D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO01.LOG
			 2                D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO02.LOG
			 3 STALE          D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO03.LOG

LOGFIL的状态有4种：

* INVALID: 文件不可访问
* STALE: 文件内容不完全，比如可能正在添加另一个日志成员
* DELETED: 文件已经不再使用
* (NULL): 文件正在使用中

## 添加一个新的日志组

	SQL> alter database add logfile ('D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO04
	.LOG') size 8M;

	数据库已更改。
	已用时间:  00: 00: 00.81

	SQL> select group#, status, archived from v$log;

		GROUP# STATUS                           ARCHIV
	---------- -------------------------------- ------
			 1 INACTIVE                         NO
			 2 INACTIVE                         NO
			 3 CURRENT                          NO
			 4 UNUSED                           YES

新添加的为UNUSED状态。


## 为日志组添加成员文件

比如为刚添加的Group 4添加一个成员文件，无须指定文件大小，文件大小自动设为和同组的的文件一样大小。

	SQL> alter database add logfile member 
	  'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO04-2.LOG' to group 4;

	数据库已更改。

	已用时间:  00: 00: 00.65
	SQL> select group#, status, member from v$logfile;

		GROUP# STATUS         MEMBER
	---------- -------------- ----------------------------------------
			 1                D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO01.LOG
			 2                D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO02.LOG
			 3                D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO03.LOG
			 4                D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO04.LOG
			 4 INVALID        D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO04-2.LOG
		 
注意到新添加的REDO04-2.LOG文件状态为INVALID。一旦该日志被第一次写入，状态自动更新。

## 删除一个成员文件

删除前必须考虑如下几点：

* 因为oracle要求需要至少有2组的Group，因此当当前只有2组，且被删除的成员文件属于本组最后一个文件时，不能删除。
* 必须确保被删除的成员文件不属于状态为Active或Current的组，如果属于，则必须强制切换Log Switch（见后文）
* 必须确保被删除的成员文件所在的组已经archived（当数据库处于archive模式下，见后文）

比如：

	SQL> alter database drop logfile member 
	   'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\REDO04-2.LOG';

	数据库已更改。

	已用时间:  00: 00: 00.23

## 删除一个日志组

必须确保满足以下条件：

* 必须确保被删除的组的状态为Inactive，如果属于Current，则必须强制切换Log Switch（见后文）
* 必须确保被删除的组已经archived（当数据库处于archive模式下，见后文）

比如：

	SQL> alter database drop logfile group 4;

	数据库已更改。

	已用时间:  00: 00: 00.20

强制Log Switch

	SQL> select group#, status, archived from v$log;

		GROUP# STATUS                           ARCHIV
	---------- -------------------------------- ------
			 1 INACTIVE                         NO
			 2 INACTIVE                         NO
			 3 CURRENT                          NO

	已选择3行。

	已用时间:  00: 00: 00.04
	SQL> alter system switch logfile;

	系统已更改。

	已用时间:  00: 00: 00.21
	SQL> select group#, status, archived from v$log;

		GROUP# STATUS                           ARCHIV
	---------- -------------------------------- ------
			 1 CURRENT                          NO
			 2 INACTIVE                         NO
			 3 ACTIVE                           NO

	已选择3行。

	已用时间:  00: 00: 00.03

使用强制Log Switch后，Group 3的状态由CURRENT切换为ACTIVE，同时，LGWR进程指向Group 1，设为CURRENT。

## 检查数据库Archive模式

有两种方式：

	SQL> select name,log_mode from v$database;

	NAME               LOG_MODE
	------------------ ------------------------
	ZIGZAG             NOARCHIVELOG

	已选择 1 行。

	已用时间:  00: 00: 00.04

或者

	SQL> archive log list;
	数据库日志模式       非存档模式
	自动存档             禁用
	存档终点             USE_DB_RECOVERY_FILE_DEST
	最早的联机日志序列   38
	当前日志序列         40
	
  