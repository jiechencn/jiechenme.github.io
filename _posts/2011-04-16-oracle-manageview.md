---
title: Oracle数据库学习-视图的写操作
author: Jie Chen
date: 2011-04-16
categories: [Oracle]
tags: [database]
---

对于视图的操作，我们最常见的是创建并使用select操作符，但其实视图也是可写的，只是可写的条件比较苛刻。视图写操作实际上是针对视图定义中最外层from从句中基表的操作。本文详细举例说明在各种条件下视图的insert，update以及delete操作的实验。

## 准备工作

在开始之前，我们先准备两个表和各自的数据，两个表用主外键相互引用。

	create table class(
	  cno number(4) primary key,
	  cname varchar2(10),
	  loc varchar2(10)
	);

	create table student(
	  sno number(4) primary key,
	  sname varchar2(10),
	  age number(2),
	  cno number(4),
	  foreign key (cno) references class(cno)
	);


	insert into class values (101, '1-1', 'building 1');
	insert into class values (102, '1-2', 'building 1');
	insert into class values (103, '1-3', 'building 1');
	insert into class values (201, '2-1', 'building 2');
	insert into class values (202, '2-2', 'building 2');
	insert into class values (203, '2-3', 'building 2');
	insert into class values (301, '3-1', 'building 3');
	insert into class values (302, '3-2', 'building 3');
	insert into class values (303, '3-3', 'building 3');
	commit;

	insert into student values (1, 'stu_1', 10, 101);
	insert into student values (2, 'stu_2', 10, 101);
	insert into student values (3, 'stu_3', 10, 102);
	insert into student values (4, 'stu_4', 10, 102);
	insert into student values (5, 'stu_5', 10, 103);
	insert into student values (6, 'stu_6', 10, 103);
	insert into student values (7, 'stu_7', 10, 201);
	insert into student values (8, 'stu_8', 10, 201);
	insert into student values (9, 'stu_9', 10, 202);
	insert into student values (10, 'stu_10', 10, 202);
	insert into student values (11, 'stu_11', 10, 203);
	insert into student values (12, 'stu_12', 10, 203);
	insert into student values (13, 'stu_13', 10, 301);
	insert into student values (14, 'stu_14', 10, 301);
	insert into student values (15, 'stu_15', 10, 302);
	insert into student values (16, 'stu_16', 10, 302);
	insert into student values (17, 'stu_17', 10, 303);
	insert into student values (18, 'stu_18', 10, 303);
	commit;

## 基于单个表的视图

我们先创建一个只有一个基本表的视图，通过条件 where cno=101过滤。同时我们加上限定属性 with check option constraint

	SQL> create view v_stu_1 as
	  2    select sno, sname, cno from student where cno=101
	  3    with check option constraint stu_1_const;

	视图已创建。


查看此视图的数据

	SQL> select * from v_stu_1;

		   SNO SNAME                       CNO
	---------- -------------------- ----------
			 1 stu_1                       101
			 2 stu_2                       101

接下来我们对该视图进行两条insert操作。

	SQL> insert into v_stu_1 values (101, 'stu_100', 101);

	已创建 1 行。

	SQL> commit;

	提交完成。

	SQL> insert into v_stu_1 values (102, 'stu_100', 102);
	insert into v_stu_1 values (102, 'stu_100', 102)
				*
	第 1 行出现错误:
	ORA-01402: 视图 WITH CHECK OPTIDN where 子句违规

以上两条一样的insert的操作有不同的执行结果，取决于限定条件 with check option constraint。它的作用就是当新插入的数据行如果能够被包含于视图本身的结果集中，那么该语句允许执行，否则语句违规。视图v_stu_1定义中的条件限定为where cno=101，如果新插入的insert语句中cno也等于101，那么允许执行，第二条insert语句插入的cno为102，不在视图的结果集中，因此被禁止。

如果要求视图绝对禁止写操作，可以加上限定从句 with read only。如下所示。

	SQL> create view v_stu_2 as
	  2    select sno, sname, cno from student where cno=101
	  3    with read only;

	视图已创建。

	SQL> insert into v_stu_2 values (103, 'stu_100', 101);
	insert into v_stu_2 values (103, 'stu_100', 101)
	*
	第 1 行出现错误:
	ORA-01733: 此处不允许虚拟列

