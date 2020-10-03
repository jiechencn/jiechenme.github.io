---
title: Oracle文件 - spfile参数文件
author: Jie Chen
date: 2014-12-02
categories: [Oracle]
tags: []
---

## 参数查找顺序

Oracle启动时，如果没有特意指定启动参数的文件路径名和文件名，会按照下面的顺序查找启动参数文件。Windows下位于ORACLE_HOME/database/目录下面，Unix位于ORACLE_DATABASE/dbs/下。

1. spfile$ORACLE_SID.ora
2. spfile.ora
3. init$ORACLE_SID.ora

查看当前db启动使用了是spfile还是pfile，最直接方式就是使用 show parameter。

	SYS@orcl> show parameter spfile

	NAME                                 TYPE     VALUE
	------------------------------------ -------- ------------------------------
	spfile                               string   /u01/app/oracle/product/12.1.0.2/db_1/dbs/spfileorcl.ora
	
指定用某个pfile

	SYS@orcl> startup pfile='/u01/app/oracle/product/12.1.0.2/db_1/dbs/init_65535.ora'
	ORACLE instance started.

	Database mounted.
	Database opened.

如果用pfile启动，无法从动态视图中知道pfile的路径在哪里，因为pfile可以存在sqlplus的客户机器上，也可能在服务器上(直接在服务器上运行sqlplus时)。唯一的方法是检查alert日志：

	Using parameter settings in client-side pfile /u01/app/oracle/product/12.1.0.2/db_1/dbs/init_65535.ora on machine localhost.localdomain
	System parameters with non-default values:
	  ...
  
	
## spfile中的内容		  

spfile是个二进制文件，在linux上可以使用 strings命令直接查看，而在windows上，用写字板write.exe也能查看其内容，只是部分有乱码。
											  
	[oracle@localhost ~]$ strings /u01/app/oracle/product/12.1.0.2/db_1/dbs/spfileorcl.ora
	orcl.__data_transfer_cache_size=0
	orcl.__db_cache_size=356515840
	orcl.__java_pool_size=4194304
	orcl.__large_pool_size=8388608
	orcl.__oracle_base='/u01/app/oracle'#ORACLE_BASE set from environment
	orcl.__pga_aggregate_target=335544320
	orcl.__sga_target=557842432
	orcl.__shared_io_pool_size=16777216
	orcl.__shared_pool_size=163577856
	orcl.__streams_pool_size=0
	*.audit_file_dest='/u01/app/oracle/admin/orcl/adump'
	*.audit_trail='db'
	*.compatible='12.1.0.2.0'
	*.control_files='/u01/app/oracl
	e/oradata/orcl/control01.ctl','/u01/app/oracle/fast_recovery_area/orcl/control02.ctl'
	*.db_block_size=8192
	*.db_domain='localdomain'
	*.db_name='orcl'
	*.db_recovery_file_dest='/u01/app/oracle/fast_recovery_area'
	*.db_recovery_file_dest_size=4560m
	*.diagnostic_dest='/u01/app/oracle'
	*.dispatchers='(PROTOCOL=TCP) (SERVICE=orclXDB)'
	*.memory_target=850m
	*.open_cursors=300
	*.processes=300
	*.remote_login_passwordfile='EXCLUSIVE'
	*.sort_area_size=65536#changed by jie on Dec 1
	*.undo_tables
	pace='UNDOTBS1'

另外，在警告日志文件alert.log中，db启动时候也会把非默认值的参数写在日志中。

	Using parameter settings in server-side spfile /u01/app/oracle/product/12.1.0.2/db_1/dbs/spfileorcl.ora
	System parameters with non-default values:
	  processes                = 300
	  memory_target            = 852M
	  control_files            = "/u01/app/oracle/oradata/orcl/control01.ctl"
	  control_files            = "/u01/app/oracle/fast_recovery_area/orcl/control02.ctl"
	  db_block_size            = 8192
	  compatible               = "12.1.0.2.0"
	  db_recovery_file_dest    = "/u01/app/oracle/fast_recovery_area"
	  db_recovery_file_dest_size= 4560M
	  undo_tablespace          = "UNDOTBS1"
	  remote_login_passwordfile= "EXCLUSIVE"
	  db_domain                = "localdomain"
	  dispatchers              = "(PROTOCOL=TCP) (SERVICE=orclXDB)"
	  audit_file_dest          = "/u01/app/oracle/admin/orcl/adump"
	  audit_trail              = "DB"
	  sort_area_size           = 65536
	  db_name                  = "orcl"
	  open_cursors             = 300
	  diagnostic_dest          = "/u01/app/oracle"
  
  
## spfile设定

	alter system set parameter=value 
					<comment='text'> 
					<deferred>
					<scope=memory|spfile|both> <sid='sid|*'>
					<container=current|all>

					
### comment='text'

注释内容，保存在v$parameter.update_comment字段中，spfile中只能保存一次注释，对同一个参数两次设置comment，只会保存最后一次comment，如果保留多次注释，只能在下次设值前手动地把这个字段里的值取出来。

### deferred

表示只对以后新建的session生效，对当前已经建立的的所有session不起作用。有些参数必须强制使用deferred，这些参数可以通过下面查询：

	SYS@orcl> select name from v$parameter where issys_modifiable='DEFERRED';

	NAME
	--------------------------------------------------
	backup_tape_io_slaves
	recyclebin
	audit_file_dest
	object_cache_optimal_size
	object_cache_max_size_percent
	sort_area_size
	sort_area_retained_size
	olap_page_pool_size

	8 rows selected.

如果对上面几个参数修改但是忘了添加deferred指示，则会出现下面错误：

	SYS@orcl> alter system set sort_area_retained_size=1;
	alter system set sort_area_retained_size=1
											 *
	ERROR at line 1:
	ORA-02096: specified initialization parameter is not modifiable with this option

### scope=memory|spfile|both

alter system的作用域，使用spfile启动db时默认值为both，使用pfile启动时，默认值为memory

## 删除参数

删除某设置，会让系统使用默认值。比如在上面的strings命令输出中，sort_area_size为：

	*.sort_area_size=65536#changed by jie on Dec 1

删除后

	SYS@orcl> alter system reset sort_area_size scope=spfile；

	System altered.

此时strings输出后就找不到sort_area_size这一项了。




