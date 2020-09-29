---
title: 用getopts处理全部的选项和参数
author: Jie Chen
date: 2012-07-25
categories: [Linux]
tags: [Shell]
---


用getopts可以编写出像模像样的很规范的获取命令行参数/选项的参数。根据它的使用方法，命令参数列表存放在 optstring 中，变量名为name，后续还可以跟上参数。 

~~~
getopts: usage: getopts optstring name [arg]
~~~

命令行的选项还分两种

* 带值的选项
* 不带值得选项（其实就是flag开关）

所以获取命令行需要处理三种数据

* 带值的选项
* 不带值得选项
* 参数列表

比如构造一段opt.sh，带值的选项有 -a -b -c， flag选项有 -x -y -z ，参数列表为 hello和world

执行opt.sh可能的情形为：
~~~
opt.sh -a 1 -b 2 -c 3 -xyz hello.txt world.txt
~~~
因为选项 a,b,c带值，所以需要用冒号构造 <kbd>a:b:c:</kbd>  ， x,y,z不需要带值，直接构造列表 <kbd>xyz</kbd> ,getopts通过while循环读取每一个选项，保存在opt(也就是变量name)中。

~~~
while getopts a:b:c:xyz opt
do
  case "$opt" in 
    a) a_value=$OPTARG ;;
	b) b_value=$OPTARG ;;
	c) c_value=$OPTARG ;;
	x) x_flag="true" ;;
	y) y_flag="true" ;;
	z) z_flag="true" ;;
  esac
done
~~~

当opt为a,b或c的时，使用 OPTARG环境变量获取这个选项之后的值。选项和选项值可能的输入为：<kbd>-a 1</kbd> 或者 <kbd>-a1</kbd> ，另外，<kbd>-x -y -z</kbd> 可以写成 <kbd>-xyz</kbd>, 这在各种bash命令中都是通用的。

处理完选项，需要处理[arg]列表。环境变量$OPTIND保存了参数列表所在的位置，所以我可以用shift快速地移掉前面所有的选项，只剩下参数列表，参数列表可以很容易通过$@获取。

~~~	
shift $[ $OPTIND - 1 ]	

for p in $@
do
  echo "$p"
done
~~~

最后，完整的程序是这样的。

~~~
#!/bin/bash

while getopts a:b:c:xyz opt
do
  case "$opt" in 
    a) a_value=$OPTARG ;;
	b) b_value=$OPTARG ;;
	c) c_value=$OPTARG ;;
	x) x_flag="true" ;;
	y) y_flag="true" ;;
	z) z_flag="true" ;;
  esac
done

echo "a_value=$a_value"
echo "b_value=$b_value"
echo "c_value=$c_value"
echo "x_flag=$x_flag"
echo "y_flag=$y_flag"
echo "z_flag=$z_flag"


shift $[ $OPTIND - 1 ]

for p in $@
do
  echo "$p"
done
~~~

运行结果
~~~
$ opt.sh -a 1 -b 2 -c 3  -xyz hello.txt world.txt
a_value=1
b_value=2
c_value=3
x_flag=true
y_flag=true
z_flag=true
hello.txt
world.txt
~~~

## 小细节

如果输入了不存在的选项列表，或者带值选项没有提供一个值，这段脚本会提示错误
~~~
$ opt.sh -a 1 -b 2 -d -c

./opt.sh: illegal option -- d
./opt.sh: option requires an argument -- c
~~~

隐藏这个错误提示，可以在选项列表前多加一个冒号，比如：
~~~
while getopts :a:b:c:xyz opt
~~~

