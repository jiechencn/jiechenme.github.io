---
title: Oracle文件 - datafile与extent扩展的分析
author: Jie Chen
date: 2014-11-20
categories: [Oracle]
tags: []
---

花了比较长的时间一点点做实验，总算初步搞清楚了数据文件和extent扩展的关系。下面详细地记录extent区间是如何扩展填充数据文件的。

先建立三个不同的tablespace

	CREATE TABLESPACE xwiz01 
		DATAFILE '/u01/app/oracle/oradata/orcl/xwiz01.dbf' SIZE 1M
		AUTOEXTEND ON NEXT 1M EXTENT MANAGEMENT LOCAL SEGMENT SPACE MANAGEMENT AUTO;

	CREATE TABLESPACE xwiz02
		DATAFILE '/u01/app/oracle/oradata/orcl/xwiz02.dbf' SIZE 1M
		AUTOEXTEND ON NEXT 1M EXTENT MANAGEMENT LOCAL SEGMENT SPACE MANAGEMENT AUTO UNIFORM SIZE 128K;
		
	CREATE TABLESPACE xwiz03
		DATAFILE '/u01/app/oracle/oradata/orcl/xwiz03.dbf' SIZE 1M
		AUTOEXTEND ON NEXT 1M EXTENT MANAGEMENT LOCAL SEGMENT SPACE MANAGEMENT AUTO UNIFORM SIZE 512K;
	
Extent 选择AUTOALLOCATE 、UNIFORM 128K和UNIFORM 512K三种情况。

三个tablespace创建后，检查他们的空间大小。
 
	SET LINESIZE 300;
	COLUMN file_name FORMAT a40;
	COLUMN db_block_size FORMAT a15;

	SELECT file_id, file_name, blocks, bytes, user_blocks, user_bytes, increment_by, (SELECT value FROM v$parameter WHERE name='db_block_size') as db_block_size 
	FROM dba_data_files WHERE tablespace_name in ('XWIZ01', 'XWIZ02', 'XWIZ03');

	FILE_ID FILE_NAME                              BLOCKS      BYTES USER_BLOCKS USER_BYTES INCREMENT_BY DB_BLOCK_SIZE
	---------- ------------------------------- ---------- ---------- ----------- ---------- ------------ -------------
	5 /u01/app/oracle/oradata/orcl/xwiz01.dbf         128    1048576         120     983040          128 8192
	6 /u01/app/oracle/oradata/orcl/xwiz02.dbf         128    1048576         112     917504          128 8192
	7 /u01/app/oracle/oradata/orcl/xwiz03.dbf         128    1048576          64     524288          128 8192


这里的字段的 计算公式如下： 
> * BYTES = CREATE TABLESPACE 声明的SIZE （这里是1M） = BLOCKS * DB_BLOCK_SIZE   -> datafile文件所占的空间
> * USER_BYTES = USER_BLOCKS * DB_BLOCK_SIZE   ->用户可用的数据空间
> * INCREMENT_BY = AUTOEXTEND ON NEXT xx -> 这里的例子是表空间自动增长 1M = 1024*1024 = 128 * DB_BLOCK_SIZE

为什么 xwiz01，xwiz02和xwiz03 的初始的user_blocks是120，112，64块呢？因为BYTES和USER_BYTES的差，按照Oracle官方说法是保存的datafile related metadata。

> * File# 5: metadata = 1048576 - 983040 = 65536 = 64K = 8 * db_block_size = 1 extent (AUTOALLOCATE)
> * File# 6: metadata = 1048576 - 917504 = 131072 = 128K = 16 * db_block_size = 1 extent (UNIFORM SIZE 128k)
> * File# 7: metadata = 1048576 - 524288 = 524288 = 512K = 64 * db_block_size = 1 extent (UNIFORM SIZE 512k)

所以，这三个文件的初始可用空间分别少了1个extent，只是他们的extent大小不等，分别是8，16和64个db_block_size。

