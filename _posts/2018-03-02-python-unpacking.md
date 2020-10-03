---
title: 可迭代对象的元素的拆包
author: Jie Chen
date: 2018-03-02
categories: [Python]
tags: []
---

今天用到了元组一个很实用又很简单的运算，就是元素拆包。其实可以把拆包推广到所有的可迭代对象： list，set， dictiona等，只要这个可迭代对象的值的数量和接收它门的变量数量一致，拆包就能实现自动匹配。

花了点时间，实验了比较多的可能性，把拆包总结了一下。

## 赋值拆包

这个用在变量的定义赋值语句中，比如下面的三种迭代类型：元组、列表和集合。元素a，b，c按照顺序自动获取每个整型值。

~~~
a, b, c = (1, 2, 3)
a, b, c = [1, 2, 3]
a, b, c = {1, 2, 3}
~~~

对于字典类型，需要通过values()获取值，如果想要获取key，就切换为keys()

~~~
a, b, c = dict(one=1, two=2, three=3).values()
~~~

## 函数参数拆包

如果一个函数需要接收多个参数，可以使用*运算符把可迭代对象作为唯一的参数传递给函数。

比如myfun1，需要3个参数。

~~~
def myfun1(x, y, z):
    print("x={0},y={1},z={2}".format(x, y, z))
~~~	
	
使用函数参数拆包的方式，可以调用为：

~~~
tuple_a = (1, 2, 3)
list_b = [1, 2, 3]
set_c = {1, 2, 3}
dict_d = dict(one=1, two=2, three=3)

myfun1(*tuple_a)
myfun1(*list_b)
myfun1(*set_c)
myfun1(*dict_d.items())
myfun1(*dict_d.keys())
myfun1(*dict_d.values())
~~~	
	
## 函数返回值拆包

Python中一个函数可以定义成返回多个值。比如：

~~~
def myfun2(x, y):
    return x+y, x-y, x*y
	
tuple_a = (1, 2)
list_b = [1, 2]
set_c = {1, 2}
dict_d = dict(one=1, two=2)

a, b, c = myfun2(*tuple_a)
a, b, c = myfun2(*list_b)
a, b, c = myfun2(*set_c)
a, b, c = myfun2(*dict_d.values())
~~~

函数返回值的拆包，其实就是赋值拆包的体现。

另外，如果仅仅需要获取返回值列表里的某些值，可以使用占位符过滤掉其他不感兴趣的返回值。比如我只想获取第二个返回值，可以占位符为：

~~~
_, b, _ = myfun2(*set_c)
~~~