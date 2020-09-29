---
title: Oracle数据库学习-用户配置
author: Jie Chen
date: 2010-01-23
categories: [Oracle]
tags: [database]
---

在Oracle中用户的创建与属性、权限的配置极其简单，但是如何高效地创建、管理可共享的权限与角色，与架构设计一样，它也需要DBA具有全局的观念。

## 一、Profile属性

### 什么是Profile

Profile定义了用户对于Oracle资源访问的限制性使用，比如该用户可以允许输错多少次密码，可以同时有多少个session同时访问，连接db时的空闲等待时限等等。每个用户可以共享一个DEFAULT的Profile，也可以单独配置一个Profile，或共享一个自定的Profile。 通过如下命令查看当前版本的database支持的profile的类型：

	SQL> select distinct resource_name from dba_profiles;

	RESOURCE_NAME
	--------------------------------
	FAILED_LOGIN_ATTEMPTS
	COMPOSITE_LIMIT
	SESSIONS_PER_USER
	CPU_PER_CALL
	PASSWORD_REUSE_TIME
	PASSWORD_VERIFY_FUNCTION
	PASSWORD_LOCK_TIME
	LOGICAL_READS_PER_SESSION
	LOGICAL_READS_PER_CALL
	PRIVATE_SGA
	PASSWORD_REUSE_MAX
	CPU_PER_SESSION
	IDLE_TIME
	CONNECT_TIME
	PASSWORD_GRACE_TIME
	PASSWORD_LIFE_TIME

	16 rows selected.

### 创建Profile

Oracle提供了一个默认的Profile，即DEFAULT。如果没有特别声明，则所有的用户都使用该Profile

	select profile, resource_name, limit from dba_profiles where profile='DEFAULT';

接下来，创建我们自己的一个特定的Profile，比如要求每个用户至多只能有2个session，连接超时为30秒，修改密码至多有3次重复等，举例如下：

	SQL> create profile my_profile limit
		 sessions_per_user 2
		 connect_time 30
		 password_reuse_max 3;

	Profile created.

查看刚刚创建的my_profile，可以发现没有声明的其他Profile属性，都会采用DEFAULT Profile的定义，见如下:

	SQL> select profile, resource_name, limit from dba_profiles where profile='MY_PROFILE';

	PROFILE 		       RESOURCE_NAME			LIMIT
	------------------------------ ------------------------------------------------
	MY_PROFILE		       COMPOSITE_LIMIT			DEFAULT
	MY_PROFILE		       SESSIONS_PER_USER		2
	MY_PROFILE		       CPU_PER_SESSION			DEFAULT
	MY_PROFILE		       CPU_PER_CALL			DEFAULT
	MY_PROFILE		       LOGICAL_READS_PER_SESSION	DEFAULT
	MY_PROFILE		       LOGICAL_READS_PER_CALL		DEFAULT
	MY_PROFILE		       IDLE_TIME			DEFAULT
	MY_PROFILE		       CONNECT_TIME			30
	MY_PROFILE		       PRIVATE_SGA			DEFAULT
	MY_PROFILE		       FAILED_LOGIN_ATTEMPTS		DEFAULT
	MY_PROFILE		       PASSWORD_LIFE_TIME		DEFAULT
	MY_PROFILE		       PASSWORD_REUSE_TIME		DEFAULT
	MY_PROFILE		       PASSWORD_REUSE_MAX		3
	MY_PROFILE		       PASSWORD_VERIFY_FUNCTION		DEFAULT
	MY_PROFILE		       PASSWORD_LOCK_TIME		DEFAULT
	MY_PROFILE		       PASSWORD_GRACE_TIME		DEFAULT

	16 rows selected.

### 修改与删除Profile

修改Profile很简单，只需ALTER PROFILE命令即可。

删除Profile的命令需要说明，如果此Profile已被赋予了某个用户，删除后该用户的Profile则会被替换成系统默认的DEFAULT的Profile。具体命令如下（注意必须带CASCADE参数，即脱离所有关系）：

	DROP PROFILE my_profile CASCADE;