BYTES的大小在这里都是1048576字节。而datafile的真正的物理文件的大小都是1056768，数量上相差一个DB_BLOCK_SIZE 8192个字节。

	[oracle@localhost orcl]$ ls -l xwiz*
	-rw-r-----. 1 oracle oracle 1056768 Nov 20 12:10 xwiz01.dbf
	-rw-r-----. 1 oracle oracle 1056768 Nov 20 12:10 xwiz02.dbf
	-rw-r-----. 1 oracle oracle 1056768 Nov 20 12:10 xwiz03.dbf

按照v$datafile的视图说明，有一个BLOCK1_OFFSET偏移量，保存的是OS系统信息。Oracle数据写物理文件的时候需要往后偏移BLOCK1_OFFSET数量才真正写入数据。这个BLOCK1_OFFSET的大小是一个物理快DB_BLOCK_SIZE=8192 bytes。所以实际物理文件大小比create tablespace声明的size要大一个偏移量数量(8192)。

公式为：

> physical filesize = bytes + block1_offset

相应第，查询v$datafile时，自定义个real_file_size就能反映出区别。

	SELECT file#, CREATE_BYTES, blocks, bytes, block1_offset, bytes + block1_offset AS real_file_size FROM v$datafile WHERE file# IN (5, 6, 7);

		 FILE# CREATE_BYTES     BLOCKS      BYTES BLOCK1_OFFSET REAL_FILE_SIZE
	---------- ------------ ---------- ---------- ------------- --------------
			 5      1048576        128    1048576          8192        1056768
			 6      1048576        128    1048576          8192        1056768
			 7      1048576        128    1048576          8192        1056768


由于这个时候，还没有在segment上建立对象（比如表、索引等），所以dba_extents视图中的extent没有任何扩展信息。

	select tablespace_name, EXTENT_ID, FILE_ID, BLOCK_ID, BYTES, BLOCKS from dba_extents where tablespace_name in ('XWIZ01', 'XWIZ02', 'XWIZ03');
	
	no rows selected



接下来分别给三个tablespace各自创建一个表插入一百万行数据，看一下extent的扩展。


	CREATE TABLE T1(
	name VARCHAR2(50)
	)
	TABLESPACE xwiz01
	/

	CREATE TABLE T2(
		name VARCHAR2(50)
	)
	TABLESPACE xwiz02
	/

	CREATE TABLE T3(
		name VARCHAR2(50)
	)
	TABLESPACE xwiz03
	/

	BEGIN
		FOR i in 1..1000000
		LOOP
			INSERT INTO T1 VALUES ('value_' || i);
			INSERT INTO T2 VALUES ('value_' || i);
			INSERT INTO T3 VALUES ('value_' || i);
		END LOOP;
		COMMIT;
	END;
	/

数据文件信息相应增大

	SELECT file_id, file_name, blocks, bytes, user_blocks, user_bytes, increment_by, (SELECT value FROM v$parameter WHERE name='db_block_size') as db_block_size 
	FROM dba_data_files WHERE tablespace_name IN ('XWIZ01', 'XWIZ02', 'XWIZ03');
			 
	FILE_ID FILE_NAME                              BLOCKS      BYTES USER_BLOCKS USER_BYTES INCREMENT_BY DB_BLOCK_SIZE
	---------- ------------------------------- ---------- ---------- ----------- ---------- ------------ -------------
	5 /u01/app/oracle/oradata/orcl/xwiz01.dbf        2816   23068672        2808   23003136          128 8192
	6 /u01/app/oracle/oradata/orcl/xwiz02.dbf        2688   22020096        2672   21889024          128 8192
	7 /u01/app/oracle/oradata/orcl/xwiz03.dbf        2816   23068672        2752   22544384          128 8192




datafile的metadata没有所变化（？）

> * File# 5: metadata = 23068672 - 23003136 = 65536
> * File# 6: metadata = 20971520 - 20840448 = 131072
> * File# 7: metadata = 23068672 - 22544384 = 524288

