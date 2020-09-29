---
title: Oracle数据库学习-tablespace和datafile
author: Jie Chen
date: 2010-11-12
categories: [Oracle]
tags: [database]
---


每个数据库都由一个或者多个tablespace组成，每个tablespace又由多个datafile组成。表、索引等数据都存储在某个tablespace内。

## 默认的表空间

每个数据库实例创建的时候都会有以下的默认表空间

* SYSTEM，存储数据字典等
* SYSAUX，辅助表空间，存储AWR, Statspack等
* TEMP
* UNDOTBS1,undo的tablespace
* USERS

## 创建tablespace

过程为

1. 创建tablespace以及指定datafile
2. 赋予用户使用tablespace空间的权限
3. 创建数据库时指定table/index等存储

例如我们创建2个tablespace，每个tablespace都建有2个datafile，分别是50M和60M

	SQL> create tablespace zigzag_tbs1 datafile
	  2       'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\zdf_11.DBF' size 50M,
	  3       'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\zdf_12.DBF' size 50M
	  4        extent management local autoallocate;

	表空间已创建。

	已用时间:  00: 00: 04.25


	SQL> create tablespace zigzag_tbs2 datafile
	  2       'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\zdf_21.DBF' size 60M,
	  3       'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\zdf_22.DBF' size 60M
	  4        extent management local autoallocate;

	表空间已创建。

	已用时间:  00: 00: 03.67

接下来赋予myuser这个账户能够访问使用这两个表空间

	SQL> alter user myuser quota unlimited on zigzag_tbs1;

	用户已更改。

	已用时间:  00: 00: 00.00



	SQL> alter user myuser quota unlimited on zigzag_tbs2;

	用户已更改。

	已用时间:  00: 00: 00.00

接下来，改用schema用户登录，为schema建立两个表，分别占用一个tablespace

	SQL> CREATE TABLE table01
	  2  (
	  3    ID    NUMBER,
	  4    Name   VARCHAR2(600)
	  5   )
	  6  TABLESPACE zigzag_tbs1
	  7  /

	表已创建。

	SQL>
	SQL>
	SQL>  begin
	  2    for i in 1..100 loop
	  3       insert into table01 values (i, 'test');
	  4    end loop;
	  5    commit;
	  6    end;
	  7   /

	PL/SQL 过程已成功完成。

	SQL> CREATE TABLE table02
	  2  (
	  3    ID    NUMBER,
	  4    Name   VARCHAR2(600)
	  5   )
	  6  TABLESPACE zigzag_tbs2
	  7  /

	表已创建。

	SQL>
	SQL>
	SQL>  begin
	  2    for i in 1..100 loop
	  3       insert into table02 values (i, 'hello');
	  4    end loop;
	  5    commit;
	  6    end;
	  7   /

	PL/SQL 过程已成功完成。

插入的数据将分别存储在各自tablesapce的数据文件中。

验证每个tablesapce与datafile的关系

	SQL> select tablespace_name, file_name, bytes/(1024*1024) "File Size(MB)" from dba_data_files where tablespace_name like 'ZIGZAG%' order by tablespace_name;

	TABLESPACE_NAME          FILE_NAME                                            File Size(MB) 
	------------------------------------------------------------ -----------------------------------
	ZIGZAG_TBS1             D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_11.DBF    50 
	ZIGZAG_TBS1             D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_12.DBF    50  
	ZIGZAG_TBS2             D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_21.DBF    60 
	ZIGZAG_TBS2             D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_22.DBF    60  


	已选择4行。

	已用时间:  00: 00: 00.04

再查看每个表空间的大小以及使用情况。

	SQL>  SELECT /* + RULE */  df.tablespace_name "Tablespace",
	  2             df.bytes / (1024 * 1024) "Size (MB)",
	  3             SUM(fs.bytes) / (1024 * 1024) "Free (MB)",
	  4             Nvl(Round(SUM(fs.bytes) * 100 / df.bytes),1) "% Free",
	  5             Round((df.bytes - SUM(fs.bytes)) * 100 / df.bytes) "% Used"
	  6        FROM dba_free_space fs,
	  7             (SELECT tablespace_name,SUM(bytes) bytes
	  8                FROM dba_data_files
	  9               GROUP BY tablespace_name) df
	 10      WHERE fs.tablespace_name (+)  = df.tablespace_name
	 11      GROUP BY df.tablespace_name,df.bytes
	 12     UNION ALL
	 13     SELECT /* + RULE */ df.tablespace_name tspace,
	 14            fs.bytes / (1024 * 1024),
	 15            SUM(df.bytes_free) / (1024 * 1024),
	 16            Nvl(Round((SUM(fs.bytes) - df.bytes_used) * 100 / fs.bytes), 1),
	 17            Round((SUM(fs.bytes) - df.bytes_free) * 100 / fs.bytes)
	 18       FROM dba_temp_files fs,
	 19            (SELECT tablespace_name,bytes_free,bytes_used
	 20               FROM v$temp_space_header
	 21              GROUP BY tablespace_name,bytes_free,bytes_used) df
	 22      WHERE fs.tablespace_name (+)  = df.tablespace_name
	 23      GROUP BY df.tablespace_name,fs.bytes,df.bytes_free,df.bytes_used
	 24      ORDER BY 4 DESC;

	Tablespace    Size (MB)  Free (MB)     % Free % Used
	------------------------------------------------------------ ---------- ---------- ---------- ----------
	ZIGZAG_TBS2	120	119.8125	100	0
	INDX		25	24.9375		100	0
	ZIGZAG_TBS1	100	99.8125	100	0
	UNDOTBS1	200	164.8125	82	18
	TEMP		20	16		80	20
	SYSAUX		120	67.1875		56	44
	SYSTEM		300	69		23	77
	USERS		1507.5	200.875		13	87

	已选择8行。

	已用时间:  00: 00: 00.07


其中zigzag_tbs1和zigzag_tbs2的Free为100%，是因为数据量非常小得到的近似值。

真正的table里面的数据存放在哪些datafile中，可以通过联合DBA_DATA_FILES ,DBA_EXTENTS 以及 dba_tables获取

	SQL> SELECT B.OWNER,c.table_name,B.SEGMENT_TYPE,c.tablespace_name, A.FILE_NAME FROM DBA_DATA_FILES A,DBA_EXTENTS B, dba_tables c WHERE A.FILE_ID=B.FILE_ID and b.segment_name=c.table_name and b.owner='MYUSER' and c.tablespace_name like 'ZIGZAG%';

	OWNER   TABLE_NAME  SEGMENT_TYPE  TABLESPACE_NAME  FILE_NAME
	---------------------------------------------------------------------------- ------------------------
	MYUSER  TABLE02     TABLE         ZIGZAG_TBS2      D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_21.DBF
	MYUSER  TABLE01     TABLE         ZIGZAG_TBS1      D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_11.DBF

	已选择2行。

	已用时间:  00: 00: 07.04
	
	  
  