有时候出于一些特殊的需要，我们可能要创建一些带错误的视图，比如该视图基于一个不存在的表的字段。为保证视图创建成功，我们使用FORCE从句，这样视图创建可以保证成功，但是视图处于invalid的状态。当不存在的字段创立以后，然后可以通过recompile的方式重新编译视图，确保视图有效。比如以下例子。

	SQL> create force view v_stu_3 as select sno, sname, cno, sex from student where cno=101;

	警告: 创建的视图带有编译错误。

	SQL> select object_type, object_name, status from user_objects where object_name=upper('v_stu_3');

	OBJECT_TYPE     OBJECT_NAME    STATUS
	--------------------------------------
	VIEW            V_STU_3        INVALID


	SQL> alter table student add sex varchar2(1);

	表已更改。

重新编译视图

	SQL> alter view v_stu_3 compile;

	视图已变更。

重新查看视图状态

	SQL> select object_type, object_name, status from user_objects where object_name=upper('v_stu_3');

	OBJECT_TYPE     OBJECT_NAME    STATUS
	--------------------------------------
	VIEW            V_STU_3        VALID

修改视图，我们可以通过replace的方式，比如下面的例子，修改已经创建的视图v_stu_2

	SQL> create view v_stu_2 as
	  2    select sno, sname, cno from student where cno=101
	  3    with read only;

	视图已创建。

	SQL> create or replace view v_stu_2 as
	  2  select sno, sname, sex from student where cno=101
	  3  with check option constraint stu_2_const;

	视图已创建。

	SQL> desc v_stu_2;
	 名称                 是否为空? 类型
	 ------------------- -------- ----------------------------
	 SNO                  NOT NULL NUMBER(4)
	 SNAME                         VARCHAR2(10 CHAR)
	 SEX                           VARCHAR2(1 CHAR)

	SQL> select view_name, text from user_views where view_name='V_STU_2';

	VIEW_NAME    TEXT
	--------------------------------------------------------------------------------------------
	V_STU_2      select sno, sname, sex from student where cno=101 with check option

从上面的一些例子中可以看到视图也是可以被insert新行的，新行将会被插入到基表中去。一个视图能否被插入必须遵循下面的规则

* 如果view的定义使用了SET、distinct操作符，或者group by 从句，那么不同通过该视图进行基本的insert
* 如果视图使用了with check option，那么被insert的行必须能够被该视图select出来。
* 如果一个非空字段没有指定默认default值，当这个非空字段不在视图定义的select从句中，不能insert
* 当视图定义视图了函数，将不能insert

视图同样也可以被update或者delete，能否被update或者delete同样适用上面的规则，比如

	SQL> delete from v_stu_1 where sno=1;

	已删除 1 行。

	SQL> commit;

	提交完成。


	SQL> select * from v_stu_1;

		   SNO SNAME                       CNO
	---------- -------------------- ----------
			 2 stu_2                       101
		   100 stu_100                     101
		   101 stu_100                     101

	SQL> update v_stu_1 set sname='stu_2_2' where sno=2;

	已更新 1 行。

	SQL> commit;

	提交完成。


	SQL> select * from v_stu_1;

		   SNO SNAME                       CNO
	---------- -------------------- ----------
			 2 stu_2_2                     101
		   100 stu_100                     101
		   101 stu_100                     101

查看视图的字段能否可写，可以查看user_updatable_columns视图，比如：

	SQL> select column_name, updatable, insertable, deletable from user_updatable_columns where table_name='V_STU_1'

	COLUMN_NAME  UPDATA INSERT DELETA
	------------------------------------------------------------------ ----
	SNO          YES    YES   YES
	SNAME         YES    YES   YES
	CNO           YES    YES   YES

## 基于多个表的视图

开始之前，先说明一下什么叫做key-preserved表。当一个表中的所有的主键，都在视图的定义中也作为主键，那么这个表就称为key-preserved表。

