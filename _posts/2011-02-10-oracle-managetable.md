---
title: Oracle数据库学习-表管理
author: Jie Chen
date: 2011-02-10
categories: [Oracle]
tags: [database]
---

Oracle中的表有很多类型，本文章涉及Heap-Organized的Table，External Table和临时表，以及Index-Organized Table。

## Heap-Organized Table

最常见的表就是Heap-Organized Table，在创建之初，可以指定ORGANIZATION HEAP，或者直接忽略此参数。Heap-Organized表中数据无序存储，由rowid物理地址唯一地标识每一行记录。

Heap-Organized表的创建可以指定表空间，存储参数，比如下面例子。


	SQL> drop tablespace ZIGZAG_TBS0
	  2  including contents and datafiles;

	表空间已删除。
	已用时间:  00: 00: 04.29

先创建表空间，Uniform的管理方式。

	SQL> CREATE TABLESPACE ZIGZAG_TBS0 DATAFILE
	  2    'D:\ORACLE\PRODUCT\10.2.0\ORADATA\ZIGZAG\ZDF_01.DBF'
	  3    SIZE 10M AUTOEXTEND OFF
	  4    LOGGING
	  5    PERMANENT
	  6    EXTENT MANAGEMENT LOCAL UNIFORM SIZE 1M
	  7    BLOCKSIZE 8K
	  8    SEGMENT SPACE MANAGEMENT MANUAL;

	表空间已创建。
	已用时间:  00: 00: 01.07

验证

	SQL> select tablespace_name,extent_management,allocation_type from dba_tablespaces 
		 where tablespace_name='ZIGZAG_TBS0';

	TABLESPACE_NAME  EXTENT_MANAGEMENT  ALLOCATION_TYPE
	---------------- -------------------- ------------------
	ZIGZAG_TBS0      LOCAL              UNIFORM

	已选择 1 行。
	已用时间:  00: 00: 00.06

创建表，指定存储参数

	SQL> create table staff (
	  2    staffno number(5),
	  3    job varchar2(200),
	  4    salary number(7, 2)
	  5  )
	  6  tablespace ZIGZAG_TBS0
	  7  storage(
	  8    INITIAL 2K      --第一个extent分配100K
	  9    NEXT 2K         --下一个extent大小
	 10    MINEXTENTS 2    --初始时分配2个extent
	 11    MAXEXTENTS 100  --最大
	 12    PCTINCREASE 60  --下一次分配时较已分配的的百分比
	 13  );

	表已创建。
	已用时间:  00: 00: 00.06

此处的Next，在第一次时为storage中指定，第三次及以后的大小计算方式为

	Next=前次已经分配的大小*(1+PCTINCREASE/100) 

可以通过user_tables来查看extent的分配情况。

	SQL> select 'init='|| initial_extent,'next='||next_extent,
		'min='||min_extents,'max='||max_extents,'pct='||pct_increase
		from user_tables where table_name='STAFF';
	--------------------------------------------------------
	init=24576  next=1048576  min=1  max=2147483645  pct=60

	已选择 1 行。
	已用时间:  00: 00: 00.00

## External Table

和普通的表很相像，但是oracle只保存了表的metadata，数据部分为外部操作系统的文件，因此只读只能select，不能做其他的DML。External Table一般用于数据的初始化加载工作。

通过下面的例子来演示如何通过External Table的创建来读取外部文件，并加载到数据库中的普通表中。

### 1，首先在操作系统中创建两个数据文本文件

staff1.txt

	100, 'CEO', 1000000.99
	101, 'CFO', 200000.99
	102, 'CIO', 100000.99
	103, 'CTO', 90000.99
	104, 'Director', 80000.99
	105, 'Manager', 70000.99
	106, 'Leader', 60000.99
	100, 'Member', 50000.99

staff2.txt

	200, 'Jerry', 99991000000.99
	201, 'Jim', 200000.99
	202, 'Tom', 100000.99
	203, 'Tommy', 90000.99
	204, 'Kate', 80000.99
	205, 'Joan', 70000.99
	206, 'Jack', 60000.99
	200, 'Apple', 50000.99

### 2，以sysdba身份创建文件目录并授权读写权限

	SQL> conn / as sysdba;  
	已连接。

	SQL> create or replace directory staff_data_dir
	  2    as 'D:\oracle\zigzag\staffdata';

	目录已创建。
	已用时间:  00: 00: 00.09

	SQL> create or replace directory staff_log_dir
	  2    as 'D:\oracle\zigzag\staffdata\log';

	目录已创建。
	已用时间:  00: 00: 00.00

	SQL> create or replace directory staff_baddata_dir
	  2    as 'D:\oracle\zigzag\staffdata\baddata';

	目录已创建。
	已用时间:  00: 00: 00.00

	SQL> grant read on directory staff_data_dir to myuser;

	授权成功。
	已用时间:  00: 00: 00.03

	SQL> grant write on directory staff_log_dir to myuser;

	授权成功。
	已用时间:  00: 00: 00.00

	SQL> grant write on directory staff_baddata_dir to myuser;

	授权成功。
	已用时间:  00: 00: 00.00

