---
title: Java IO - PipedInputStream和PipedOutputStream的多线程读写
author: Jie Chen
date: 2015-05-28
categories: [Java]
tags: []
---

PipedInputStream和PipedOutputStream通过pipe使得同一个JVM进程中的不同线程下的两个输入输出流可以交换数据。Pipe流设计时有下面的限制。

* 输入流不能比输出流先关闭
* 输入流必须完整无缺地读到全部的输出流数据，不能只读一部分
* 输出流和输出流必须显式地调用close()关闭
* PipedInputStream的读处于阻塞状态，即等待PipedOutputStream写出数据后才能写入

上面的第4点，限制了我们务必要把这两个类的读写放在不同的线程中，否则出现“deadlock”。这里的“deadlock”在JDK文档中的定义时间是1994年左右，我认为这个描述是不精确的，只会出现waiting for monitor状态的无限制等待状态，但那不叫死锁。怎么可能会死锁呢，我读了PipedOutputStream源代码，发现它的输出不依赖于PipedInputStream的任何条件。

JDK1.0中的原话为James Gosling所注释

	 * Attempting to use
	 * both objects from a single thread is not
	 * recommended, as it may deadlock the thread.
	 
 
另外逻辑上的读写是：输出流输出数据保存在1024字节（默认，可通过构造函数修改）的buffer中，当缓存满时，通知输入流读入；或者输入流的线程获得执行时间时，即便缓存没有满也会马上读入。

对于上述限制中的第三点，即 输出流和输出流必须显式地调用close()关闭。可以通过下面的例子来观察比较有意思的错误。在开始之前，先构造出两个不同的线程来处理读写。

## PipeReaderThread线程

	package cn.xwiz.lab.io;

	import java.io.PipedInputStream;

	public class PipeReaderThread implements Runnable {

	  private PipedInputStream pin;
	  private int wait;

	  public PipeReaderThread(PipedInputStream pi, int wait) {
		pin = pi;
		this.wait = wait;
	  }

	  @Override
	  public void run() {
		try {
		  byte[] bs = new byte[1024];

		  int r = -1;

		  System.out.println("Reading ... ");

		  while ((r = pin.read(bs)) > 0){
			String s = new String(bs, 0, r);
			System.out.println("Read: " + s);
			synchronized (this) {
			  wait(wait);
			}
		  }
		  pin.close();
		  
		} catch (Exception e) {
		  e.printStackTrace();
		}

	  }
	}


## PipeWriterThread线程

	package cn.xwiz.lab.io;

	import java.io.PipedOutputStream;

	public class PipeWriterThread implements Runnable {

	  static final String s = "xwiz.cn";
	  private PipedOutputStream pout;

	  private int wait, loop;
	  public PipeWriterThread(PipedOutputStream po, int wait, int loop) {
		pout = po;
		this.wait = wait;
		this.loop = loop;
	  }

	  @Override
	  public void run() {
		try {

		  for(int i = 0; i<loop; i++) {
			System.out.println("Writing..." + i);
			pout.write(s.getBytes());
			System.out.println("Wrote");
			synchronized (this) {
			  wait(wait);
			}
		  }
		  pout.close();

		} catch (Exception e) {
		  e.printStackTrace();
		}


	  }
	}


## 调用类线程main函数


	PipedInputStream pin = new PipedInputStream();
	PipedOutputStream pout = new PipedOutputStream();
	pin.connect(pout);

	PipeReaderThread prThread = new PipeReaderThread(pin, 1);
	PipeWriterThread pwThread = new PipeWriterThread(pout, 1, 10);

	new Thread(pwThread).start();
	new Thread(prThread).start();
	  
	  
上述代码的逻辑就是：构造两个读写线程，写线程按照给定频率和次数输出“xwiz.cn”字符串，比如每1毫秒输出一次，连续输出10次。读线程是每1毫秒执行read去读。

## 让Reader比Writer快1倍

假设读的线程要比写的线程执行来的快，因为读处于阻塞状态，即等待状态，既然无数据可读，那么读线程就会空等，一旦等到数据后马上输出，不用理会buffer是否塞满1024字节。这里的快慢执行可以通过优先级控制，但我用wait()做演示，能更精确地控制他们的执行速度。

      PipeReaderThread prThread = new PipeReaderThread(pin, 1000);
      PipeWriterThread pwThread = new PipeWriterThread(pout, 2000, 10);

      new Thread(pwThread).start();
      new Thread(prThread).start();
	  
注释掉pout.close()执行结果为：
	  
	Writing...0
	Reading ... 
	Wrote
	Read: xwiz.cn
	Writing...1
	Wrote
	Read: xwiz.cn
	Writing...2
	Wrote
	Read: xwiz.cn
	Writing...3
	Wrote
	Read: xwiz.cn
	Writing...4
	Wrote
	Read: xwiz.cn
	Writing...5
	Wrote
	Read: xwiz.cn
	Writing...6
	Wrote
	Read: xwiz.cn
	Writing...7
	Wrote
	Read: xwiz.cn
	Writing...8
	Wrote
	Read: xwiz.cn
	Writing...9
	Wrote
	Read: xwiz.cn
	java.io.IOException: Pipe broken
		at java.io.PipedInputStream.read(PipedInputStream.java:321)
		at java.io.PipedInputStream.read(PipedInputStream.java:377)
		at java.io.InputStream.read(InputStream.java:101)
		at cn.xwiz.lab.io.PipeReaderThread.run(PipeReaderThread.java:26)
		at java.lang.Thread.run(Thread.java:745)




## 让Writer比Reader快1倍

这是另外一种情形。

	PipeReaderThread prThread = new PipeReaderThread(pin, 2000);
	PipeWriterThread pwThread = new PipeWriterThread(pout, 1000, 10);

	new Thread(pwThread).start();
	new Thread(prThread).start();

注释掉pout.close()执行结果为：

	Writing...0
	Wrote
	Reading ... 
	Read: xwiz.cn
	Writing...1
	Wrote
	Read: xwiz.cn
	Writing...2
	Wrote
	Writing...3
	Wrote
	Read: xwiz.cnxwiz.cn
	Writing...4
	Wrote
	Writing...5
	Wrote
	Writing...6
	Wrote
	Read: xwiz.cnxwiz.cnxwiz.cn
	Writing...7
	Wrote
	Writing...8
	Wrote
	Read: xwiz.cnxwiz.cn
	Writing...9
	Wrote
	Read: xwiz.cn
	java.io.IOException: Write end dead
		at java.io.PipedInputStream.read(PipedInputStream.java:310)
		at java.io.PipedInputStream.read(PipedInputStream.java:377)
		at java.io.InputStream.read(InputStream.java:101)
		at cn.xwiz.lab.io.PipeReaderThread.run(PipeReaderThread.java:26)
		at java.lang.Thread.run(Thread.java:745)



上述两种情况非常相似，无非是在没有关闭输出流的情况下，改变了读写的快慢速度，就出现了不同的异常信息。 其实他们的意思都是相似的，就是线程停止后输出流非正常关闭，第二个线程的read无法正常读取。只所以用两个不同的异常，我的理解就是：为了区别错误情形。

