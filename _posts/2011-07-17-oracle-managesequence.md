---
title: Oracle数据库学习-Sequence序列值
author: Jie Chen
date: 2011-07-17
categories: [Oracle]
tags: [database]
---

Sequence序列值大量应用在表中需要设置ID自动增长作为主键的地方，在当前数据库中属于全局级别。本文简单介绍Sequence的使用。

## 创建

![](/assets/res/oracle_dba_intro_13_createsequence.jpg)

根据Oracle的SQL定义，sequence的创建语法非常清晰。我们用下面的例子演示最常见的创建方法。

	SQL> create sequence myseq
	  2  increment by 10
	  3  start with 100
	  4  nomaxvalue
	  5  nocycle
	  6  cache 5;

	序列已创建。

	已用时间:  00: 00: 00.00

** increment by表示步长
** start with 开始值
** nomaxvalue 是否设置最大值，如果需要则指定 maxvalue xxx，相反，可以指定最小值，适用于步长为复数的情况
** nocycle 当达到最大或最小值后是否循环
** cache 一次性需要缓存多少个序列值

## Cache作用

通过启用执行计划，可以很清楚地观察到cache的作用，减少了db block的读取。 先取第一个序列值，得到100，此时内存中应该缓存了5个数字，分别是100，110，120，130，140

	SQL> set autotrace on;
	SQL> set timing on;
	SQL> select myseq.nextval from dual;
	   NEXTVAL
	----------
		   100

	已用时间:  00: 00: 00.00

	执行计划
	----------------------------------------------------------
	Plan hash value: 833250823
	------------------------------------------------------------------
	| Id  | Operation        | Name  | Rows  | Cost (%CPU)| Time     |
	------------------------------------------------------------------
	|   0 | SELECT STATEMENT |       |     1 |     2   (0)| 00:00:01 |
	|   1 |  SEQUENCE        | MYSEQ |       |            |          |
	|   2 |   FAST DUAL      |       |     1 |     2   (0)| 00:00:01 |
	------------------------------------------------------------------

	统计信息
	----------------------------------------------------------
			 30  recursive calls
			  3  db block gets
			  3  consistent gets
			  0  physical reads
			776  redo size
			412  bytes sent via SQL*Net to client
			385  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed


可以观察到上述db block get的读取。接下来连续多次取下一个序列值。

	SQL> /

	   NEXTVAL
	----------
		   140

	已用时间:  00: 00: 00.01

	执行计划
	----------------------------------------------------------
	Plan hash value: 833250823
	------------------------------------------------------------------
	| Id  | Operation        | Name  | Rows  | Cost (%CPU)| Time     |
	------------------------------------------------------------------
	|   0 | SELECT STATEMENT |       |     1 |     2   (0)| 00:00:01 |
	|   1 |  SEQUENCE        | MYSEQ |       |            |          |
	|   2 |   FAST DUAL      |       |     1 |     2   (0)| 00:00:01 |
	------------------------------------------------------------------

	统计信息
	----------------------------------------------------------
			  0  recursive calls
			  0  db block gets
			  0  consistent gets
			  0  physical reads
			  0  redo size
			413  bytes sent via SQL*Net to client
			385  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed

db block均为0，因为sequence是从内存中直接获取，不需要再从buffer cache中获取。

当试图获取150这个sequence的时候，有了db block get，从buffer cache再次获取。

	SQL> /

	   NEXTVAL
	----------
		   150

	已用时间:  00: 00: 00.00

	执行计划
	----------------------------------------------------------
	Plan hash value: 833250823
	------------------------------------------------------------------
	| Id  | Operation        | Name  | Rows  | Cost (%CPU)| Time     |
	------------------------------------------------------------------
	|   0 | SELECT STATEMENT |       |     1 |     2   (0)| 00:00:01 |
	|   1 |  SEQUENCE        | MYSEQ |       |            |          |
	|   2 |   FAST DUAL      |       |     1 |     2   (0)| 00:00:01 |
	------------------------------------------------------------------

	统计信息
	----------------------------------------------------------
			 14  recursive calls
			  3  db block gets
			  1  consistent gets
			  0  physical reads
			724  redo size
			413  bytes sent via SQL*Net to client
			385  bytes received via SQL*Net from client
			  2  SQL*Net roundtrips to/from client
			  0  sorts (memory)
			  0  sorts (disk)
			  1  rows processed
	SQL>

## currval的获取

currval是会话级别的，多个会话互不干扰。分别创建2个会话，并获取sequence，nextval由于是全局级别，类似C++中的static，因此获得全局唯一值。

	--会话A
	SQL> select myseq.nextval from dual;

	   NEXTVAL
	----------
		   160

	--会话B
	SQL> select myseq.nextval from dual;

	   NEXTVAL
	----------
		   170

再观察不同会话级别的currval

	--会话A
	SQL> select myseq.currval from dual;

	   CURRVAL
	----------
		   160
	SQL>

	--会话B
	SQL> select myseq.currval from dual;

	   CURRVAL
	----------
		   170
	SQL>

## sequence的负增长

sequence的序列号可以实现倒序的负增长。比如下面的例子。

	SQL> create sequence myseq2
	  2  increment by -10
	  3  start with 100
	  4  maxvalue 101
	  5  nominvalue
	  6  nocycle
	  7  cache 5;

	序列已创建。

	SQL> select myseq2.nextval from dual;

	   NEXTVAL
	----------
		   100

	SQL> /

	   NEXTVAL
	----------
			90

	SQL> /

	   NEXTVAL
	----------
			80

	SQL>
