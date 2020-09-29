---
title: 标准输入和参数的管道传输
author: Jie Chen
date: 2012-06-01
categories: [Linux]
tags: [Shell]
---

一个进程通过管道把自己的标准输出流作为标准输入流传输给第二个进程，这是最常用的。但是有时候第二个进程不支持接受标准输入流，只接受普通参数，这就需要对标准输入流做转换。

## pipe

假设当前目录下有几个txt文件
~~~
$ find *.txt
chinese.txt
english.txt
maths.txt
name.txt
~~~

通过管道传输，find *.txt 的结果成为标准输出流，管道转换后，成为 grep 的标准输入流vvvv。这相当于把find的结果（多行字符串）作为输入流，传输给grep。所以grep对这个输入流（字符串）进行正则查找。
~~~
$ find *.txt | grep n
chinese.txt
english.txt
name.txt
~~~
如果把这个标准输出流，转换成普通参数，就需要通过xargs来明示。
~~~
$ find *.txt | xargs grep n
name.txt:1 student_name_1
name.txt:2 student_name_2
name.txt:3 student_name_3
name.txt:4 student_name_4
name.txt:5 student_name_5
name.txt:6 student_name_6
~~~
这样的执行结果，等价于下面的多文件内容正则查找。结果是从name.txt文本文件的内容中找到含有字符n的内容。
~~~
$ grep n chinese.txt english.txt maths.txt name.txt
~~~
## -exec

-exec 目的是执行完第一个进程后接着执行第二个进程，它可以接收第一个进程执行完毕的输出作为普通参数，不能作为标准输入流。而且它只能对输出的结果进行逐行的执行。

比如下面，分号表示-exec执行结束，\是转义。
~~~
$  find *.txt -exec grep n {} \;
1 student_name_1
2 student_name_2
3 student_name_3
4 student_name_4
5 student_name_5
6 student_name_6
~~~

上面的find结果返回4行内容，每一行都是一个文件名。-exec解释后，对这四行逐一执行 grep，所以上述命令相当于执行：

~~~
grep n chinese.txt 
grep n english.txt 
grep n maths.txt 
grep n name.txt
~~~

可见，效率很低。

## -ok

-ok 是 -exec的安全版本，每执行一条，都会需要用户交互yes/no (y/n也可以)确认。比如：

~~~
$ find *.txt -ok ls -al {} \;
< ls ... chinese.txt > ? y
-rw-r--r--. 1 oracle oracle 20 Apr 24 09:13 chinese.txt
< ls ... english.txt > ? n
< ls ... maths.txt > ? n
< ls ... name.txt > ? y
-rw-r--r--. 1 oracle oracle 103 Apr 24 08:29 name.txt
~~~

## 总结

* 如果第二个命令需要第一个进程的输出流作为输入流来读取，直接使用 | 
* 如果第二个命令需要第一个进程的输出流作为普通参数来读取，使用 | xargs 或者 -exec 或者 -ok
* 如果第二个命令不支持标准输入流而只能接收普通参数，使用 | xargs 或者 -exec 或者 -ok