### 3，切换到schema用户，创建External Table，并通过ORACLE_LOADER读取外部文件

	SQL> connect myuser/oracle
	已连接。

	SQL> create table staff_external_tb
	  2  (
	  3    staffno number(5),
	  4    job varchar2(200),
	  5    salary number(7, 2)
	  6  )
	  7  organization external
	  8  (
	  9    type ORACLE_LOADER
	 10    default directory staff_data_dir
	 11    access parameters
	 12    (
	 13      records delimited by newline
	 14      badfile staff_baddata_dir:'bad%a_%p.txt'
	 15      logfile staff_log_dir:'log%a_%p.txt'
	 16      fields terminated by ','
	 17      missing field values are null
	 18      (staffno, job, salary)
	 19    )
	 20    location ('staff1.txt', 'staff2.txt')
	 21  )
	 22  parallel
	 23  reject limit unlimited;

	表已创建。
	已用时间:  00: 00: 00.01

注意到这个EXTERNAL TABLE的数据加载规则有一项是salary number(7, 2)，因此上述文件中的salary数字长度超过7的将被忽略并报错。

### 4，插入数据到实际表

切换当前session以并行方式工作

	SQL> alter session enable parallel DML;

	会话已更改。
	已用时间:  00: 00: 00.00

导入表

	SQL> insert into staff select * from staff_external_tb;

	已创建10行。

两个数据文件总共16条记录，导入数据库的有效记录为10个。

参看产生的baddata文件如下：

	100, 'CEO', 1000000.99
	101, 'CFO', 200000.99
	102, 'CIO', 100000.99
	200, 'Jerry', 99991000000.99
	201, 'Jim', 200000.99
	202, 'Tom', 100000.99

错误日志如下：

	error processing column SALARY in row 1 for datafile D:\oracle\zigzag\staffdata\staff1.txt
	ORA-01438: ?????????????
	error processing column SALARY in row 2 for datafile D:\oracle\zigzag\staffdata\staff1.txt
	ORA-01438: ?????????????
	error processing column SALARY in row 3 for datafile D:\oracle\zigzag\staffdata\staff1.txt
	ORA-01438: ?????????????
	error processing column SALARY in row 1 for datafile D:\oracle\zigzag\staffdata\staff2.txt
	ORA-01438: ?????????????
	error processing column SALARY in row 2 for datafile D:\oracle\zigzag\staffdata\staff2.txt
	ORA-01438: ?????????????
	error processing column SALARY in row 3 for datafile D:\oracle\zigzag\staffdata\staff2.txt
	ORA-01438: ?????????????

staff_external_tb并不真正持有数据内容，select * from staff_external_tb取得的数据是通过ORACLE_LOADER驱动读取外部文件而来。 除ORACLE_LOADER意外，Oracle还支持ORACLE_DATAPUMP，读取由expdp导出的dump二进制文件

## Temporary Table

临时表只存在于某个session或者transaction中，一旦session或者transaction结束，当前session或者transactoin表中的数据会自动清空。何时清空由ON COMMIT参数指定。session或者transaction之间的临时表是互相独立的。truncate一个临时表不会影响另外session中的临时表。表中的数据存储在当前用户的临时表空间，不驻数据文件。

* ON COMMIT DELETE ROWS：transaction一旦commit，oracle会truncate这个临时表
* ON COMMIT PRESERVE ROWS：session一旦结束，truncate临时表

举例：创建一个基于session的临时表

	SQL> create global temporary table staff_temp(
	  2    staffno number(5) primary key,
	  3    job varchar2(200),
	  4    salary number(7, 2)
	  5  )
	  6  on commit preserve rows;

	表已创建。
	已用时间:  00: 00: 00.17

临时表插入10行数据

	SQL> insert into staff_temp select * from staff_external_tb;

	已创建10行。
	已用时间:  00: 00: 00.45

注意到在当前session中，临时表记录有10笔。接下来断开session

断开session并重新连接

	SQL> conn
	请输入用户名:  myuser
	已连接。
	SQL> select count(*) from staff_temp;

	  COUNT(*)
	----------
			 0

	已选择 1 行。

	已用时间:  00: 00: 00.03

可见staff_temp已被truncate

## Index-Organized Table

有别于Heap-Organized，Index-Organized将数据和索引都保存在B-tree的叶子节点中，因此选择操作十分快，同时由于索引和数据存储在同一个B-Tree表中，磁盘大幅节省，而insert/delete/update将十分缓慢。比如insert操作，根据B-Tree组织，需要找到合适的位置插入数据，当无法占位时，需要在末端的叶子节点处创建一个额外的溢出块来存储，因此I/O比较慢。适合简单的通过主键来进行select的只读表。

创建Index-Organized Table，只需要通过参数 ORGANIZATION INDEX指明。