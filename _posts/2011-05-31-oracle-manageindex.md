---
title: Oracle数据库学习-索引及常规使用
author: Jie Chen
date: 2011-05-31
categories: [Oracle]
tags: [database]
---

创建index十分简单，本文简单讲述组合索引、位图索引以及函数所以以及压缩索引，以及如何查看索引的空间使用情况。


## 数据准备

我们先创建一个employee表。

	SQL> create table employee(
	  2         id number not null,
	  3         name varchar2(200) not null,
	  4         title varchar2(200),
	  5         sex varchar2(1) not null
	  6      );
	  
	表已创建。


同时插入女性员工和男性员工多名

	SQL> begin
	  2      for i in 1..100000 loop
	  3        insert into employee values (i, 'name_' || i, 'title_' || i, 'F');
	  4      end loop;
	  5      commit;
	  6      end;
	  7   /

	PL/SQL 过程已成功完成。

	SQL> begin
	  2      for i in 100001..200000 loop
	  3        insert into employee values (i, 'name_' || i, 'title_' || i, 'M');
	  4      end loop;
	  5      commit;
	  6      end;
	  7   /

	PL/SQL 过程已成功完成。

	SQL>


## 最基本的索引

创建一个最基本的索引，并及时统计信息

	SQL> create index idx_id on employee(id) compute statistics;

	索引已创建。

接下来我们看这个索引的执行计划

	SQL> select name from employee where id=101;

	已选择 1 行。

	已用时间:  00: 00: 00.00

	执行计划

	----------------------------------------------------------
	Plan hash value: 1963869640
	----------------------------------------------------------------------------------------
	| Id  | Operation                   | Name     | Rows  | Bytes | Cost (%CPU)| Time     |
	----------------------------------------------------------------------------------------
	|   0 | SELECT STATEMENT            |          |     1 |   315 |     1   (0)| 00:00:01 |
	|   1 |  TABLE ACCESS BY INDEX ROWID| EMPLOYEE |     1 |   315 |     1   (0)| 00:00:01 |
	|*  2 |   INDEX RANGE SCAN          | IDX_ID   |     1 |       |     1   (0)| 00:00:01 |
	----------------------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   2 - access("ID"=101)
	Note
	-----
	   - dynamic sampling used for this statement (level=2)
	
	统计信息
	----------------------------------------------------------
			  0  recursive calls
			  0  db block gets
			  4  consistent gets
			  0  physical reads
			  0  redo size
			536  bytes sent via SQL*Net to client
			523  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed


## 组合索引

组合索引被使用的前提条件是索引中的第一个列必须被where从句用到。创建以下不同的组合索引，加以区别。

	SQL> create index idx_name_title on employee(name, title) compute statistics;

	索引已创建。

	已用时间:  00: 00: 00.23

	SQL> create index idx_id_name_title on employee(id, name, title) compute statistics;

	索引已创建。

	已用时间:  00: 00: 00.24

	SQL> create index idx_name on employee(name) compute statistics;

	索引已创建。

	已用时间:  00: 00: 00.46

.

	SQL>  analyze table employee compute statistics
	  2      for table
	  3      for all indexes
	  4      for all indexed columns
	  5      /

	表已分析。

	已用时间:  00: 00: 02.66


测试如下不同的组合情况

	SQL> select name from employee where id=101 and name='name_101';

	已选择 1 行。

	已用时间:  00: 00: 00.00

	执行计划

	----------------------------------------------------------
	Plan hash value: 1272724938
	--------------------------------------------------------------------------------------
	| Id  | Operation        | Name              | Rows  | Bytes | Cost (%CPU)| Time     |
	--------------------------------------------------------------------------------------
	|   0 | SELECT STATEMENT |                   |     1 |    15 |     1   (0)| 00:00:01 |
	|*  1 |  INDEX RANGE SCAN| IDX_ID_NAME_TITLE |     1 |    15 |     1   (0)| 00:00:01 |
	--------------------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   1 - access("ID"=101 AND "NAME"='name_101')

	统计信息
	----------------------------------------------------------
			  1  recursive calls
			  0  db block gets
			  4  consistent gets
			  0  physical reads
			  0  redo size
			536  bytes sent via SQL*Net to client
			523  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed

where条件用到了id和name，所以优先采用IDX_ID_NAME_TITLE索引

	SQL> select name from employee where name='name_101';

	已选择 1 行。

	已用时间:  00: 00: 00.00

	执行计划
	----------------------------------------------------------
	Plan hash value: 3844016409
	-----------------------------------------------------------------------------
	| Id  | Operation        | Name     | Rows  | Bytes | Cost (%CPU)| Time     |
	-----------------------------------------------------------------------------
	|   0 | SELECT STATEMENT |          |     1 |    11 |     2   (0)| 00:00:01 |
	|*  1 |  INDEX RANGE SCAN| IDX_NAME |     1 |    11 |     2   (0)| 00:00:01 |
	-----------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   1 - access("NAME"='name_101')

	统计信息

	----------------------------------------------------------
			  1  recursive calls
			  0  db block gets
			  4  consistent gets
			  0  physical reads
			  0  redo size
			536  bytes sent via SQL*Net to client
			523  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed

