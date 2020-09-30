---
title: 权限转换的Shell脚本
author: Jie Chen
date: 2012-06-11
categories: [Linux]
tags: [shell]
---

使用chmod可以使用+,-或者=符号给u,g和o三组赋予权限。虽然字面上非常好理解，但是我还是最喜欢直接用八进制组一次性赋值。分享2个自己写的脚本，用来解析权限在字面和八进制之间的快速换算。


## 字面到八进制组的转换

命令方式为：

> p2o.sh permission_text

比如：p2o.sh rwxr-xr--

~~~
# p2o.sh
# permission to octal
# usage: p2o.sh rwxr-xr--
# author: jiechencn@qq.com

permission=$1
for (( i=0; i<${#permission}; i++ )) do
  p="${permission:$i:1}"
  case "$p" in
    r | w | x) 
		pv="1"
		;;
    - ) 
		pv="0"
		;;
  esac
  pvs=$pvs$pv
done

p1=`echo $pvs | cut -c 1-3`
p2=`echo $pvs | cut -c 4-6`
p3=`echo $pvs | cut -c 7-9`

echo "permission = "$permission
echo "binary group = "$pvs
echo "octal group = "$((2#$p1))$((2#$p2))$((2#$p3))
~~~


## 八进制组到字面的转换

用法：

> o2p.sh octal_group

比如：

o2p.sh 754

这段脚本有个注意的地方，就是每一个八进制数字转换成三位二进制的时候，必须左边补齐0。

~~~
# o2p.sh
# octal to permission
# usage: d2p.sh 731
# author: jiechencn@qq.com

octals=$1
for (( i=0; i<${#octals}; i++ )) 
do
  d=${octals:$i:1}
  b=`echo "obase=2;$d"|bc`
  
  b=`printf "%03d" $b`  # 左侧自动补齐0
  
  bs=$bs$b
  
  b1=`echo $b | cut -c 1`
  b2=`echo $b | cut -c 2`
  b3=`echo $b | cut -c 3`
  
  if [ $b1 == '1' ]
  then
    ps=$ps"r"
  else
    ps=$ps"-"
  fi
  if [ $b2 == '1' ]
  then
    ps=$ps"w"
  else
    ps=$ps"-"
  fi
  if [ $b3 == '1' ]
  then
    ps=$ps"x"
  else
    ps=$ps"-"
  fi
done

echo "octal group = "$octals
echo "binary group = "$bs
echo "permission = "$ps
~~~





## 最后看一下实际运行

~~~
[oracle@localhost shell]$ p2o.sh rwxr-xr--
permission = rwxr-xr--
binary group = 111101100
octal group = 754
~~~

~~~
[oracle@localhost shell]$ o2p.sh 754
octal group = 754
binary group = 111101100
permission = rwxr-xr--
~~~
