---
title: 用$*和$@读取全部参数的细节测试
author: Jie Chen
date: 2012-08-08
categories: [Linux]
tags: [shell]
---


写完<a href="/posts/linux-getopts" class="bodyA" target="_blank">用getopts处理全部的选项和参数</a></a>后，我注意到了获取所有参数的特殊变量$@，要不要加双引号，对参数的处理有很大影响。另外linux还是有个也是读取所有参数的特殊变量$*，引用他们时，加不加双引号变化很大。索性一并测试一下，总结一下它们的规律特点，分别是:

* <kbd>$*</kbd>
* <kbd>"$*"</kbd>
* <kbd>$@</kbd>
* <kbd>"$@"</kbd>

## $*

* 不加双引号：参数列表会被作为一个单词列表处理
* 加双引号：参数列表会被作为一个整体字符串


## $@

无论加不加双引号，行为都是一样。表现差异取决于参数列表本身有没有双引号。

* 参数列表整体没有双引号：参数列表会被作为一个单词列表处理
* 参数列表整体加了双引号：参数列表会被作为一个一个整体字符串


## 测试代码

~~~
#!/bin/bash
echo 'read parameters in $*'
for p in $*
do
  echo $p
done

echo

echo 'read parameters in "$*"'
for p in "$*"
do
  echo $p
done

echo

echo 'read parameters in $@'
for p in "$@"
do
  echo $p
done

echo

echo 'read parameters in "$@"'
for p in "$@"
do
  echo $p
done
~~~


## 测试结果

参数列表不加双引号
~~~
$allparas.sh a b c d e
read parameters in $*
a
b
c
d
e

read parameters in "$*"
a b c d e

read parameters in $@
a
b
c
d
e

read parameters in "$@"
a
b
c
d
e
~~~
参数列表加了双引号后
~~~
$ allparas.sh "a b c d e"
read parameters in $*
a
b
c
d
e

read parameters in "$*"
a b c d e

read parameters in $@
a b c d e

read parameters in "$@"
a b c d e
~~~

在实际编写shell时，这两种变量和不同的引用方式，没有对错之分，选择适合自己的需求才是正确的。