.

	SQL> select name from employee where name='name_101' and title='title_101';

	已选择 1 行。

	已用时间:  00: 00: 00.00

	执行计划

	----------------------------------------------------------
	Plan hash value: 2187645414
	-----------------------------------------------------------------------------------
	| Id  | Operation        | Name           | Rows  | Bytes | Cost (%CPU)| Time     |
	-----------------------------------------------------------------------------------
	|   0 | SELECT STATEMENT |                |     1 |    23 |     2   (0)| 00:00:01 |
	|*  1 |  INDEX RANGE SCAN| IDX_NAME_TITLE |     1 |    23 |     2   (0)| 00:00:01 |
	-----------------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   1 - access("NAME"='name_101' AND "TITLE"='title_101')

	统计信息

	----------------------------------------------------------
			  0  recursive calls
			  0  db block gets
			  4  consistent gets
			  0  physical reads
			  0  redo size
			536  bytes sent via SQL*Net to client
			524  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed

.
	
	SQL> select name from employee where title='title_101';

	已选择 1 行。

	已用时间:  00: 00: 00.04

	执行计划

	----------------------------------------------------------
	Plan hash value: 147428535
	-----------------------------------------------------------------------------------
	| Id  | Operation        | Name           | Rows  | Bytes | Cost (%CPU)| Time     |
	-----------------------------------------------------------------------------------
	|   0 | SELECT STATEMENT |                |     1 |    23 |   490   (1)| 00:00:06 |
	|*  1 |  INDEX FULL SCAN | IDX_NAME_TITLE |     1 |    23 |   490   (1)| 00:00:06 |
	-----------------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   1 - access("TITLE"='title_101')
		   filter("TITLE"='title_101')

	统计信息

	----------------------------------------------------------
			  1  recursive calls
			  0  db block gets
			983  consistent gets
			  0  physical reads
			  0  redo size
			536  bytes sent via SQL*Net to client
			524  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed

注意这个sql，由于where使用title作为条件，没有一个组合索引符合title作为第一列的情况，但是由于select选择是的name，它和title均被索引，因此采用IDX_NAME_TITLE索引，并使用index full scan的方式。

## 函数索引

	SQL> create index idx_func_name on employee(upper(name));

	索引已创建。

	已用时间:  00: 00: 00.26

	SQL> select id, name, sex from employee where upper(name)='NAME_101';

	已选择 1 行。

	已用时间:  00: 00: 00.02


	执行计划

	----------------------------------------------------------
	Plan hash value: 148997294
	---------------------------------------------------------------------------------------------
	| Id  | Operation                   | Name          | Rows  | Bytes | Cost (%CPU)| Time     |
	---------------------------------------------------------------------------------------------
	|   0 | SELECT STATEMENT            |               |  2000 | 68000 |    79   (0)| 00:00:01 |
	|   1 |  TABLE ACCESS BY INDEX ROWID| EMPLOYEE      |  2000 | 68000 |    79   (0)| 00:00:01 |
	|*  2 |   INDEX RANGE SCAN          | IDX_FUNC_NAME |   800 |       |     2   (0)| 00:00:01 |
	---------------------------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   2 - access(UPPER("NAME")='NAME_101')

	统计信息
	----------------------------------------------------------
			 44  recursive calls
			  0  db block gets
			  9  consistent gets
			 28  physical reads
			  0  redo size
			671  bytes sent via SQL*Net to client
			524  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed

## 位图索引

位图索引只适合极少变动数据表的情形，对于大量更新的表并不适宜。

	SQL> create bitmap index idx_b_sex on employee(sex);

	索引已创建。

	已用时间:  00: 00: 00.03

	SQL> select * from employee where sex='F';

	已选择100000行。

	已用时间:  00: 00: 01.71


	执行计划

	----------------------------------------------------------
	Plan hash value: 416980436

	------------------------------------------------------------------------------------------
	| Id  | Operation                    | Name      | Rows  | Bytes | Cost (%CPU)| Time     |
	------------------------------------------------------------------------------------------
	|   0 | SELECT STATEMENT             |           |  2000 | 60000 |    99   (0)| 00:00:02 |
	|   1 |  TABLE ACCESS BY INDEX ROWID | EMPLOYEE  |  2000 | 60000 |    99   (0)| 00:00:02 |
	|   2 |   BITMAP CONVERSION TO ROWIDS|           |       |       |            |          |
	|*  3 |    BITMAP INDEX SINGLE VALUE | IDX_B_SEX |       |       |            |          |
	------------------------------------------------------------------------------------------
	Predicate Information (identified by operation id):
	---------------------------------------------------
	   3 - access("SEX"='F')

	统计信息

	----------------------------------------------------------
			  1  recursive calls
			  0  db block gets
		   7135  consistent gets
			 27  physical reads
			  0  redo size
		4707330  bytes sent via SQL*Net to client
		  73850  bytes received via SQL*Net from client
		   6668  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
		 100000  rows processed

	 
