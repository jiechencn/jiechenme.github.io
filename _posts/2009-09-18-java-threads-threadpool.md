---
title: Java多线程 - 简单的线程池模式
author: Jie Chen
date: 2009-09-18
categories: [Java]
tags: [multithread]
---

在中间件等容器中，线程池是用的最多的管理线程与任务请求分配的方式。创建线程是需要消耗时间和内存的。在服务启动之初，就初始化一定数量的线程，其优点是保证这些数量的线程在内存设定的情况下一定会创建成功，并且节约了临时创建线程的时间。

线程池的工作原理大致为：为可以预期的大量请求创建一定数目的线程，当请求到来时，请求会被放入一个FIFO的列表中，线程池中的线程按照一定的优先调度获取这些请求，执行完后，再从请求列表中挨个获取下一个请求加以处理。在线程与请求处理过程中，会有两种情形出现：

* 一定数目的线程来不及处理大量的请求，请求会被挂起，直到线程有能力可以处理
* 没有请求到来，所有线程处于等待状态，等待请求列表中出现第一个请求

这两种情形，都可以通过wait()来模拟，并使用notify()来唤醒对方，条件已经满足。

这里涉及到几个类：

* 线程池管理
* 任务处理线程
* 请求线程
* 请求对象

先从主程序开始看，通过ThreadPoolManager线程池管理类创建一个线程池，池内分配2个线程，通过setup()初始化这2个线程。然后通过RequesterThread请求线程类模拟三个用户发出的任务请求，每个人发出的任务请求都是大量的。

	public class Main {
		public static void main(String args[]) {
			ThreadPoolManager tpMgr = new ThreadPoolManager(2);
			tpMgr.setup();  //启动池内的所有线程
			new RequesterThread("Tom", tpMgr).start();
			new RequesterThread("Jerry", tpMgr).start();
			new RequesterThread("Peter", tpMgr).start();
		}
	}

## 请求对象Task

	public class Task {
		private String requester;
		private int reqID;
		public Task(String requester, int reqID) {
			this.requester = requester;
			this.reqID = reqID;        
		}
		public void execute(){
			System.out.println(Thread.currentThread().getName() + " executes " + this);
			try{
				Thread.sleep(1000);
			}catch(Exception e){
				//
			}
		}
		public String toString(){
			return requester + " @taskID=" + reqID;
		}
	}


Task类，简单地通过execute()方法来假设执行一段工作，耗时1秒。在构造函数中，requester代表的是请求者名称， 就是Tom/Jerry/Peter等。reqID代表的是每个人发出的请求ID号。


## 请求线程RequesterThread

	public class RequesterThread extends Thread{
		private ThreadPoolManager tpMgr;
		RequesterThread(String name, ThreadPoolManager threadPoolManager) {
			super(name);
			this.tpMgr = threadPoolManager;
		}
		public void run(){
			for (int i=0; true; i++){
				tpMgr.putTask(new Task(getName(), i));
				try {
					Thread.sleep(50);
				} catch (InterruptedException e) {
				}
				;
			}
		}
	}

在Main主程序中，通过new RequesterThread("Tom", tpMgr).start()来创建Tom这个人发出的多个任务请求。在run()中的for循环中，不停地创建new Task()，i代表的是请求ID号，递增。for循环没有终止条件，模拟不停的用户请求。而新创建的任务通过ThreadPoolManager.putTask()来添加到FIFO的请求列表中。

## 任务处理线程WorkerThread

	public class WorkerThread extends Thread{
	   private ThreadPoolManager tpMgr;
	   public WorkerThread(String thName, ThreadPoolManager tpMgr){
		   super(thName);
		   this.tpMgr = tpMgr;
	   }
	   public void run(){
		   while(true){
			   Task task = tpMgr.getTask();
			   task.execute();
		   }
	   }
	}

WorkerThread正好和RequesterThread相反，它是从ThreadPoolManager内置的请求列表中按照先进先出通过getTask()获取一个任务，并执行。

## 线程池管理ThreadPoolManager

	public class ThreadPoolManager {
		private static final int MAX_REQUESTS = 10;
		LinkedList<Task> tasks = new LinkedList<Task>();
		
		private WorkerThread[] threadPool;
		
		// count: threads number
		public ThreadPoolManager(int threads) {
			threadPool = new WorkerThread[threads];
			for (int i=0; i<threads; i++){
				threadPool[i] = new WorkerThread("Thread-" + i, this);
			}        
		}
		public void setup(){
			for (int i = 0; i < threadPool.length; i++) {
				threadPool[i].start();
			}
		}

		public synchronized Task getTask() {
			while (tasks.isEmpty()){
				try {
					wait();
				} catch (InterruptedException e) {
				}
			}
			Task task = tasks.removeFirst();
			notifyAll();
			return task;
		}
		public synchronized void putTask(Task task){
			while (tasks.size() >= MAX_REQUESTS){
				try {
					System.out.println("!! New task blocked due to maximum tasks size: " + tasks.size());
					wait();
				} catch (InterruptedException e) {
				}
			}
			tasks.add(task);
			System.out.println("+ New task added, size: " + tasks.size());
			notifyAll();        
		}
	}


线程池管理是最复杂的，它负责处理线程池的管理，任务请求的先进先出列表等。

Main主程序中，通过ThreadPoolManager的构造函数和setup方法，初始化了一定数量的线程，放到池里。

在任务请求类中，for无限循环里，通过tpMgr.putTask(new Task(getName(), i));添加新建的任务。在具体的putTask()方法中，需要处理一个情形：任务列表中的数量超出线程池能接受的最大任务数。此时需要等待。等待的条件是任务数量小于最大任务数。

同样，在任务处理线程中，调用了ThreadPoolManager的getTask()。当任务请求列表为空时，线程暂挂，需要等待条件成熟：任务请求列表不为空。


## 执行过程

从执行结果中，可以很清晰地看到请求任务列表在空和满的情况下等待的状态。因为我们只创建了2个线程，所以线程池中只有Thread-0和Thread-1。

	+ New task added, size: 1
	Thread-1 executes Tom @taskID=0
	+ New task added, size: 1
	Thread-0 executes Jerry @taskID=0
	+ New task added, size: 1
	+ New task added, size: 2
	+ New task added, size: 3
	+ New task added, size: 4
	+ New task added, size: 5
	+ New task added, size: 6
	+ New task added, size: 7
	+ New task added, size: 8
	+ New task added, size: 9
	+ New task added, size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	Thread-1 executes Peter @taskID=0
	...
	...
	...
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	Thread-0 executes Peter @taskID=13
	+ New task added, size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	Thread-1 executes Jerry @taskID=11
	+ New task added, size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	Thread-0 executes Jerry @taskID=12
	+ New task added, size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	+ New task added, size: 10
	Thread-1 executes Tom @taskID=16
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	!! New task blocked due to maximum tasks size: 10
	...
	...


	
