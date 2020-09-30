---
title: 用/dev/null和/dev/zero更改文件
author: Jie Chen
date: 2012-03-11
categories: [Linux]
tags: [shell]
---

/dev/null的行为和/dev/zero看起来，有相反的作用，有时又相似。/dev/null就像无底洞，吃掉被指定的重定向。而/dev/zero能为目标文件提供连续的数据流填空。他们的作用可能还不止这些。但是我一般用它们做下面最常用的用途。

## /dev/null清空文件

/dev/null常常用来吞掉被重定向的数据。反过来，它可以被当作输入流的空文件。

在分析系统故障时，日志文件会有大量的数据产生。重现问题的时候，首先过滤掉无用的信息，再比对时间戳信息。日志文件太大，非常不好处理。最好的方法是：使用/dev/null把日志文件清空（不删除）。

~~~
cat /dev/null >test.log 
~~~

这相当于把空文件以覆盖的方式写入test.log。

## /dev/zero构造大文件

有时需要准备一个大文件供测试，/dev/zero提供输入流就能起到非常大的作用。比如输出一个test.log，大小为10M*20块=200M。

~~~
dd if=/dev/zero of=test.log bs=10M count=20
~~~

## /dev/zero清空文件

将上面的命令修改一下，count为0，表示输入0块数据， 0*10M=0，一样能起到清空文件的作用。所以下面两个命令的结果是等价的。

~~~
cat /dev/null >test.log 
~~~

~~~
dd if=/dev/zero of=test.log bs=10M count=0
~~~

上面的这些操作，都是以覆盖的方式重写文件。