---
title: Python中的int此整型非彼整型问题
author: Jie Chen
date: 2018-02-06
categories: [Python]
tags: []
---

最近在学习Python，有一次尝试用递归方法来实现去除左右空格的功能，却进入了一个有趣的话题，就是Python3中的整型问题。记录下来。

	# -*- coding: utf-8 -*-
	import sys

	def xtrim(s):
		if s[:1]==' ':
			s = s[0-len(s)+1:]
			s = xtrim(s)
		if s[-1:]==' ':
			s = s[:len(s)-1]
			s = xtrim(s)
		return s

		s = '   可爱的小猪可爱的小猪可爱的小猪   ' # 字符串前后都含一万个空格的字符串
		print(xtrim(s))


递归一定层次后就抛错了，因为默认最深递归为1000。

	Traceback (most recent call last):
	...
	RecursionError: maximum recursion depth exceeded in comparison


然后试图用maxsize最大值来重置递归限制，在我的64bit机器上，Python解析器也是64bit，我以为int也就是64bit了。

	print('sys.maxsize=', sys.maxsize)	
	print('(2**63)-1=', (2**63)-1)	
	sys.setrecursionlimit(sys.maxsize)

结果很奇怪啊。
	
	sys.maxsize= 9223372036854775807
	(2**63)-1= 9223372036854775807
	Traceback (most recent call last):
	  File "test_slice.py", line 21, in <module>
		sys.setrecursionlimit((2**63)-1)
	OverflowError: Python int too large to convert to C long


重新查看了Python的文档，找到这么一段话：

	Various pieces of the Python interpreter used C’s int type to store sizes or counts; for example, the number of items in a list or tuple were stored in an int. The C compilers for most 64-bit platforms still define int as a 32-bit type, so that meant that lists could only hold up to 2**31 - 1 = 2147483647 items.

也就是说，int常常被用来list或者tuple中的下标计数，比如像我这个例子里的递归层次的计数限制。但是C语言编译器对于大部分的64bit操作系统还是使用了32bit类型的整型。所以对于常用的普通int，还是上限2147483647。

	sys.setrecursionlimit((2**31)-1)

又发现了更有意思的定义。比如下面这个天文数字，返回的类型也是整型，而对于本该long类型的(2**63)-1，也是整型。由于Pthong3已经没有long的概念，不知道下面这个天文数字是如何做到存储的。

	i = 99999999999999999999999999999999999999999999999999999999999999999999999999999
	print(str(i) + ' type=', type(i))
	print('sys.maxsize type=', type(sys.maxsize))	
	print('(2**63)-1 type=', type((2**63)-1))

匪夷所思

	99999999999999999999999999999999999999999999999999999999999999999999999999999 type= <class 'int'>
	sys.maxsize type= <class 'int'>
	(2**63)-1 type= <class 'int'>


此整型非彼整型，Python3中整数的定义已经可以无限大了，那要 sys.maxsize有什么用呢？
 



