---
title: Oracle文件 - trc和alert
author: Jie Chen
date: 2014-12-04
categories: [Oracle]
tags: []
---

trc和alert是用来分析和捕捉错误的关键日志文件。alert警告日志非常详细，包括何时启动，何时关闭数据库，何时切换日志等。同时也会记录内部错误并指出trace跟踪文件的详细路径。trace文件则记录两种类型的日志：系统内部错误信息，用户请求所产生的信息。

## trc和alert的路径

### 11g以前

在Oracle 10g和9i版本上，跟踪文件和警告日志的路径由user_dump_dest或者background_dump_dest设定。

	SYS@orcl> show parameter dump_dest

	NAME                                 TYPE                            VALUE
	------------------------------------ ---------------------------------------------------------------
	background_dump_dest                 string                         /u01/app/oracle/product/10.1.0.2/db_1/rdbms/log
	core_dump_dest                       string                         /u01/app/oracle/diag/rdbms/orcl/orcl/cdump
	user_dump_dest                       string                         /u01/app/oracle/product/10.1.0.2/db_1/rdbms/log

具体的全路径文件名可以通过v$parameter, v$instance, v$session, v$parameter获取。

	select pa.value || '/' || i.instance_name || '_ora_' || p.spid || '.trc' alert_trace from v$process p, v$session s, v$parameter pa, v$instance i where p.addr=s.paddr and s.sid = (select sid from v$mystat where rownum=1) and pa.name in ('user_dump_dest', 'background_dump_dest')
	union
	select p.value || '/alert_' || i.instance_name || '.log' alert_trace from v$parameter p, v$instance i where name in ('user_dump_dest', 'background_dump_dest');


比如返回结果

	ALERT_TRACE
	----------------------------------------------------------------------------------------------------
	/u01/app/oracle/product/10.1.0.2/db_1/rdbms/log/alert_orcl.log
	/u01/app/oracle/product/10.1.0.2/db_1/rdbms/log/orcl_ora_1707.trc

而文件保存在user_dump_dest还是background_dump_dest中，则由数据库服务的连接方式决定。如果使用专用服务器连接方式，日志记录在user_dump_dest中，如果是共享服务器连接，则记录在background_dump_dest中。判断当前连接方式是专有还是共享，从v$session就能获得。

	SYS@orcl> select server, sid from v$session where sid=userenv('sid');

	SERVER                             SID
	--------------------------- ----------
	DEDICATED                            1

### 11g和12c

从11g开始，user_dump_dest和background_dump_dest已经不再支持，被自动诊断库Automatic Diagnostic Repository (ADR)替代，参数由DIAGNOSTIC_DEST决定，并且trc和alert文件全路径可以通过v$diag_info视图中经过下面规律计算而得到：

	ADR_HOME = <DIAGNOSTIC_DEST>/diag/rdbms/<dbname>/<instname>/
	TRACE FILE = <ADR_HOME>/trace/<instance>_ora_<spid>.trc
	ALERT FILE = <ADR_HOME>/trace/alert_ora.log
	ALERT FILE(XML format) = <ADR_HOME>/alert/log.xml

根据这个规则，很容易通过SQL找出这两个日志文件的全路径名

	select 'DB Alert Log', value || '/alert_' || (select instance_name from v$instance) || '.log' from v$diag_info where name='Diag Trace' 
	union
	select 'DB Alert Log (XML)', value || '/log.xml' from v$diag_info where name='Diag Alert' 
	union
	select 'Trc for Current Session', value || '/' || (select instance_name from v$instance) || '_ora_' || (select p.spid from v$process p, v$session s, (select sid from v$mystat where rownum=1) t where p.addr=s.paddr and t.sid=s.sid and s.audsid=userenv('sessionid')) || '.trc' from v$diag_info where name='Diag Trace'
	union
	select 'Default Trc for Current Session', value from v$diag_info where name='Default Trace File'
	union
	select 'Trc Directory', value from v$diag_info where name='Diag Trace';

返回结果

	DB Alert Log
	/u01/app/oracle/diag/rdbms/orcl/orcl/trace/alert_orcl.log

	DB Alert Log (XML)
	/u01/app/oracle/diag/rdbms/orcl/orcl/alert/log.xml

	Default Trc for Current Session
	/u01/app/oracle/diag/rdbms/orcl/orcl/trace/orcl_ora_11104.trc

	Trc Directory
	/u01/app/oracle/diag/rdbms/orcl/orcl/trace

	Trc for Current Session
	/u01/app/oracle/diag/rdbms/orcl/orcl/trace/orcl_ora_11104.trc


最后一行记录返回orcl_ora_1707_xxxxx.trc，这是默认的trc文件，如果用户对会话跟踪文件加了特定的文件标识名，通过'Default Trace File'就能很好地辨识出来。

另外trc文件在11g以后也能通过v$process视图一下子就能获得

	select p.traceid, p.tracefile from v$process p, v$session s where p.addr=s.paddr and s.audsid=userenv('sessionid');

	TRACEID    TRACEFILE
	--------------------------------------------------------------------
			   /u01/app/oracle/diag/rdbms/orcl/orcl/trace/orcl_ora_1707.trc


## 自定义标识trc文件

通过对trc文件名进行自定义标识的好处是，如果没有权限执行上述的视图，查找带标识的文件名就比较方便。比如设定标识为xxxxx

	alter session set tracefile_identifier='xxxxx'; -- 关闭标识则设tracefile_identifier=''

开始跟踪会话

	exec dbms_monitor.session_trace_enable -- 关闭会话跟踪 exec dbms_monitor.session_trace_disable

那么所有的会话跟踪信息就会被记录到类似orcl_ora_1707_xxxxx.trc的文件中，使用操作系统查找文件名包含xxxxx就比较容易。

从v$process中验证文件名：

	select p.traceid, p.tracefile from v$process p, v$session s where p.addr=s.paddr and s.audsid=userenv('sessionid');

	TRACEID    TRACEFILE
	--------------------------------------------------------------------
	xxxxx      /u01/app/oracle/diag/rdbms/orcl/orcl/trace/orcl_ora_1707_xxxxx.trc

 
 
