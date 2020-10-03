---
title: Linux如何优雅地输出Java线程堆
author: Jie Chen
date: 2019-04-30
categories: [Linux]
tags: [shell,threaddump]
---

诊断Java线程问题时，一直要求提供客户Java的线程堆。大部分时间都在指导客户怎么抓数据，非常浪费时间。曾经写了一个非常方便的脚本，但是用户一般都会纠结于参数的选择，效果不明显。

有兴趣的可以使用我的脚本 ajs2.sh

~~~

#!/bin/bash
#--------------------------------------------------------------------------------
#- File name:   ajs.sh - (A)uto (J)ava (S)tack
#- Purpose:     Collect Java Stacks automatically
#- Author:      jie.chen@oracle.com
#- Usage:       ./ajs.sh -p <process keyword> [interval in second] [times]
#--------------------------------------------------------------------------------
version=0.1
showApp(){
  echo -e "\t\t(A)uto (J)ava (S)tack\t(v"$version")"
}
showHelp(){
  echo
  showApp
  echo "Syntax:"
  echo -e "\t"$(basename $0)" -p <process keyword> [interval in second] [times]"
  echo "Example:"
  echo -e "\t1) for weblogic cluster managed:  "$(basename $0)" -p myweblogic-ManagedServer1 5 8"
  echo -e "\t2) for weblogic standlone:        "$(basename $0)" -p weblogic.Server"
  echo -e "\t3) for apache tomcat:             "$(basename $0)" -p catalina 5 8"
  echo -e "\t4) for common java:               "$(basename $0)" -p 1234 5 8"
  echo 
  #exit
}

# default parameters
pname="java" 
op="-p"
args=(5 8)
trcfile=$(basename $0)
pfilter=grep # to filter out "grep" itself

## read parameters from shell command line
while getopts HhP:p: opt
do
  case "$opt" in
  P | p) pname=$OPTARG
     op="-p";;
  H | h) showHelp
     exit;;
  *) ;;
  esac
done

i=0
shift $[ $OPTIND - 1 ]
for param in "$@"
do
  args[$i]=$param
  i=$i+1
done

tsleep=${args[0]}
ttimes=${args[1]}

getPDesc(){
  if [ -z "$pfilter" ] 
  then 
    ps -ef | grep $pname | grep -v $(basename $0)
  else
    ps -ef | grep $pname | grep -v $(basename $0) | grep -v $pfilter
  fi
}

pdesc=`getPDesc`

if [ -z "$pdesc" ]
then
  echo "!!! No matched process '$pname' found !!!"
  echo "Type command '$(basename $0) -h' for help"
  #showHelp
  exit
fi

pid=`echo $pdesc | cut -d ' '  -f 2`
trcfile=$trcfile"_"$pid".txt"

cat > $trcfile << EOF  # new file
`showApp`
--------------------------------------------------------------
$(basename $0) $op $pname $tsleep $ttimes
$pdesc
EOF

echo "PID" $pid "found" | tee -a $trcfile  ## append file

for ((i=1; i<=$ttimes; i++))
do
  printf "[%-40s]  %d%%\r" 'Collecting Java Stacks ... ' $[100/$ttimes*$i]
  echo '-------------- Thread Dumps '$i >> $trcfile
  jstack $pid >> $trcfile
  #jmap -heap $pid >> $trcfile
  if [ $i -ne $ttimes ]
  then
    sleep $tsleep
  fi
done

echo '-------------- JVM Env Settings' >> $trcfile
jinfo $pid >> $trcfile

printf "[%-40s]  %d%%" 'Collected ... ' $[100]
echo
echo "Dumped Java Stack Traces File: "`pwd`"/"$trcfile
~~~



## 少就是多=简洁


上面我已经尽量地让程序运行傻瓜化，但也一直在思考把工具写得更加简单，最近构造出一行简短的代码，让一些用户试了一下，效果很好。

~~~
pid=30994;t=5;jinfo $pid >$pid & watch -n $t "grep -c 'Full thread dump Java HotSpot(TM)' $pid;jstack $pid >> $pid"
~~~
其中：
> * pid：Java Process ID
> * t：抓取的秒数间隔

执行结果：
~~~
Every 5.0s: grep -c 'Full thread dump Java HotSpot(TM)' 30994;jstack 30994 >> 30994    Sat Apr 27 22:52:39 2019

8
~~~
最后一个数字 8 代表当前时间已经抓取到的线程堆次数。可以根据实际问题决定何时退出程序。
