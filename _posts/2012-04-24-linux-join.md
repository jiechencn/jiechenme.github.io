---
title: 用Join像SQL Outer Join一样合并多文件
author: Jie Chen
date: 2012-04-24
categories: [Linux]
tags: [shell]
---

曾今处理过一个问题，帮助用户合并多个CSV文件，不需要编程（编程一定会涉及到又是循环又是匹配再填充），Linux下一个join命令，逐步调优，再重定向输出。

> Shell一定是一个逐步调整优化的过程，没有人能完整记住所有参数

join命令的设计和SQL中的等值连接非常类似，默认它是一个自然联结。它支持：

* 默认：自然联结 - 以两个文件的第一列字段为key进行等值联结  inner join
* -a 1: 左外联结 left outer join
* -a 2: 右外联结 right outer join
* -a 1 -a 2: 全联结 full outer join

假如需要合并4个文件，分别是name.txt， chinese.txt，maths.txt，english.txt 分别保存了学生姓名，语文、数学和英语的考试得分。

我故意把考试分数的文件的内容打乱顺序。

name.txt 照学号排列
~~~
$ cat name.txt
1 student_name_1
2 student_name_2
3 student_name_3
4 student_name_4
5 student_name_5
6 student_name_6
~~~

chinese.txt 混入了几行空白行
~~~
[oracle@localhost mydir]$ cat chinese.txt
1 c81

2 c82
3 c83

~~~

maths.txt的学号和得分列调换了。

~~~
[oracle@localhost mydir]$ cat maths.txt
m73 3
m74 4
m75 5
~~~

english.txt学号打乱

~~~
[oracle@localhost mydir]$ cat english.txt
2 e92
3 e93
6 e96
5 e95
~~~

我期望得到的结果是按照学号排列，列出所有考生的语数英分数。如果某一门课没有参加考试，用其他字符字符。

首先联结 chinese.txt和maths.txt。如果不指定联结key，默认是按照第一列的字段进行等值。但是这两个文件的第一列并不匹配。chinese.txt的第一列匹配maths.txt第二列。

所以我需要指定按照maths.txt的第二列来作为key。所以设定
~~~
-2 2 
~~~

表示匹配第二个文件的第二列。同时指定 -o 来输出列的值
~~~
-o '0,1.2,2.1'

0：表示列出key
1.2：表示列出第一个文件的第二列
2.1：表示列出第二个文件的第一列
~~~

所以有：

~~~
$ join chinese.txt maths.txt -2 2 -a 1 -a 2  -e '---' -o '0,1.2,2.1'

1 c81 ---
--- --- ---
2 c82 ---
join: chinese.txt:5: is not sorted:
3 c83 m73
--- --- ---
4 --- m74
5 --- m75
~~~

输出中有空行（来源于chinese.txt）和警告信息。对chinese.txt用grep通过正则，过滤掉空白行，同时通过管道传递给sort命令，再把结果反向重定向到join。

~~~
$ join <(grep . chinese.txt | sort) maths.txt -2 2 -a 1 -a 2  -e '---' -o '0,1.2,2.1'

1 c81 ---
2 c82 ---
3 c83 m73
4 --- m74
5 --- m75
~~~

结果看起来不错。

再把上面的结果作为重定向输入，和english.txt进行全外联结

~~~
$ join <(join <(grep . chinese.txt | sort) maths.txt -2 2 -a 1 -a 2  -e '---' -o '0,1.2,2.1') <(sort english.txt) -a 1 -a 2 -e '---' -o '0,1.2,1.3,2.2'

1 c81 --- ---
2 c82 --- e92
3 c83 m73 e93
4 --- m74 ---
5 --- m75 e95
6 --- --- e96
~~~

最后和name.txt全外联结。输出结果完美显示 学号，姓名，各科成绩。

~~~
$ join name.txt <(join <(join <(grep . chinese.txt | sort) maths.txt -2 2 -a 1 -a 2  -e '---' -o '0,1.2,2.1') <(sort english.txt) -a 1 -a 2 -e '---' -o '0,1.2,1.3,2.2') -a 1 -a 2

1 student_name_1 c81 --- ---
2 student_name_2 c82 --- e92
3 student_name_3 c83 m73 e93
4 student_name_4 --- m74 ---
5 student_name_5 --- m75 e95
6 student_name_6 --- --- e96
~~~

join如果用来处理合并多个简单的CSV文件，需要指定字段分隔符（逗号）。比如

~~~
join name.txt score.txt -t,
~~~

但是，如果字段值本身也包括逗号，join就无法区分了。