---
title: 线程分析-线程池溢出
author: Jie Chen
date: 2013-04-27
categories: [Java]
tags: [threaddump]
---

Java中的线程池溢出，是个比较难诊断的问题。它的成因是多个任务（比如HTTP请求）等待执行，因为每个thread在一个时间点上只能执行一个task，如果线程池最多可以容纳20个线程，涌进来30个任务，那么只有20个任务才能得到执行，剩下的10个必须等待池中出现闲置的线程。如果有一些执行的任务很耗时，后面的任务将长时间得不到线程资源供执行，线程池溢出的问题就会发生。

很多web服务器都提供了线程池的技术，可以动态扩展，但都有一定的限额。下面的代码演示线程池溢出的问题，代码相对复杂，但能很好地解释这个现象。

## 代码演示

定义一个ThreadPool的类，负责创建一组有限数量的线程，他们将被用来执行排队中的任务。注意任务的数量必须大于线程的数量。这个类的方法定义：

* ThreadPool(): 构造器初始化一组线程，这些线程都是PooledThread的实例
* runTask(): 获取一个闲置的可用的线程来执行一个任务
* getTask(): 获取下一个将要执行的任务
* close(): 强迫终止所有的线程
* join(): 停止线程，等待正在执行的任务线程自然地停止

ThreadPool定义一个内部类，PooledThread，这是具体的线程类，用来执行任务。PooledThread定义：

	private class PooledThread extends Thread {

        public PooledThread() {
            super(ThreadPool.this, "Zigzag_Thread-" + (threadID++));
        }

        public void run() {
            while (!isInterrupted()) {

                // get a task to run
                Runnable task = null;
                try {
                    task = getTask();
                } catch (InterruptedException ex) {
                }

                // if getTask() returned null or was interrupted,
                // close this thread by returning.
                if (task == null) {
                    return;
                }

                // run the task, and eat any exceptions it throws
                try {
                    task.run();
                } catch (Throwable t) {
                    uncaughtException(this, t);
                }
            }
        }
    }
	
	
完整的线程池定义：

	class ThreadPool extends ThreadGroup {

		private boolean isAlive;
		private LinkedList taskQueue;
		private int threadID;


		public ThreadPool(int numThreads) {
			super("Zigzag_ThreadPool");
			setDaemon(true);

			isAlive = true;

			taskQueue = new LinkedList();
			for (int i = 0; i < numThreads; i++) {
				new PooledThread().start();
			}
		}

		
		public synchronized void runTask(Runnable task) {
			if (!isAlive) {
				throw new IllegalStateException();
			}
			if (task != null) {
				taskQueue.add(task);
				notify();
			}

		}

		protected synchronized Runnable getTask() throws InterruptedException {
			while (taskQueue.size() == 0) {
				if (!isAlive) {
					return null;
				}
				wait();
			}
			return (Runnable) taskQueue.removeFirst();
		}

		
		public synchronized void close() {
			if (isAlive) {
				isAlive = false;
				taskQueue.clear();
				interrupt();
			}
		}


		public void join() {
			synchronized (this) {
				isAlive = false;
				notifyAll();
			}

			Thread[] threads = new Thread[activeCount()];
			int count = enumerate(threads);
			for (int i = 0; i < count; i++) {
				try {
					threads[i].join();
				} catch (InterruptedException ex) {
				}
			}
		}


		private class PooledThread extends Thread {

			public PooledThread() {
				super(ThreadPool.this, "Zigzag_Thread-" + (threadID++));
			}

			public void run() {
				while (!isInterrupted()) {

					// get a task to run
					Runnable task = null;
					try {
						task = getTask();
					} catch (InterruptedException ex) {
					}

					// if getTask() returned null or was interrupted,
					// close this thread by returning.
					if (task == null) {
						return;
					}

					// run the task, and eat any exceptions it throws
					try {
						task.run();
					} catch (Throwable t) {
						uncaughtException(this, t);
					}
				}
			}
		}
	}