查看物理文件的实际大小，和BYTES还是对应不上。因为他们之间差了一个DB_BLOCK_SIZE。

	ls -l xwiz*

	-rw-r-----. 1 oracle oracle 23076864 Nov 20 12:33 xwiz01.dbf
	-rw-r-----. 1 oracle oracle 22028288 Nov 20 12:33 xwiz02.dbf
	-rw-r-----. 1 oracle oracle 23076864 Nov 20 12:30 xwiz03.dbf

SQL确认

	SELECT file#, CREATE_BYTES, blocks, bytes, block1_offset, bytes + block1_offset AS real_file_size FROM v$datafile WHERE file# IN (5, 6, 7);

		 FILE# CREATE_BYTES     BLOCKS      BYTES BLOCK1_OFFSET REAL_FILE_SIZE
	---------- ------------ ---------- ---------- ------------- --------------
			 5      1048576       2816   23068672          8192       23076864
			 6      1048576       2688   22020096          8192       22028288
			 7      1048576       2816   23068672          8192       23076864
		 
接下来看每个tablespace的extent的分配。

	select tablespace_name, EXTENT_ID, FILE_ID, BLOCK_ID, BYTES, BLOCKS from dba_extents where tablespace_name in ('XWIZ01');

	TABLESPACE  EXTENT_ID    FILE_ID   BLOCK_ID      BYTES     BLOCKS
	---------- ---------- ---------- ---------- ---------- ----------
	XWIZ01              0          5          8      65536          8
	XWIZ01              1          5         16      65536          8
	XWIZ01              2          5         24      65536          8
	XWIZ01              3          5         32      65536          8
	XWIZ01              4          5         40      65536          8
	XWIZ01              5          5         48      65536          8
	XWIZ01              6          5         56      65536          8
	XWIZ01              7          5         64      65536          8
	XWIZ01              8          5         72      65536          8
	XWIZ01              9          5         80      65536          8
	XWIZ01             10          5         88      65536          8
	XWIZ01             11          5         96      65536          8
	XWIZ01             12          5        104      65536          8
	XWIZ01             13          5        112      65536          8
	XWIZ01             14          5        120      65536          8
	XWIZ01             15          5        128      65536          8
	XWIZ01             16          5        256    1048576        128
	XWIZ01             17          5        384    1048576        128
	XWIZ01             18          5        512    1048576        128
	XWIZ01             19          5        640    1048576        128
	XWIZ01             20          5        768    1048576        128
	XWIZ01             21          5        896    1048576        128
	XWIZ01             22          5       1024    1048576        128
	XWIZ01             23          5       1152    1048576        128
	XWIZ01             24          5       1280    1048576        128
	XWIZ01             25          5       1408    1048576        128
	XWIZ01             26          5       1536    1048576        128
	XWIZ01             27          5       1664    1048576        128
	XWIZ01             28          5       1792    1048576        128
	XWIZ01             29          5       1920    1048576        128
	XWIZ01             30          5       2048    1048576        128
	XWIZ01             31          5       2176    1048576        128
	XWIZ01             32          5       2304    1048576        128
	XWIZ01             33          5       2432    1048576        128
	XWIZ01             34          5       2560    1048576        128

	35 rows selected.


XWIZ01文件初始大小为1M，extent采用AUTOALLOCATE自动分配，初始为65536字节，所以extent每次增长64K(默认，8个DB_BLOCK_SIZE)，等bytes增长到65536 * 16=1M（初始文件大小）后，extent根据算法调整为1M，按照128个DB_BLOCK_SIZE（=1M）增长。所以总的BYTES大小是

> * 65536 * 16 + 1048576 * 19 = 20971520


总的blocks数是2560：

	select tablespace_name, sum(BYTES), sum(BLOCKS) from dba_extents where tablespace_name in ('XWIZ01') group by tablespace_name;

	TABLESPACE SUM(BYTES) SUM(BLOCKS)
	---------- ---------- -----------
	XWIZ01       20971520        2560

但是会发现总的extent所占用的sum(bytes)是 20971520 ，而不是 23068672，也不是 23003136 。

实际上：