对于基于多表join的视图的写操作，条件限制比较复杂且理解起来比较困难，它们必须遵循如下的基本规则

对于视图的insert、update、delete操作只允许一次修改一个基本表，即不能同时修改多个表。
update规则：视图中的字段如果属于key-preserved的表的字段，才允许被update。如果视图定义有with check option，则where条件中的join连接字段以及重复表的所有字段都不能被update
delete规则：连接表中仅有一个key-preserved表，则只允许delete该key-preserved表中的数据行。假设视图的from从句中有形如from student a, student b的形式，也符合“仅有一个”key-preserved的条件，但with check option除外。
insert规则：可以对key-preserved进行insert，但with check option除外。
分别举例。我们先创建join两个table的视图

	SQL> create view v_stu_class_1 as
	  2    select s.cno, c.cname, s.sno, s.sname, s.age
	  3    from student s, class c
	  4    where s.cno=c.cno and c.cno=301;

	视图已创建。

	SQL> create view v_stu_class_2 as
	  2    select s.cno, c.cname, s.sno, s.sname, s.age
	  3    from student s, class c
	  4    where s.cno=c.cno and c.cno=301
	  5    with check option constraint stu_cls_2_const;

	视图已创建。

检查两个视图各自字段的可写性。

	SQL> select column_name, updatable, insertable, deletable from user_updatable_columns where table_name='V_STU_CLASS_1';

	COLUMN_NAME       UPDATA INSERT DELETA
	--------------------------------------------
	CNO               YES    YES    YES   <---
	CNAME             NO     NO     NO
	SNO               YES    YES    YES
	SNAME             YES    YES    YES
	AGE               YES    YES    YES


	SQL> select column_name, updatable, insertable, deletable from user_updatable_columns where table_name='V_STU_CLASS_2';

	COLUMN_NAME       UPDATA INSERT DELETA
	--------------------------------------------
	CNO               NO     NO     NO   <---注意这里
	CNAME             NO     NO     NO   <---
	SNO               YES    YES    YES
	SNAME             YES    YES    YES
	AGE               YES    YES    YES

### Update操作

开始对两个视图分别进行update操作。

	SQL> select * from v_stu_class_1;

		   CNO CNAME                       SNO SNAME                       AGE
	---------- -------------------- ---------- -------------------- ----------
		   301 3-1                          13 stu_13                       13
		   301 3-1                          14 stu_14                       10

	SQL> update v_stu_class_1 set cname='3-2', sno=1302, sname='stu_1302', age=16 where sno=13;
	update v_stu_class_1 set cname='3-2', sno=1302, sname='stu_1302', age=16 where sno=13
							 *
	第 1 行出现错误:
	ORA-01779: 无法修改与非键值保存表对应的列

此处不能执行是因为cname来自于class表，此表不是key-preserved表。

	SQL> update v_stu_class_1 set sno=1302, sname='stu_1302', age=16 where sno=13;

	已更新 1 行。

	SQL> commit;

	提交完成。

	SQL> select * from v_stu_class_1;

		   CNO CNAME                       SNO SNAME                       AGE
	---------- -------------------- ---------- -------------------- ----------
		   301 3-1                        1302 stu_1302                     16
		   301 3-1                          14 stu_14                       10

	SQL> update v_stu_class_2 set cno=302, sno=1402, sname='stu_1402', age=16 where sno=14;
	update v_stu_class_2 set cno=302, sno=1402, sname='stu_1402', age=16 where sno=14
							 *
	第 1 行出现错误:
	ORA-01733: 此处不允许虚拟列

此句不能执行，是由于cno是视图定义中的where条件中用到了它，它成为连接字段

	SQL> update v_stu_class_1 set cno=302, sno=1402, sname='stu_1402', age=16 where sno=14;

	已更新 1 行。

	SQL> commit;

	提交完成。

	SQL> select * from v_stu_class_1;

		   CNO CNAME                       SNO SNAME                       AGE
	---------- -------------------- ---------- -------------------- ----------
		   301 3-1                        1302 stu_1302                     16

	SQL> select * from v_stu_class_2;

		   CNO CNAME                       SNO SNAME                       AGE
	---------- -------------------- ---------- -------------------- ----------
		   301 3-1                        1302 stu_1302                     16

	SQL> select * from student where sno=1402;

		   SNO SNAME                       AGE        CNO SE
	---------- -------------------- ---------- ---------- --
		  1402 stu_1402                     16        302

	SQL>

