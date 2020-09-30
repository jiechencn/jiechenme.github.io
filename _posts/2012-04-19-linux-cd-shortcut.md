---
title: cd目录的快速跳转
author: Jie Chen
date: 2012-04-19
categories: [Linux]
tags: [shell]
---


cd命令非常简单，日常使用其实也没有什么需要特别技巧的东西。但是下面的一些小技巧，非常有利于操作效率。


## 回到主目录

	cd ~ 


## 回到前一个访问过的目录

	cd -

多次执行 'cd -'，只会在当前目录和前一个目录中来回切换。

## 跨目录快速跳转

CDPATH环境变量为cd命令定义一个cd自己的查找目录。然后如果想要进入这个目录的子目录，可以直接 'cd 子目录'，无论当前pwd是在哪个目录下。

比如，定义CDPATH的查找目录列表为：当前目录；主目录；/home/oracle/temp/p1/目录和/home/oracle/temp/p2/目录

	export CDPATH=.:~:/home/oracle/temp/p1:/home/oracle/temp/p2

从/u01/app/oracle/目录下快速跳到/home/oracle/temp/p1/mydir/目录，直接使用'cd mydir'

	[oracle@localhost oracle]$ pwd
	/u01/app/oracle

	[oracle@localhost oracle]$ cd mydir
	/home/oracle/temp/p1/mydir

	[oracle@localhost oracle]$ pwd
	/home/oracle/temp/p1/mydir

注意：当mydir在多个查找目录中都存在时，cd只会从目录列表中选择第一个匹配的目录。也即是说当mydir目录存在于p1和p2目录下面时，cd会进入排在前面的p1/mydir


## 快速后退目录

后退目录使用 cd ../../../比较繁琐的情况下，可以自定义一些别名命令替代cd。

	[oracle@localhost trace]$ alias cd1='cd ..'
	[oracle@localhost trace]$ alias cd2='cd ../..'
	[oracle@localhost trace]$ alias cd3='cd ../../..'
	[oracle@localhost trace]$ alias cd4='cd ../../../..'

快速后退目录直接用这些别名就可以了

	[oracle@localhost trace]$ pwd
	/u01/app/oracle/diag/rdbms/orcl/orcl/trace

	[oracle@localhost trace]$ cd3
	/u01/app/oracle/diag/rdbms

## cd后列出目录内容

习惯性的，一般cd后我们都会使用pwd确认目录是否正确，同时喜欢用ls来列举目录文件。把这些操作结合起来，可以自定cd方法，覆盖内置的cd。

	function cd()
	{
	builtin cd ${1} && pwd && ls -l;
	}

buildin表示需要调用内置函数, $(1) 表示cd后面的参数, &&是先后执行两个命令的连接符号。

比如，执行cd mydir后，命令同时执行了pwd和 ls -l操作。

	[oracle@localhost home]$ cd mydir
	/home/oracle/temp/p1/mydir
	/home/oracle/temp/p1/mydir
	total 0
	drwxr-xr-x. 2 oracle oracle  6 Apr 19 05:54 a
	drwxr-xr-x. 3 oracle oracle 14 Apr 19 05:55 myfolder


把上面的一些小技巧综合起来，放到~/.bash_profile里，一劳永逸。


	alias cd..='cd ..'
	alias cd1='cd ..'
	alias cd2='cd ../..'
	alias cd3='cd ../../..'

	export CDPATH=.:~:/home/oracle/temp/p1:/home/oracle/temp/p2

	function cd()
	{
	builtin cd ${1} && pwd && ls -l;
	}


这里，我还还定义了 alias cd..='cd ..'  。因为我常常把 'cd ..' 错打成 'cd..'。