> * 操作系统的datafile filesize: 实际的物理文件大小 = BLOCK1_OFFSET(存放OS Info) + dba_data_files.bytes
> * dba_data_files.bytes是datafile数据文件的大小 = dba_data_files.user_bytes + 1 * extent (存放metadata)
> * dba_data_files.user_bytes是datafile中可以供用户写入的空间大小 = sum(dba_extents.bytes) + freesize
> * sum(dba_extents.bytes)是datafile中实际被使用的大小
> * freesize = dba_data_files.user_bytes - sum(dba_extents.bytes)


使用下面这个视图，结合dba_data_files，dba_extents，dba_free_space查询extent和表空间的使用情况。

	select df.tablespace_name, df.file_name, df.bytes totalSize, usedBytes usedSize, USER_BYTES userfullSize, freeBytes freeSize, df.autoextensible
	from dba_data_files df
		left join (
			select file_id, sum(bytes) usedBytes
			from dba_extents
			group by file_id
		) ext on df.file_id = ext.file_id
		left join (
			select file_id, sum(bytes) freeBytes
			from dba_free_space
			group by file_id
		) free on df.file_id = free.file_id
	where df.tablespace_name in ('XWIZ01')
	order by df.tablespace_name, df.file_name;

数据文件空余空间为：

	TABLESPACE FILE_NAME                                 TOTALSIZE   USEDSIZE USERFULLSIZE   FREESIZE AUTOEXTEN
	---------- ---------------------------------------- ---------- ---------- ------------ ---------- ---------
	XWIZ01     /u01/app/oracle/oradata/orcl/xwiz01.dbf    23068672   20971520     23003136    2031616 YES

> * FREESIZE = dba_data_files.user_bytes - sum(dba_extents.bytes) 
			 = 23003136 - 20971520  = 2031616
 