## 压缩索引

压缩索引可以具有相同前缀的键索引，减少冗余。通过以下方法可以比较压缩索引和非压缩的空间使用情况。

	SQL> drop table employee cascade constraints;

	表已删除。

	已用时间:  00: 00: 00.07

	SQL> create table employee(
	  2         id number not null,
	  3         name varchar2(200) not null,
	  4         title varchar2(200),
	  5         sex varchar2(1) not null
	  6      );

	表已创建。

	已用时间:  00: 00: 00.01

	SQL> create table employee2(
	  2         id number not null,
	  3         name varchar2(200) not null,
	  4         title varchar2(200),
	  5         sex varchar2(1) not null
	  6      );

	表已创建。

	已用时间:  00: 00: 00.01

	SQL> begin
	  2      for i in 1..1000000 loop
	  3        insert into employee values (i, 'name_' || i, 'title_' || i, 'F');
	  4  	  insert into employee2 values (i, 'name_' || i, 'title_' || i, 'F');
	  5      end loop;
	  6      commit;
	  7      end;
	  8   /

	PL/SQL 过程已成功完成。

	已用时间:  00: 01: 10.15

	SQL> create index emp_sex_title_idx_1 on employee(sex, title);

	索引已创建。

	已用时间:  00: 00: 02.09

	SQL> create index emp_sex_title_idx_2 on employee2(sex, title) compress 1;

	索引已创建。

	已用时间:  00: 00: 02.15

	SQL>  analyze table employee compute statistics
	  2      for table
	  3      for all indexes
	  4      for all indexed columns
	  5      /

	表已分析。

	已用时间:  00: 00: 06.24

	SQL>  analyze table employee2 compute statistics
	  2      for table
	  3      for all indexes
	  4      for all indexed columns
	  5      /

	表已分析。


计算空间

	SQL> 
	SQL> VARIABLE total_blocks NUMBER
	SQL> VARIABLE total_bytes NUMBER
	SQL> VARIABLE unused_blocks NUMBER
	SQL> VARIABLE unused_bytes NUMBER
	SQL> VARIABLE lastextf NUMBER
	SQL> VARIABLE last_extb NUMBER
	SQL> VARIABLE lastusedblock NUMBER
	SQL> exec DBMS_SPACE.UNUSED_SPACE('AGILE', 'EMP_SEX_TITLE_IDX_1', 'INDEX', :total_blocks, :total_bytes,:unused_blocks, :unused_bytes, :lastextf, :last_extb, :lastusedblock);

	PL/SQL 过程已成功完成。

	已用时间:  00: 00: 00.01

	SQL> print

	TOTAL_BLOCKS ------------ 3712
	TOTAL_BYTES ----------- 30408704
	UNUSED_BLOCKS ------------- 30
	UNUSED_BYTES ------------ 245760
	LASTEXTF ---------- 13
	LAST_EXTB ---------- 65536
	LASTUSEDBLOCK ------------- 98

	SQL> VARIABLE total_blocks NUMBER
	SQL> VARIABLE total_bytes NUMBER
	SQL> VARIABLE unused_blocks NUMBER
	SQL> VARIABLE unused_bytes NUMBER
	SQL> VARIABLE lastextf NUMBER
	SQL> VARIABLE last_extb NUMBER
	SQL> VARIABLE lastusedblock NUMBER

	SQL> exec DBMS_SPACE.UNUSED_SPACE('AGILE', 'EMP_SEX_TITLE_IDX_2', 'INDEX', :total_blocks, :total_bytes,:unused_blocks, :unused_bytes, :lastextf, :last_extb, :lastusedblock);

	PL/SQL 过程已成功完成。

	已用时间:  00: 00: 00.00

	SQL> 
	SQL> print

	TOTAL_BLOCKS------------ 3456
	TOTAL_BYTES----------- 28311552
	UNUSED_BLOCKS------------- 55
	UNUSED_BYTES------------ 450560
	LASTEXTF---------- 13
	LAST_EXTB---------- 68992
	LASTUSEDBLOCK------------- 73


## 查看索引是否被使用过

启用监视

	SQL> alter index emp_sex_title_idx_1 monitoring usage;

	索引已更改。

	已用时间:  00: 00: 00.03

正常的sql操作

	SQL> select count(*) from employee where sex='F';

	  COUNT(*)
	----------
	   1000000

	已选择 1 行。

	已用时间:  00: 00: 00.15

关闭监视

	SQL> alter index emp_sex_title_idx_1 nomonitoring usage;


查看索引使用情况

	SQL> select index_name, monitoring, used, start_monitoring, end_monitoring from v$object_usage where index_name='EMP_SEX_TITLE_IDX_1';

	INDEX_NAME                MONITO USED   START_MONITORING          END_MONITORING
	------------------------- ------ ------ -------------------- --------------------------------------
	EMP_SEX_TITLE_IDX_1       NO     YES    05/31/2011 15:08:35      05/31/2011 15:08:49

	已选择 1 行。


	
	 