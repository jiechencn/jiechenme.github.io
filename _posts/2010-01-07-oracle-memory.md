---
title: Oracle数据库学习-内存管理
author: Jie Chen
date: 2010-01-07
categories: [Oracle]
tags: [database]
---

Oracle提供了对内存的自动和手动两种管理方式，本文通过简单的实例说明两种方式下对SGA/PGA的设置。

## 一、SGA与PGA

### SGA

SGA是一组共享内存结构，包含了database的一个实例的所有数据和控制信息，SGA由多个服务和background的进程共享。

### PGA

PGA是对应一个服务进程的内存区域，一个PGA对应一个服务进程，进程之间不可共享同一个PGA。 用Oracle提供的一个示意图更能很好地说明PGA/SGA的区别和各自的领域。

![](/assets/res/oracle_dba_intro_4_sgapga.jpg)
  
## 二、自动内存管理

在使用DBCA创建数据库的时候，Oracle会提示你是否采用自动内存管理方式。如果之前使用的是手动方式，则可以通过下面的步骤重新启用自动的方式。 启用自动方式很简单，主要就是设置初始化参数MEMORY_TARGET。实例会根据设置的MEMORY_TARGET自动为SGA和PGA分配各自的内存。MEMORY_TARGET的动态修改不需要设计数据库的重启，但是不能一下子设的过大。

具体步骤如下：

### 1. 检查LOCK_SGA 检查pfile或者spfile的LOCK_SGA，确保其为false

以下均假设oracle从spfile启动

	SQL> show parameter sga;

	NAME				     TYPE	 VALUE
	------------------------------------ ----------- ------------------------------
	lock_sga			     boolean	 FALSE
	pre_page_sga			     boolean	 FALSE
	sga_max_size			     big integer 512M
	sga_target			     big integer 0

### 2. 检查SGA_TARGET 和PGA_AGGREGATE_TARGET

	SQL> show parameter target;

	NAME				     TYPE	 VALUE
	------------------------------------ ----------- ------------------------------
	archive_lag_target		     integer	 0
	db_flashback_retention_target	     integer	 1440
	fast_start_io_target		     integer	 0
	fast_start_mttr_target		     integer	 0
	memory_max_target		     big integer 820M
	memory_target			     big integer 820M
	pga_aggregate_target		     big integer 0
	sga_target			     big integer 0

### 3. 检查已分配的PGA的大小

	SQL> select value from v$pgastat where name='maximum PGA allocated';

		 VALUE
	----------
	  14633984

### 4. 计算memory_target的大小

	memory_target = sga_target + max(pga_aggregate_target, maximum PGA allocated)
	memory_target的大小可以由此得出，或者也可以设置为稍大于该值的一个数值

### 5. 修改参数

	ALTER SYSTEM SET MEMORY_TARGET = nM;
	ALTER SYSTEM SET SGA_TARGET = 0;
	ALTER SYSTEM SET PGA_AGGREGATE_TARGET = 0;

### 6. MEMORY_MAX_TARGET

MEMORY_MAX_TARGET是可选的，它可以和MEMORY_TARGET一样大小，也可以比它大。如果设置为小于MEMORY_TARGET，oracle会自动调整。MEMORY_MAX_TARGET的设置需要重启才有效，因为它并不是一个动态的初始化参数，所以必须指定scope。

如：

	ALTER SYSTEM SET MEMORY_MAX_TARGET = 1000M SCOPE = SPFILE;

### 7. 自动内存管理的优化

oracle提供了v$memory_target_advice的视图对当前的内存分配给出一个建议。

	SQL> select * from v$memory_target_advice order by memory_size;
	MEMORY_SIZE MEMORY_SIZE_FACTOR ESTD_DB_TIME ESTD_DB_TIME_FACTOR VERSION
	----------- ------------------ ------------ ------------------- ----------
	180 		.5 		458 		1.344		0
	270		.75 		367 		1.0761 		0
	360 		1 		341 		1 		0
	450 		1.25 		335 		.9817 		0
	540 		1.5 		335 		.9817 		0
	630 		1.75 		335 		.9817 		0
	720 		2 		335 		.9817 		0

