---
title: 线程分析-CPU过高使用
author: Jie Chen
date: 2013-04-04
categories: [Java]
tags: [threaddump]
---

Java线程问题中有一类现象很常见，就是CPU过高使用。有时你能看到400%的使用率，这是因为多核的原因，实际也就是100%或者99%而已。什么样的情况导致CPU过高，很难讲，情况太多，比如硬件资源、不好的算法、太过持久的IO吞吐等，都可能是成因。下面通过简单的例子，演示如何从Thread Dump中找到有问题的那个线程，直到有问题的代码行。

## 代码例子

这个代码只是演示而已，没人会这么写。

	package zigzag.research.threaddump;

	public class HighCPU {
		public static void main(String[] args)
		{
			while(true){
				// do nothing
			}
		}
	}


## Linux上的线程

在Linux上运行这个代码，使用top，显示有问题的进程。注意这里显示的是进程ID。在top命令的窗口按键 Shift-H，将会显示具体的线程。top的帮助说明-H的使用意图是：

	-H : Threads toggle
		Starts top with the last remembered 'H' state reversed.  
		When this toggle is On, all individual threads will be displayed. 
		Otherwise, top displays a summation of all threads in a process.

下图中可以看到有问题的线程ID是3045 （这里的PID其实已经是ThreadID了）。3045是十进制，对应的十六进制为0xBE5。因此我们得知问题线程ID号。

![](/assets/res/java_thread_highcpu_top.png)
			
Thread Dump的输出结果为：

	"Finalizer" daemon prio=1 tid=0x08fb6428 nid=0xbea in Object.wait() [0xb59d5000..0xb59d60b0]
			at java.lang.Object.wait(Native Method)
			- waiting on(a java.lang.ref.ReferenceQueue$Lock)
			at java.lang.ref.ReferenceQueue.remove(ReferenceQueue.java:116)
			- locked(a java.lang.ref.ReferenceQueue$Lock)
			at java.lang.ref.ReferenceQueue.remove(ReferenceQueue.java:132)
			at java.lang.ref.Finalizer$FinalizerThread.run(Finalizer.java:159)

	"Reference Handler" daemon prio=1 tid=0x08fb5ea8 nid=0xbe9 in Object.wait() [0xb5a56000..0xb5a56e30]
			at java.lang.Object.wait(Native Method)
			- waiting on(a java.lang.ref.Reference$Lock)
			at java.lang.Object.wait(Object.java:474)
			at java.lang.ref.Reference$ReferenceHandler.run(Reference.java:116)
			- locked(a java.lang.ref.Reference$Lock)

	"main" prio=1 tid=0x08f128d0 nid=0xbe5 runnable [0xff80c000..0xff80c5e8]
			at zigzag.research.threaddump.HighCPU.main(HighCPU.java:6)

根据线程ID，很容易找出有问题的线程和具体对应的有问题的代码行。

	"main" prio=1 tid=0x08f128d0 nid=0xbe5 runnable [0xff80c000..0xff80c5e8]
			at zigzag.research.threaddump.HighCPU.main(HighCPU.java:6)
		

		
## Windows上的线程
在Windows上，获取操作系统级别的线程ID是有点困难的，使用微软额外提供下载的Process Explorer，就能得到解决。在Process Explorer中，明确到具体的线程，会以十进制的TID来标识，比如下图中的2708就对应Thread Dump中的十六进制0xA94。

![](/assets/res/java_thread_highcpu_explorer.png)

	"main" prio=6 tid=0x000000000054b000 nid=0xa94 runnable [0x00000000025cf000]
	   java.lang.Thread.State: RUNNABLE
			at zigzag.research.threaddump.HighCPU.main(HighCPU.java:6)

		
## 关于Thread ID

Java nid(Native Thread ID) = Linux Process = Windows Thread



