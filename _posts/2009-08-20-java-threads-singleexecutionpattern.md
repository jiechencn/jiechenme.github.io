---
title: Java多线程 - 单一执行模式
author: Jie Chen
date: 2009-08-20
categories: [Java]
tags: [multithread]
---

## 用synchronized实现单一执行模式

多个线程在共享同一个资源时，用synchronized方法给资源加上同步锁，确保在同一个时间点上，只有唯一的一个线程占用该资源，这是单一执行模式的概念。同步锁可以附加在方法体上，给类实例this加锁，比如：

	public synchronized void myMethod(){
		//
	}

或者在方法体内，给共享资源对象加锁，

	public void myMethod(){
		synchronized(obj){
			//
		}
	}
	
synchronized同步锁在多线程内比较耗时，因为执行时需要消耗额外的计算：试探锁（等待，再试探），获取锁，释放锁。
	
## 死锁的形成

共享资源对象如果存在多个，很容易出现死锁。死锁逻辑概念不用多解释，它的存在条件只要满足下面2点就能形成，而且一定能形成死锁。

* 共享资源为一个以上
* 两个或者两个以上的线程对多个共享资源的获取顺序完全不一致时

下面的例子演示死锁如何形成以及如何消解死锁。

假设有两个人Tom和Jerry需要使用相同的刀和叉来吃饭。在这里，线程分别是Tom和Jerry，多个共享资源是刀和叉，此时第一个死锁条件满足。如果满足第二个条件即两人使用刀和叉的顺序不一致，就能形成死锁，导致谁也吃不到东西。

	public class EatThread extends Thread {
	  private String name;
	  private final Tool firstTool;
	  private final Tool secondTool;
	  public EatThread(String name, Tool firstTool, Tool secondTool){
		this.name = name;
		this.firstTool = firstTool;
		this.secondTool = secondTool;
	  }
	  public void run(){
		while(true){
		  eat();
		}
	  }
	  private void eat(){
		synchronized (firstTool){
		  System.out.println(name + " uses " + firstTool);
		  synchronized (secondTool){
			System.out.println(name + " uses " + secondTool);
			System.out.println(name + " is eating");
			System.out.println(name + " puts down " + secondTool);
		  }
		  System.out.println(name + " puts down " + firstTool);
		}
	  }
	}

.

	public class Tool {
	  private String handTool;
	  public Tool(String handTool){
		this.handTool = handTool;
	  }
	  public String toString(){
		return handTool;
	  }
	}

在Main客户类中，通过给两个线程（Tom和Jerry）赋予不同顺序的工具，让死锁条件满足。

	public class Main {
	  public static void main(String args[]){
		Tool spoon = new Tool("spoon");
		Tool fork = new Tool("fork");
		EatThread tom = new EatThread("Tom", spoon, fork);
		EatThread jerry = new EatThread("Jerry", fork, spoon);
		tom.start();
		jerry.start();
	  }
	}

执行结果很快进入死锁状态，程序僵持住了。

	Tom uses spoon
	Tom uses fork
	Tom is eating
	Tom puts down fork
	Tom puts down spoon
	Tom uses spoon               <-- deadlock
	Jerry uses fork              <-- deadlock

通过抓取Thread Dump，很容易就能捕获异常线程。下面的Thread-0表示Tom， Thread-1就是Jerry，分别都处于BLOCKED的状态。

	"Thread-1" #10 prio=5 os_prio=0 tid=0x011fbc00 nid=0x1318 waiting for monitor entry [0x156ef000]
	   java.lang.Thread.State: BLOCKED (on object monitor)
		at cn.xwiz.lab.thread.ch1.EatThread.eat(EatThread.java:24)
		- waiting to lock <0x048c5eb0> (a cn.xwiz.lab.thread.ch1.Tool)
		- locked <0x048c5ee8> (a cn.xwiz.lab.thread.ch1.Tool)
		at cn.xwiz.lab.thread.ch1.EatThread.run(EatThread.java:17)

	"Thread-0" #9 prio=5 os_prio=0 tid=0x011fe400 nid=0x156c waiting for monitor entry [0x155bf000]
	   java.lang.Thread.State: BLOCKED (on object monitor)
		at cn.xwiz.lab.thread.ch1.EatThread.eat(EatThread.java:24)
		- waiting to lock <0x048c5ee8> (a cn.xwiz.lab.thread.ch1.Tool)
		- locked <0x048c5eb0> (a cn.xwiz.lab.thread.ch1.Tool)
		at cn.xwiz.lab.thread.ch1.EatThread.run(EatThread.java:17)
		
	Java stack information for the threads listed above:
	===================================================
	"Thread-1":
		at cn.xwiz.lab.thread.ch1.EatThread.eat(EatThread.java:24)
		- waiting to lock <0x048c5eb0> (a cn.xwiz.lab.thread.ch1.Tool)
		- locked <0x048c5ee8> (a cn.xwiz.lab.thread.ch1.Tool)
		at cn.xwiz.lab.thread.ch1.EatThread.run(EatThread.java:17)
	"Thread-0":
		at cn.xwiz.lab.thread.ch1.EatThread.eat(EatThread.java:24)
		- waiting to lock <0x048c5ee8> (a cn.xwiz.lab.thread.ch1.Tool)
		- locked <0x048c5eb0> (a cn.xwiz.lab.thread.ch1.Tool)
		at cn.xwiz.lab.thread.ch1.EatThread.run(EatThread.java:17)

	Found 1 deadlock.


## 死锁的破解

了解死锁形成的两个条件，只要打破任意一个，就能让程序正常执行下去。

### 破坏条件1

让共享资源由2个变成1个，就能立刻解锁，但是代码会改动比较多，因为同步的对象数量变了。原先有2个工具刀和叉。如果将他们合并为一个对象组合Pair，两人只要共享该Pair就可以，而不用关心Pair到底由什么组成。

因此额外引入Pair，包装2个Tool。

	public class Pair {
	  private Tool firstTool;
	  private Tool secondTool;
	  public Pair(Tool firstTool, Tool secondTool){
		this.firstTool = firstTool;
		this.secondTool = secondTool;
	  }
	  public String toString(){
		return firstTool + " and " + secondTool;
	  }
	}

线程类只同步Pair对象

	public class EatThread extends Thread {
	  private String name;
	  private Pair pair;
	  public EatThread(String name, Pair pair){
		this.name = name;
		this.pair = pair;
	  }

	  public void run(){
		while(true){
		  eat();
		}
	  }

	  private void eat(){
		synchronized (pair){
		  System.out.println(name + " uses " + pair);
		  System.out.println(name + " is eating");
		  System.out.println(name + " puts down " + pair);
		}
	  }
	}

客户调用的类，也随之改变。

    Tool spoon = new Tool("spoon");
    Tool fork = new Tool("fork");

    Pair pair = new Pair(spoon, fork);
    EatThread tom = new EatThread("Tom", pair);
    EatThread jerry = new EatThread("Jerry", pair);

### 破坏条件2

第二个条件的破坏非常简单，让所有的类都保持不变，改变Main客户类的工具使用的顺序，是两个线程按照同一个顺序使用刀和叉，死锁消除。

    EatThread tom = new EatThread("Tom", spoon, fork);
    EatThread jerry = new EatThread("Jerry", spoon, fork);
	
	
	
	
	