### Delete操作

上述是update操作演示了视图更新的严格的规则，下面看delete操作。

	SQL> create view v_c_c_1 as
	  2  select a.cno acno, b.cno bcno, a.cname aname, b.cname bname from class a, class b
	  3  where a.cno=b.cno;

	视图已创建。

	SQL>
	SQL> create view v_c_c_2 as
	  2  select a.cno acno, b.cno bcno, a.cname aname, b.cname bname from class a, class b
	  3  where a.cno=b.cno
	  4  with check option;

	视图已创建。

视图v_c_c_1虽然有两个key-preserved表，但他们都是class同一个表，因此该视图认作只含有一个key-preserved表。

视图v_c_c_2虽然也只有一个key-preserved表，但它的定义具有with check option限定，

测试delete之前先给class插入一个新记录，以免删除时引起“违反约束条件”的错误，因为student具有一个外键指向class的cno。

	SQL> insert into class values (401, '4-1', 'suzhou');

	已创建 1 行。

	SQL> commit;

	提交完成。

	SQL> select * from v_c_c_1;

		  ACNO       BCNO ANAME                BNAME
	---------- ---------- -------------------- --------------------
		   401        401 4-1                  4-1
		   101        101 1-1                  1-1
		   102        102 1-2                  1-2
		   103        103 1-3                  1-3
		   201        201 2-1                  2-1
		   202        202 2-2                  2-2
		   203        203 2-3                  2-3
		   301        301 3-1                  3-1
		   302        302 3-2                  3-2
		   303        303 3-3                  3-3

	已选择10行。

	SQL> select * from v_c_c_2;

		  ACNO       BCNO ANAME                BNAME
	---------- ---------- -------------------- --------------------
		   401        401 4-1                  4-1
		   101        101 1-1                  1-1
		   102        102 1-2                  1-2
		   103        103 1-3                  1-3
		   201        201 2-1                  2-1
		   202        202 2-2                  2-2
		   203        203 2-3                  2-3
		   301        301 3-1                  3-1
		   302        302 3-2                  3-2
		   303        303 3-3                  3-3

	已选择10行。

上述两个视图具有同样的select结果集，但定义完全不同。下面开始delete实验。

	SQL> delete from v_c_c_1 where acno=401;

	已删除 1 行。

	SQL> rollback;

	回退已完成。

	SQL> delete from v_c_c_2 where acno=401;
	delete from v_c_c_2 where acno=401
				*
	第 1 行出现错误:
	ORA-01752: 不能从没有一个键值保存表的视图中删除

### Insert操作

下面演示insert操作。

	SQL> select * from v_stu_class_1;

		   CNO CNAME                       SNO SNAME                       AGE
	---------- -------------------- ---------- -------------------- ----------
		   301 3-1                        1302 stu_1302                     16


	SQL> insert into v_stu_class_1(cno, sno, sname, age) values (301, 1303, 'stu_1303', 16);

	已创建 1 行。

	SQL> rollback;

	回退已完成。

同样的操作针对v_stu_class_2，则不允许，因为该视图定义具有with check option限定

	SQL> insert into v_stu_class_2(cno, sno, sname, age) values (301, 1303, 'stu_1303', 16);
	insert into v_stu_class_2(cno, sno, sname, age) values (301, 1303, 'stu_1303', 16)
							  *
	第 1 行出现错误:
	ORA-01733: 此处不允许虚拟列

再次对v_stu_class_1操作，此时我们对cname字段也插入值，得到的错误提示一次写操作只能针对一个基表。

	SQL> insert into v_stu_class_1 values (301, '3-1', 1303, 'stu_1303', 16);
	insert into v_stu_class_1 values (301, '3-1', 1303, 'stu_1303', 16)
	*
	第 1 行出现错误:
	ORA-01776: 无法通过联接视图修改多个基表

其他：视图的drop很简单。