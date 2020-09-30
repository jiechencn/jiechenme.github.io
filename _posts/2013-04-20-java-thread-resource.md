---
title: 线程分析-资源竞争
author: Jie Chen
date: 2013-04-20
categories: [Java]
tags: [threaddump]
---

Java线程问题中最为常见的成因是资源竞争，它导致的后果是服务器停止响应，或者CPU过高使用。资源竞争非常好理解，就是一个或者多个线程等待某个资源，而这个资源又被另一个线程所占有并长时间不能释放。对于这种情形，Thread Dump中的“waiting for monitor entry”就非常重要。

## 代码演示

我假设有这样一个例子，三个小孩分享同一块面包，但是必须排队一个接一个地分享。也就是第一位小孩先吃1/3，吃完后再让后面队伍中的第二个小孩吃1/3，最后应当是最后一个小孩吃剩下的最后1/3。考虑到每个小孩吃东西的速度，过慢的速度就导致资源的紧张，后面的小孩等不及了就烦躁了。我们定义一个面包，注意是static，因为只有一个，静态的。

	static Object bread = new Object();

每一个小孩持有面包的时候，花上几分钟时间吃1/3个。

	synchronized (bread) {
	  // eating for a few time
	}

最后形成这样的代码，排队等候吃面包。注意排队是同时的串行的，所以采用3个线程，而吃面包必须排队，是并行的，所以有先后。

![](/assets/res/java_thread_resource_code.png)

执行结果为：（上述代码中用了极端的时间延时，为了演示。）

	E:\>java -classpath . zigzag.research.threaddump.ResourceContention
	current boy: 1
	current boy: 2
	current boy: 3
	The boy 1 is eating the bread

三个小孩已经排好队，但只有第一个小孩在吃，长时间内都轮不到后面2个小孩。

## 分析

Thread Dump输出结果中，可以看到“waiting for monitor entry”，等待资源的获取。Thread-2和Thread-1一直在等待0x00000007d663a6d0面包，而这个0x00000007d663a6d0面包长时间被Thread-0所占有。Thread-0就是第一个小孩。

![](/assets/res/java_thread_resource_td.png)

## 嵌套竞争

另一种资源竞争的成因是：线程A等待线程B释放一个资源，但是线程B无法释放，因为它要等待线程C先释放另一个资源。这种嵌套的的竞争，需要理清哪个是源头。

 