再看第二个表空间

	select tablespace_name, EXTENT_ID, FILE_ID, BLOCK_ID, BYTES, BLOCKS from dba_extents where tablespace_name in ('XWIZ02');
	TABLESPACE  EXTENT_ID    FILE_ID   BLOCK_ID      BYTES     BLOCKS
	---------- ---------- ---------- ---------- ---------- ----------
	XWIZ02              0          6          8     131072         16
	XWIZ02              1          6         24     131072         16
	XWIZ02              2          6         40     131072         16
	XWIZ02              3          6         56     131072         16
	XWIZ02              4          6         72     131072         16
	XWIZ02              5          6         88     131072         16
	XWIZ02              6          6        104     131072         16
	XWIZ02              7          6        120     131072         16
	XWIZ02              8          6        136     131072         16
	XWIZ02              9          6        152     131072         16
	XWIZ02             10          6        168     131072         16
	XWIZ02             11          6        184     131072         16
	XWIZ02             12          6        200     131072         16
	XWIZ02             13          6        216     131072         16
	XWIZ02             14          6        232     131072         16
	XWIZ02             15          6        248     131072         16
	XWIZ02             16          6        264     131072         16
	XWIZ02             17          6        280     131072         16
	XWIZ02             18          6        296     131072         16
	XWIZ02             19          6        312     131072         16
	XWIZ02             20          6        328     131072         16
	XWIZ02             21          6        344     131072         16
	XWIZ02             22          6        360     131072         16
	XWIZ02             23          6        376     131072         16
	XWIZ02             24          6        392     131072         16
	XWIZ02             25          6        408     131072         16
	XWIZ02             26          6        424     131072         16
	XWIZ02             27          6        440     131072         16
	XWIZ02             28          6        456     131072         16
	XWIZ02             29          6        472     131072         16
	XWIZ02             30          6        488     131072         16
	XWIZ02             31          6        504     131072         16
	XWIZ02             32          6        520     131072         16
	XWIZ02             33          6        536     131072         16
	XWIZ02             34          6        552     131072         16
	XWIZ02             35          6        568     131072         16
	XWIZ02             36          6        584     131072         16
	XWIZ02             37          6        600     131072         16
	XWIZ02             38          6        616     131072         16
	XWIZ02             39          6        632     131072         16
	XWIZ02             40          6        648     131072         16
	XWIZ02             41          6        664     131072         16
	XWIZ02             42          6        680     131072         16
	XWIZ02             43          6        696     131072         16
	XWIZ02             44          6        712     131072         16
	XWIZ02             45          6        728     131072         16
	XWIZ02             46          6        744     131072         16
	XWIZ02             47          6        760     131072         16
	XWIZ02             48          6        776     131072         16
	XWIZ02             49          6        792     131072         16
	XWIZ02             50          6        808     131072         16
	XWIZ02             51          6        824     131072         16
	XWIZ02             52          6        840     131072         16
	XWIZ02             53          6        856     131072         16
	XWIZ02             54          6        872     131072         16
	XWIZ02             55          6        888     131072         16
	XWIZ02             56          6        904     131072         16
	XWIZ02             57          6        920     131072         16
	XWIZ02             58          6        936     131072         16
	XWIZ02             59          6        952     131072         16
	XWIZ02             60          6        968     131072         16
	XWIZ02             61          6        984     131072         16
	XWIZ02             62          6       1000     131072         16
	XWIZ02             63          6       1016     131072         16
	XWIZ02             64          6       1032     131072         16
	XWIZ02             65          6       1048     131072         16
	XWIZ02             66          6       1064     131072         16
	XWIZ02             67          6       1080     131072         16
	XWIZ02             68          6       1096     131072         16
	XWIZ02             69          6       1112     131072         16
	XWIZ02             70          6       1128     131072         16
	XWIZ02             71          6       1144     131072         16
	XWIZ02             72          6       1160     131072         16
	XWIZ02             73          6       1176     131072         16
	XWIZ02             74          6       1192     131072         16
	XWIZ02             75          6       1208     131072         16
	XWIZ02             76          6       1224     131072         16
	XWIZ02             77          6       1240     131072         16
	XWIZ02             78          6       1256     131072         16
	XWIZ02             79          6       1272     131072         16
	XWIZ02             80          6       1288     131072         16
	XWIZ02             81          6       1304     131072         16
	XWIZ02             82          6       1320     131072         16
	XWIZ02             83          6       1336     131072         16
	XWIZ02             84          6       1352     131072         16
	XWIZ02             85          6       1368     131072         16
	XWIZ02             86          6       1384     131072         16
	XWIZ02             87          6       1400     131072         16
	XWIZ02             88          6       1416     131072         16
	XWIZ02             89          6       1432     131072         16
	XWIZ02             90          6       1448     131072         16
	XWIZ02             91          6       1464     131072         16
	XWIZ02             92          6       1480     131072         16
	XWIZ02             93          6       1496     131072         16
	XWIZ02             94          6       1512     131072         16
	XWIZ02             95          6       1528     131072         16
	XWIZ02             96          6       1544     131072         16
	XWIZ02             97          6       1560     131072         16
	XWIZ02             98          6       1576     131072         16
	XWIZ02             99          6       1592     131072         16
	XWIZ02            100          6       1608     131072         16
	XWIZ02            101          6       1624     131072         16
	XWIZ02            102          6       1640     131072         16
	XWIZ02            103          6       1656     131072         16
	XWIZ02            104          6       1672     131072         16
	XWIZ02            105          6       1688     131072         16
	XWIZ02            106          6       1704     131072         16
	XWIZ02            107          6       1720     131072         16
	XWIZ02            108          6       1736     131072         16
	XWIZ02            109          6       1752     131072         16
	XWIZ02            110          6       1768     131072         16
	XWIZ02            111          6       1784     131072         16
	XWIZ02            112          6       1800     131072         16
	XWIZ02            113          6       1816     131072         16
	XWIZ02            114          6       1832     131072         16
	XWIZ02            115          6       1848     131072         16
	XWIZ02            116          6       1864     131072         16
	XWIZ02            117          6       1880     131072         16
	XWIZ02            118          6       1896     131072         16
	XWIZ02            119          6       1912     131072         16
	XWIZ02            120          6       1928     131072         16
	XWIZ02            121          6       1944     131072         16
	XWIZ02            122          6       1960     131072         16
	XWIZ02            123          6       1976     131072         16
	XWIZ02            124          6       1992     131072         16
	XWIZ02            125          6       2008     131072         16
	XWIZ02            126          6       2024     131072         16
	XWIZ02            127          6       2040     131072         16
	XWIZ02            128          6       2056     131072         16
	XWIZ02            129          6       2072     131072         16
	XWIZ02            130          6       2088     131072         16
	XWIZ02            131          6       2104     131072         16
	XWIZ02            132          6       2120     131072         16
	XWIZ02            133          6       2136     131072         16
	XWIZ02            134          6       2152     131072         16
	XWIZ02            135          6       2168     131072         16
	XWIZ02            136          6       2184     131072         16
	XWIZ02            137          6       2200     131072         16
	XWIZ02            138          6       2216     131072         16
	XWIZ02            139          6       2232     131072         16
	XWIZ02            140          6       2248     131072         16
	XWIZ02            141          6       2264     131072         16
	XWIZ02            142          6       2280     131072         16
	XWIZ02            143          6       2296     131072         16
	XWIZ02            144          6       2312     131072         16
	XWIZ02            145          6       2328     131072         16
	XWIZ02            146          6       2344     131072         16
	XWIZ02            147          6       2360     131072         16
	XWIZ02            148          6       2376     131072         16
	XWIZ02            149          6       2392     131072         16
	XWIZ02            150          6       2408     131072         16
	XWIZ02            151          6       2424     131072         16
	XWIZ02            152          6       2440     131072         16
	XWIZ02            153          6       2456     131072         16
	XWIZ02            154          6       2472     131072         16
	XWIZ02            155          6       2488     131072         16
	XWIZ02            156          6       2504     131072         16
	XWIZ02            157          6       2520     131072         16

	158 rows selected.

