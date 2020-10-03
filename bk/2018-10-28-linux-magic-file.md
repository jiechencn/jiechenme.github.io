---
title: file命令与magic查询原理
author: Jie Chen
date: 2018-10-28
categories: [Linux]
tags: [shell]
---


在给客户处理问题时候，用到了file命令，后来对file命令产生了兴趣，所以拿它和Java类文件的魔术判断做实验，这里记下一些实验的收货。

Java Class文件通过魔数来标识自身的编译版本。在Linux中，通过 xxd，可以看到十六进制头部信息。

~~~
$ xxd CookieExample.class | head -2

0000000: cafe babe 0000 0034 00ed 0a00 4400 7b08  .......4....D.{.
0000010: 007c 0b00 7d00 7e08 007f 0700 800a 0005  .|..}.~.........
~~~

<kbd>cafe babe</kbd> 是Java的标志魔数，接下来的 <kbd>0000 0034</kbd> 代表编译版本，其中<kbd>0000</kbd> 是小版本，<kbd>0034</kbd>是大版本。查询Java编译规格，可以知道<kbd>0034</kbd>属于Java 1.8。

在Linux中，通过file命令可以快速地查询到class文件信息

~~~
$ file CookieExample.class

./CookieExample.class: compiled Java class data, version 52.0 (Java 1.8)
~~~

很容易就能想到，Linux上一定存在一个数据源（数据库或文件），存放各种常见的已知文件的魔数信息，file命令直接查询这个数据源并返回结果。通过 -v 开关，就能获取到这个数据源。

~~~
$ file -v

file-5.11
magic file from /etc/magic:/usr/share/misc/magic
~~~

结果显示有两个数据源：

* /etc/magic : 用户可以自定义的魔数库文件，默认是空的
* /usr/share/misc/magic ：Linux系统预定义的魔数库，有数百个文件类型

对于Java class相关的魔数库定义，通过 grep 和 tail相结合，快速查找到定义：
~~~
$ grep -A 100 -B 100 "^.\+Java 1.8." /usr/share/misc/magic | tail -n 201

#------------------------------------------------------------------------------
# $File: cafebabe,v 1.8 2009/09/19 16:28:08 christos Exp $
# Cafe Babes unite!
#
# Since Java bytecode and Mach-O fat-files have the same magic number, the test
# must be performed in the same "magic" sequence to get both right.  The long
# at offset 4 in a mach-O fat file tells the number of architectures; the short at
# offset 4 in a Java bytecode file is the JVM minor version and the
# short at offset 6 is the JVM major version.  Since there are only
# only 18 labeled Mach-O architectures at current, and the first released
# Java class format was version 43.0, we can safely choose any number
# between 18 and 39 to test the number of architectures against
# (and use as a hack). Let's not use 18, because the Mach-O people
# might add another one or two as time goes by...
#
0       belong          0xcafebabe
!:mime  application/x-java-applet
>4      belong          >30             compiled Java class data,
>>6     beshort         x               version %d.
>>4     beshort         x               \b%d
# Which is which?
#>>4    belong          0x032d          (Java 1.0)
#>>4    belong          0x032d          (Java 1.1)
>>4     belong          0x002e          (Java 1.2)
>>4     belong          0x002f          (Java 1.3)
>>4     belong          0x0030          (Java 1.4)
>>4     belong          0x0031          (Java 1.5)
>>4     belong          0x0032          (Java 1.6)
>>4     belong          0x0033          (Java 1.7)
>>4     belong          0x0034          (Java 1.8)
~~~

根据注释部分的脚本解释，file通过访问文件头的十六进制，以偏移位为索引，查找各个部分的数据，跟已知的常见文件规格来比较。

如果给file命令添加 -d 调试开关，就能看出逻辑判断的过程。

~~~
$ file -d CookieExample.class

// 删除了大部分其他判断，只保留了Java部分的判断分支
mget @0: 
17: > 0 belong&,=-889275714,""]
18446744072820275902 == 18446744072820275902 = 1

mget @4: 
19: >> 4 belong&,>30,"compiled Java class data,"]
52 > 30 = 1

mget @6: 
20: >>> 6 beshort&,x,"version %d."]
52 == *any* = 1

mget @4: 
21: >>> 4 beshort&,x,"%d"]
0 == *any* = 1

mget @4: 
25: >>> 4 belong&,=46,"(Java 1.2)"]
52 == 46 = 0

mget @4: 
26: >>> 4 belong&,=47,"(Java 1.3)"]
52 == 47 = 0

mget @4: 
27: >>> 4 belong&,=48,"(Java 1.4)"]
52 == 48 = 0

mget @4: 
28: >>> 4 belong&,=49,"(Java 1.5)"]
52 == 49 = 0

mget @4: 
29: >>> 4 belong&,=50,"(Java 1.6)"]
52 == 50 = 0

mget @4: 
30: >>> 4 belong&,=51,"(Java 1.7)"]
52 == 51 = 0

mget @4: 
31: >>> 4 belong&,=52,"(Java 1.8)"]
52 == 52 = 1
softmagic 1
./CookieExample.class: compiled Java class data, version 52.0 (Java 1.8)
~~~

详细的逻辑过程，非常容易理解：

1. 偏移量索引从0开始。
2. 索引从0开始，如果十六进制数为0xcafebabe，则进入下一行的内部判断。
3. 0xcafebabe往后四个字节long类型，是0000 0034，十进制是52，大于30，所以判定这个class文件为"compiled Java class data"
4. 索引从第6开始的short类型，0034，满足x，x代表任意。这两个字节代表Java的大版本
5. 索引从第4开始的short类型，0000，满足x，x代表任意。这两个字节代表Java的大版本
6. 接下来，索引从第4开始的long类型，0000 0034，一个个去判断，直到匹配 Java 1.8