客户执行类

	public class OutOfThread {
		public static void main(String[] args) {
			if (args.length != 2) {
				System.out.println("Tests the ThreadPool task.");
				System.out.println("Usage: java OutOfThread numTasks numThreads");
				System.out.println("  numTasks - integer: number of task to run.");
				System.out.println("  numThreads - integer: number of threads in the thread pool.");
				return;
			}
			int numTasks = Integer.parseInt(args[0]);
			int numThreads = Integer.parseInt(args[1]);

			// create the thread pool
			ThreadPool threadPool = new ThreadPool(numThreads);

			// run example tasks
			for (int i = 0; i < numTasks; i++) {
				threadPool.runTask(createTask(i));
			}

			// close the pool and wait for all tasks to finish.
			threadPool.join();
		}

		/**
		 * Creates a simple Runnable that prints an ID, waits a long time, then
		 * prints the ID again.
		 */
		private static Runnable createTask(final int taskID) {
			return new Runnable() {
				public void run() {
					System.out.println("Task " + taskID + ": start");

					// simulate a long-running task
					try {
						int i = 0;
						while (i<9999999L*2000)
							i++;

					} catch (Exception ex) {
					}

					System.out.println("Task " + taskID + ": end");
				}
			};
		}
	}

执行这段代码，比如定义线程池里有3个线程，安排做5个任务。最完美的执行结果就是类似下面的，5个任务全部完成。

	Task 2: start
	Task 0: start
	Task 1: start
	Task 0: end
	Task 3: start
	Task 2: end
	Task 4: start
	Task 1: end
	Task 3: end
	Task 4: end

而实际的运行结果是：

	E:\>java -classpath . zigzag.research.threaddump.OutOfThread 5 3
	Task 2: start
	Task 0: start
	Task 1: start
	Task 2: end
	Task 3: start

在长时间里，Task 4一直分配不到空闲的线程，长时间得不到执行。这就是线程池溢出的问题。

## 线程分析

Thread Dump中， Zigzag_Thread-3，Zigzag_Thread-1 和 Zigzag_Thread-0 状态为 "RUNNABLE"。这个线程堆的输出结果非常良好，线程们都很正常，无法判断是否有问题。多次输出Thread Dump，就会发现一些端倪来。Zigzag_Thread-2没有出现在输出中，是因为它已经结束。可是Zigzag_Thread-4永远没有出现，因为一直轮不到它。

	"Zigzag_Thread-3" prio=6 tid=0x00000000069d5800 nid=0x26b4 runnable [0x000000000744f000]
	   java.lang.Thread.State: RUNNABLE
			at zigzag.research.threaddump.OutOfThread$1.run(OutOfThread.java:45)
			at zigzag.research.threaddump.ThreadPool$PooledThread.run(OutOfThread.java:183)

	"Zigzag_Thread-1" prio=6 tid=0x00000000069d5000 nid=0x11b4 runnable [0x000000000734f000]
	   java.lang.Thread.State: RUNNABLE
			at zigzag.research.threaddump.OutOfThread$1.run(OutOfThread.java:45)
			at zigzag.research.threaddump.ThreadPool$PooledThread.run(OutOfThread.java:183)

	"Zigzag_Thread-0" prio=6 tid=0x00000000069d2000 nid=0x1a34 runnable [0x000000000724f000]
	   java.lang.Thread.State: RUNNABLE
			at zigzag.research.threaddump.OutOfThread$1.run(OutOfThread.java:46)
			at zigzag.research.threaddump.ThreadPool$PooledThread.run(OutOfThread.java:183)

为了解决这种情况，很多web服务器都提供了对线程池的配置和监控。比如下图为Oracle Application Server以及Oracle Weblogic控制台中的Thread Pool配置。

![](/assets/res/java_thread_oufofthread_oas.png)

![](/assets/res/java_thread_oufofthread_wls.png)