因为extent采用UNIFORM SIZE增长，每次为128K = 131072 bytes = 16 * DB_BLOCK_SIZE

	select tablespace_name, sum(BYTES), sum(BLOCKS) from dba_extents where tablespace_name in ('XWIZ02') group by tablespace_name;


	TABLESPACE SUM(BYTES) SUM(BLOCKS)
	---------- ---------- -----------
	XWIZ02       20709376        2528

数据文件给部分空间为：

	select df.tablespace_name, df.file_name, df.bytes totalSize, usedBytes usedSize, USER_BYTES userfullSize, freeBytes freeSize, df.autoextensible
	from dba_data_files df
		left join (
			select file_id, sum(bytes) usedBytes
			from dba_extents
			group by file_id
		) ext on df.file_id = ext.file_id
		left join (
			select file_id, sum(bytes) freeBytes
			from dba_free_space
			group by file_id
		) free on df.file_id = free.file_id
	where df.tablespace_name in ('XWIZ02')
	order by df.tablespace_name, df.file_name;


	TABLESPACE FILE_NAME                                 TOTALSIZE   USEDSIZE USERFULLSIZE   FREESIZE AUTOEXTEN
	---------- ---------------------------------------- ---------- ---------- ------------ ---------- ---------
	XWIZ02     /u01/app/oracle/oradata/orcl/xwiz02.dbf    22020096   20709376     21889024    1179648 YES


> * FREESIZE = dba_data_files.user_bytes - sum(dba_extents.bytes) 
			 = 21889024 - 20709376  = 1179648


