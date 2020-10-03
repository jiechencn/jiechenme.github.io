---
title: 容器对象的推导方式和元组生成器表达式
author: Jie Chen
date: 2018-02-12
categories: [Python]
tags: []
---


使用推导语法可以快速地产生容器系列的对象，比如list，set，dict，tuple。由此出现了列表推导，集合推导，字典推导和元组推导（生成器表达式）

## 列表推导

比如一个最简单的列表推导，将逗号分隔的字符串组成列表。

~~~
str = '0,1,2,3,4,5,6,7,8,9'
list1 = [i for i in str.split(',')]
xprint(list1)
~~~
~~~
type:<class 'list'>,id:2486205385608,value:['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']
~~~

如果希望列表元素是整数，而不是字符，可以对推导语法的结果添加函数int

~~~
str = '0,1,2,3,4,5,6,7,8,9'
list2 = [int(i) for i in str.split(',')]
xprint(list2)
~~~
~~~
type:<class 'list'>,id:2486205385544,value:[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
~~~

### 带条件过滤的列表推导

推导过程中也可以加入条件过滤，比如只生成偶数序列的列表

~~~
str = '0,1,2,3,4,5,6,7,8,9'
list3 = [int(i) for i in str.split(',') if int(i)%2==0]
xprint(list3)
~~~
~~~
type:<class 'list'>,id:2486205385480,value:[0, 2, 4, 6, 8]
~~~

### 二维矩阵列表

矩阵列表，相当于的列表的嵌套列表

~~~
str = '0,1,2,3,4,5,6,7,8,9'
list4 = [[int(i) for i in str.split(',')] for i in str.split(',')]
for r in list4:
    print(r)
~~~
~~~
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
type:<class 'list'>,id:2486205385416,value:[[0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]]
~~~


## 字典推导

使用字典推导，可能只能处理简单的条件过滤，过于复杂的筛选，还不如用循环来生成。这是一个简单的由元组列表转成key为name的字典。

~~~
students=[
    (1, 'Tom'),
    (2, 'Jerry'),
    (3, 'Marry'),
    (4, 'John'),
]

nameDict = {name: number for name, number in students};
xprint(nameDict);
~~~

## 集合推导

同样，也可以用集合推导，产生一个集合对象
~~~
	str = '0,1,2,3,4,5,6,7,8,9'
	set3 = {int(i) for i in str.split(',') if int(i)%2==0}
	xprint(set3)
~~~

### 二维集合推导

试着模仿列表的列表来生成集合矩阵

~~~
# wrong code
set4 = {{int(i) for i in str.split(',')} for i in str.split(',')}
for r in set4:
    print(r)
~~~
~~~
TypeError: unhashable type: 'set'
~~~

由于set的元素必须是immutable的可以hash的对象。这个例子中，set的元素又是一个set，而set本身是不可hash的，只有set的元素允许hash。所以不存在集合的集合，只能是集合列表。将最外面的花括号改成list的方括号。

~~~
set4 = [{int(i) for i in str.split(',')} for i in str.split(',')]
for r in set4:
    xprint(r)
xprint(set4)
~~~
~~~
type:<class 'set'>,id:2964833125096,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833125320,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833125544,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833125768,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833125992,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833126216,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833126440,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833126664,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833126888,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'set'>,id:2964833127112,value:{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
type:<class 'list'>,id:2964833011784,value:[{0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}, {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}]
~~~

## 元组推导（生成器表达式）

元组推导比较特殊。看这个执行结果。

~~~
tupleGene1 = (int(i) for i in str.split(','))
xprint(tupleGene1)
~~~
~~~
type:<class 'generator'>,id:1435801381704,value:<generator object <genexpr> at 0x0000014E4C6E6B48>
~~~

结果并不是期望的tuple类型，而是generator，生成器。

这个generator，非常类似于设计模式中的迭代器，迭代器只能迭代一个静态的对象链条。因为tuple中的元素是不允许修改的，所以两者非常相似。

另外，generator在生成时，和上面的其他推导完全不一样，它并不是一下子把tuple建立出来，而是返回一个迭代指针。使用隐喻的next函数来取得下一个值。所以生成器表达式非常适合用于创建一个超大元组。

元组推导产生的是一个generator，可以把它转换成tuple，再按照tuple方式取值。就像下面这样。但是失去了generator创建大对象的优越性。

~~~
tupleGene1 = (int(i) for i in str.split(','))
mytuple1 = tuple(tupleGene1)
for e in mytuple1:
    xprint(e)
~~~
~~~
type:<class 'int'>,id:1546022336,value:0
type:<class 'int'>,id:1546022368,value:1
type:<class 'int'>,id:1546022400,value:2
type:<class 'int'>,id:1546022432,value:3
type:<class 'int'>,id:1546022464,value:4
type:<class 'int'>,id:1546022496,value:5
type:<class 'int'>,id:1546022528,value:6
type:<class 'int'>,id:1546022560,value:7
type:<class 'int'>,id:1546022592,value:8
type:<class 'int'>,id:1546022624,value:9
~~~

通常使用next方法循环取值。由于不存在Java中迭代器Iterator.hasNext()的函数，判断是否还有下一个元素只能通过异常来检查。就像这个：

~~~
while True:
    try:
        t = next(tupleGene1)
        xprint(t)
    except StopIteration:
        break
~~~
~~~
type:<class 'int'>,id:1546022336,value:0
type:<class 'int'>,id:1546022368,value:1
type:<class 'int'>,id:1546022400,value:2
type:<class 'int'>,id:1546022432,value:3
type:<class 'int'>,id:1546022464,value:4
type:<class 'int'>,id:1546022496,value:5
type:<class 'int'>,id:1546022528,value:6
type:<class 'int'>,id:1546022560,value:7
type:<class 'int'>,id:1546022592,value:8
type:<class 'int'>,id:1546022624,value:9
~~~

同样，元组的元组的推导，会产生一个迭代器的迭代器。比如下面的tuple转换，演示迭代器的元素又是另一个迭代器。
~~~
tupleGene2 = ((int(i) for i in str.split(',')) for i in str.split(','))
mytuple = tuple(tupleGene2)
for tup in mytuple:
    t = tuple(tup)
    xprint(t)
~~~