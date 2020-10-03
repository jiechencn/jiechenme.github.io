---
title: touch与文件时间
author: Jie Chen
date: 2016-09-06
categories: [Linux]
tags: [Shell]
---

之前有个很诡异的问题，就是更新JAR压缩包中的class文件，会出现JAR文件本身的Last Modification Time保持不变的情况。这个问题到现在都没有找出原因，后来只能通过touch来人为修改。所以也顺便学习了一下touch的其他用法。

## 修改为指定的时间

假设有个file1的文件，它的时间信息显示为：

~~~
$ stat file1
~~~
~~~
  File: ‘file1’
  Size: 0               Blocks: 0          IO Block: 4096   regular empty file
Device: fb00h/64256d    Inode: 112075      Links: 1
Access: (0644/-rw-r--r--)  Uid: (    0/    root)   Gid: ( 1000/  oracle)
Context: unconfined_u:object_r:user_home_t:s0
Access: 2012-08-29 13:45:02.000000000 +0800
Modify: 2012-08-29 13:45:02.000000000 +0800
Change: 2012-08-29 13:45:02.000000000 +0800
~~~

除了touch file1最简单粗暴的方式修改时间外，还可以指定某个时间，秒数用小数点区分。

~~~
$ touch -t 201208301122.33 file1
~~~
~~~
[oracle@localhost test]$ stat file1
  File: ‘file1’
  Size: 0               Blocks: 0          IO Block: 4096   regular empty file
Device: fb00h/64256d    Inode: 112075      Links: 1
Access: (0644/-rw-r--r--)  Uid: (    0/    root)   Gid: ( 1000/  oracle)
Context: unconfined_u:object_r:user_home_t:s0
Access: 2012-08-30 11:22:33.000000000 +0800
Modify: 2012-08-30 11:22:33.000000000 +0800
Change: 2012-08-29 13:45:02.000000000 +0800
 Birth: -
~~~

这样同时修改Access和Modify时间。当然也可以单独指定Access或者Modify，比如：

~~~
$ touch -t 201208311122.33 -m file1
// $ touch -t 201208311122.33 -a file1
~~~
~~~
[oracle@localhost test]$ stat file1
  File: ‘file1’
  Size: 0               Blocks: 0          IO Block: 4096   regular empty file
Device: fb00h/64256d    Inode: 112075      Links: 1
Access: (0644/-rw-r--r--)  Uid: (    0/    root)   Gid: ( 1000/  oracle)
Context: unconfined_u:object_r:user_home_t:s0
Access: 2012-08-30 11:22:33.000000000 +0800
Modify: 2012-08-31 11:22:33.000000000 +0800
Change: 2012-08-29 13:45:02.000000000 +0800
 Birth: -
~~~

## 修改为和其他文件一样的时间

使用 -r 参数（就是reference），可以把file1的时间复制到file2中去。

~~~
$ touch -r file1 file2
~~~

同样道理，可以指定只修改access或者modify的时间。

~~~ 
$ touch -r file1 -a file2  
$ touch -r file1 -m file2
~~~

####Modify和Change时间####

一个文件的时间有三种属性，Access，Modify和Change。

* Modify指文件内容的最后修改时间，当然可以通过touch来篡改
* Change指的是文件的inode信息最后修改时间，比如文件的权限修改、owner修改等最后一次时间。