最后看xwiz03表空间

	select tablespace_name, EXTENT_ID, FILE_ID, BLOCK_ID, BYTES, BLOCKS from dba_extents where tablespace_name in ('XWIZ03');

	TABLESPACE  EXTENT_ID    FILE_ID   BLOCK_ID      BYTES     BLOCKS
	---------- ---------- ---------- ---------- ---------- ----------
	XWIZ03              0          7          8     524288         64
	XWIZ03              1          7         72     524288         64
	XWIZ03              2          7        136     524288         64
	XWIZ03              3          7        200     524288         64
	XWIZ03              4          7        264     524288         64
	XWIZ03              5          7        328     524288         64
	XWIZ03              6          7        392     524288         64
	XWIZ03              7          7        456     524288         64
	XWIZ03              8          7        520     524288         64
	XWIZ03              9          7        584     524288         64
	XWIZ03             10          7        648     524288         64
	XWIZ03             11          7        712     524288         64
	XWIZ03             12          7        776     524288         64
	XWIZ03             13          7        840     524288         64
	XWIZ03             14          7        904     524288         64
	XWIZ03             15          7        968     524288         64
	XWIZ03             16          7       1032     524288         64
	XWIZ03             17          7       1096     524288         64
	XWIZ03             18          7       1160     524288         64
	XWIZ03             19          7       1224     524288         64
	XWIZ03             20          7       1288     524288         64
	XWIZ03             21          7       1352     524288         64
	XWIZ03             22          7       1416     524288         64
	XWIZ03             23          7       1480     524288         64
	XWIZ03             24          7       1544     524288         64
	XWIZ03             25          7       1608     524288         64
	XWIZ03             26          7       1672     524288         64
	XWIZ03             27          7       1736     524288         64
	XWIZ03             28          7       1800     524288         64
	XWIZ03             29          7       1864     524288         64
	XWIZ03             30          7       1928     524288         64
	XWIZ03             31          7       1992     524288         64
	XWIZ03             32          7       2056     524288         64
	XWIZ03             33          7       2120     524288         64
	XWIZ03             34          7       2184     524288         64
	XWIZ03             35          7       2248     524288         64
	XWIZ03             36          7       2312     524288         64
	XWIZ03             37          7       2376     524288         64
	XWIZ03             38          7       2440     524288         64
	XWIZ03             39          7       2504     524288         64

	40 rows selected.


extent采用UNIFORM SIZE增长，每次为512K = 524288 bytes = 64 * DB_BLOCK_SIZE

	select tablespace_name, sum(BYTES), sum(BLOCKS) from dba_extents where tablespace_name in ('XWIZ03') group by tablespace_name;

	TABLESPACE SUM(BYTES) SUM(BLOCKS)
	---------- ---------- -----------
	XWIZ03       20971520        2560


	select df.tablespace_name, df.file_name, df.bytes totalSize, usedBytes usedSize, USER_BYTES userfullSize, freeBytes freeSize, df.autoextensible
	from dba_data_files df
		left join (
			select file_id, sum(bytes) usedBytes
			from dba_extents
			group by file_id
		) ext on df.file_id = ext.file_id
		left join (
			select file_id, sum(bytes) freeBytes
			from dba_free_space
			group by file_id
		) free on df.file_id = free.file_id
	where df.tablespace_name in ('XWIZ03')
	order by df.tablespace_name, df.file_name;


	TABLESPACE FILE_NAME                                 TOTALSIZE   USEDSIZE USERFULLSIZE   FREESIZE AUTOEXTEN
	---------- ---------------------------------------- ---------- ---------- ------------ ---------- ---------
	XWIZ03     /u01/app/oracle/oradata/orcl/xwiz03.dbf    23068672   20971520     22544384    1572864 YES


> * FREESIZE = dba_data_files.user_bytes - sum(dba_extents.bytes) 
			 = 22544384 - 20971520  = 1572864
		 
		 

		 
总结 datafile与extent的关系：

	--> 实际的物理文件大小
	physical datafile filesize = BLOCK1_OFFSET + dba_data_files.bytes    
	
	--> 视图中datafile数据文件的大小
	dba_data_files.bytes = dba_data_files.user_bytes + 1 * extent     
	
	-->datafile中可以供用户写入的空间大小
	dba_data_files.user_bytes = sum(dba_extents.bytes) + freesize  
	
	-->datafile中实际被使用的大小
	sum(dba_extents.bytes)  
	
	--> datafile中空闲的空间
	freesize = ba_data_files.user_bytes - sum(dba_extents.bytes)  
	
	--> datafile增长时，
		autoallocation: extent extend = 64K/1M/... 根据内部算法逐步增大
		uniform: extent extend = uniform size



	

 