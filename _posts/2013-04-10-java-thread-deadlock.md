---
title: 线程分析-DeadLock
author: Jie Chen
date: 2013-04-10
categories: [Java]
tags: [threaddump]
---

Java中谈线程问题必谈死锁，这种现象很奇怪。因为死锁的发生还是比较少见的，它是经典的线程问题但绝对不是常见的。它一般不会引起CPU使用过高，相反地，它会使得服务器停止对客户请求的响应。死锁的问题很容易就能从Thread Dump中识别出来。

这里有一点需要明确的是：死锁和CPU过高使用没有必然联系。但是网络上经常讲CPU过高和Java线程就归纳到死锁上来。这是不正确的。线程问题有很多种类，死锁只是其中一种很少见的情况。

下面通过具体的代码来演示死锁的产生，并通过Thread Dump来分析死锁的成因。经典的死锁问题是两个线程对资源的交叉利用和互斥产生的，复杂的情况是三个以上线程对资源的互斥竞争。多年以来我从来没有碰到过这种情况。下面的代码演示2个线程。

## 演示代码

线程0和线程1互相对资源x和y进行互斥型的竞争。

	public class DeadLock implements Runnable {
		public int i = 1;
		static Object x = new Object(), y = new Object();

		public void run() {
			System.out.println("current thread=" + i);
			if (i == 0) {
				synchronized (x) { 
					try {
						Thread.sleep(500);
					} catch (Exception e) {
					}
					synchronized (y) {
						System.out.println("locked y");
					}
				}
			}
			if (i == 1) {
				synchronized (y) { 
					try {
						Thread.sleep(500);
					} catch (Exception e) {
					}
					synchronized (x) {
						System.out.println("locked x");
					}
				}
			}
		}

		public static void main(String[] args) {
			DeadLock test0 = new DeadLock();
			DeadLock test1 = new DeadLock();
			test0.i = 0;
			test1.i = 1;
			Thread t1 = new Thread(test0);
			Thread t2 = new Thread(test1);
			t1.start();
			t2.start();
		}
	}


执行结果标明 “locked x”和“locked y”始终没有打印出来，程序也一直无法正常退出。

![](/assets/res/java_deadlock_con.png)

## 成因分析

Thread-1等待y的资源锁0x00000007d663a588，但是该锁0x00000007d663a588被Thread-0持有，同时Thread-0等待x的资源锁0x00000007d663a598，而0x00000007d663a598又被Thread-1所持有。在这个案例中，“waiting for monitor entry”提示非常重要。注意“BLOCKED”，BLOCKED是对线程瞬间状态的描述，而不是说这个线程有问题，不是说这个线程是死锁的成因。网上很多的分析网站开篇就将BLOCKED，这个也是不对的。

![](/assets/res/java_deadlock_td1.png)

死锁很容易被Thread Dump识别，不需要人工分析。下面的Dump输出就是。

![](/assets/res/java_deadlock_td2.png)

## 明确的几点

注意以下情况需要明确的：
	> * 死锁一般不会引起CPU过高使用问题
	> * 死锁通常使服务器停止响应请求
	> * 线程中的BLOCKED标识不是判断线程问题的关键