## 二、Privilege与Role

### 创建Role

多个用户可以共享一个Role，一个Role含多个权限，而权限的设置较为复杂多样，详情可以参看相应文档。

假设现在要创建一个自定义的Role：my_role，同时赋予一些系统权限：

	SQL> CREATE ROLE my_role NOT IDENTIFIED;

	Role created.

	SQL> GRANT CREATE TRIGGER,
		  CREATE ANY SYNONYM,
		  CREATE VIEW,
		  CREATE TABLE,
		  CREATE ROLLBACK SEGMENT,
		  DROP PUBLIC SYNONYM,
		  CREATE PROCEDURE,
		  CREATE SESSION,
		  ADMINISTER DATABASE TRIGGER,
		  CREATE INDEXTYPE,
		  CREATE OPERATOR,
		  CREATE PUBLIC SYNONYM,
		  ALTER SESSION,
		  CREATE SEQUENCE,
		  CREATE SYNONYM,
		  QUERY REWRITE,
		  CREATE TYPE,
		  EXECUTE ANY PROCEDURE,
		  DROP ANY SYNONYM
	TO my_role;

	Grant succeeded.

### 修改与删除

	ALTER ROLE ...
	DROP ROLE my_role;

## 三、User

### 创建用户

创建用户的需要如下参数：

* 设置用户名
* 验证方法（一般为密码）
* 默认的tablespace
* temporary的tablespace
* 空间配置
* 指定Profile
* 配置Role

如：


	SQL> CREATE USER myuser
	  IDENTIFIED BY oracle      --密码
	  DEFAULT TABLESPACE USERS  --使用系统默认的users表空间
	  TEMPORARY TABLESPACE TEMP --临时表空间
	  QUOTA UNLIMITED ON USERS  --空间大小
	  PROFILE my_profile;       --设定Profile

	User created.

接下来应该是配置Role

	SQL> GRANT my_role TO myuser;

	Grant succeeded.

### 删除Profile和Role

	--删除Role
	SQL> revoke my_role from myuser;

	Revoke succeeded.

	--删除Profile（即用DEFAULT替换）
	SQL> alter user myuser profile default;

	User altered.

### 查看User role

	--查看ROLE是否正确

	SQL> select * from dba_role_privs where grantee='MYUSER';

	GRANTEE 		       GRANTED_ROLE		      ADM DEF
	------------------------------ ------------------------------ --- ---
	MYUSER			       MY_ROLE			      NO  YES

	SQL> select * from dba_sys_privs where grantee='MY_ROLE';

	GRANTEE 		       PRIVILEGE				ADM
	------------------------------ ---------------------------------------- ---
	MY_ROLE 		       CREATE INDEXTYPE 			NO
	MY_ROLE 		       EXECUTE ANY PROCEDURE			NO
	MY_ROLE 		       CREATE VIEW				NO
	MY_ROLE 		       CREATE ANY SYNONYM			NO
	MY_ROLE 		       CREATE TRIGGER				NO
	MY_ROLE 		       ALTER SESSION				NO
	MY_ROLE 		       CREATE PROCEDURE 			NO
	MY_ROLE 		       DROP PUBLIC SYNONYM			NO
	MY_ROLE 		       DROP ANY SYNONYM 			NO
	MY_ROLE 		       CREATE ROLLBACK SEGMENT			NO
	MY_ROLE 		       ADMINISTER DATABASE TRIGGER		NO
	MY_ROLE 		       CREATE OPERATOR				NO
	MY_ROLE 		       CREATE PUBLIC SYNONYM			NO
	MY_ROLE 		       CREATE TABLE				NO
	MY_ROLE 		       CREATE SEQUENCE				NO
	MY_ROLE 		       QUERY REWRITE				NO
	MY_ROLE 		       CREATE TYPE				NO
	MY_ROLE 		       CREATE SYNONYM				NO
	MY_ROLE 		       CREATE SESSION				NO

	19 rows selected.


至此，用户myuser创建完毕，权限与属性也分配完成。