分析如上的结果，可以得知，当采用内存因子Factor为1.25的时候，内存处于最优的状态，此时完成一个workload用时为335个单位。因此可以采用450M内存作为memory_target

## 三、手动管理内存

手动的管理并不是100%的手动，在一定程度上也还保留着部分的自动管理。手动管理根据SGA/PGA包含如下的管理方式

* 针对SGA的自动共享内存管理
* 针对SGA的手动共享内存管理
* 针对PGA的自动内存管理
* 针对PGA的手动内存管理

### 针对SGA的自动共享内存管理

自动共享内存管理的方式简化了SGA的内存管理，只需要设置初始化参数SGA_TARGET，oracle会根据实际内存需要给不同的SGA的component分配足够的内存。除了设置SGA_TARGET之后，还需要设置SGA的各个component的内存，可以全部将他们设为0，也可以部分地设置。这些组件如下：

![](/assets/res/oracle_dba_intro_4_sga_comp.jpg)

设置如下：

#### 1. 如果是从手动共享内存管理切换到自动共享内存管理，则先获取SGA_TARGET的大小

	SQL> SELECT ((SELECT SUM(value) FROM V$SGA) -(SELECT CURRENT_SIZE FROM V$SGA_DYNAMIC_FREE_MEMORY)) SGA_TARGET FROM DUAL;

	SGA_TARGET
	----------
	 535662592
 
然后再在pfile或者spfile中设置SGA_TARGET

	ALTER SYSTEM SET SGA_TARGET=value

如果是从自动内存管理切换到自动共享内存管理，则先将MEMORY_TARGET设为0，oracle会自动将已分配的SGA作为SGA_TARGET的值。

	ALTER SYSTEM SET MEMORY_TARGET = 0;

#### 2. 设置SGA的组件内存的大小，可全为0，也可部分设置

	ALTER SYSTEM SET SHARED_POOL_SIZE = 0;
	ALTER SYSTEM SET LARGE_POOL_SIZE = 0;
	ALTER SYSTEM SET JAVA_POOL_SIZE = 0;
	ALTER SYSTEM SET DB_CACHE_SIZE = 0;

### 针对SGA的手动共享内存管理

手动共享内存管理必须将MEMORY_TARGET和SGA_TARGET全部设置为0，并且，要为SGA的每一个组件根据其自身特点分配合适的内存。以后对这个主题做深入的探讨。

### 针对PGA的自动内存管理

默认情况下，oracle会自动地对实例PGA自动管理，你可以设置PGA_AGGREGATE_TARGET的参数大小，oracle会限制实例PGA中所有服务进程和background的进程的内存组合都不会超过该值。在使用DBCA创建数据库的同时，PGA_AGGREGATE_TARGET会要求指定一个合适的大小，如果没有指定，oracle会自己选择一个恰当的值。

通过v$process可以查阅各个服务进程的内存分配情况

	SQL> select pid, spid, pga_used_mem, pga_alloc_mem, pga_max_mem  from v$process;

		   PID SPID 		    PGA_USED_MEM PGA_ALLOC_MEM PGA_MAX_MEM
	---------- ------------------------ ------------ ------------- -----------
		 1				       0	     0		 0
		 2 236				  437894	716366	    716366
		 3 4120 			  437082	716366	    716366
		 4 6500 			  433902	716366	    716366
		 5 1576 			  510246	781902	    781902
		 6 2084 			  437082	716366	    716366
		 7 5432 			  437082	716366	    716366
		 8 2012 			  453298	699338	   1223626
		 9 1864 			 2211450       3542754	   3542754
		10 4492 			 4662386       9629262	  10284622
		11 3600 			  480950       1002818	   1002818

### 针对PGA的手动内存管理

过于复杂，不推荐使用