---
title: Java多线程 - 保护性暂挂
author: Jie Chen
date: 2009-09-07
categories: [Java]
tags: [multithread]
---

有一种多线程工作环境，要求一件事情的开始起源于另外一件事情提供的资源的就绪。比如Java Message消息队列Queue。只有发送者Sender把消息实体组装完毕发送到queue中时，Reciever才能从queue中获取消息加以处理。也就是说Receiver的工作前提是消息队列中至少不为空。一旦消息队列为空的情况发生，Receiver必须挂起，暂停执行，以保证queue中有消息就绪。这种暂挂的目的是为了保护queue的资源可用。

下面的例子模拟这样的消息队列的输入输出。假设消息实体为：

	public class Message {
	  String body;
	  Message(String name){
		body = name;
	  }
	  public String toString(){
		return body;
	  }
	}


消息队列我用LinkedList来存储消息的长列表，保证队列的FIFO不出错。

	public class MessageQueue {
	  private final LinkedList queue = new LinkedList();
	  public synchronized Message getMessage(){
		while (queue.size()<=0){
		  try {
			wait();
		  } catch (InterruptedException e) {
			e.printStackTrace();
		  }
		}

		return (Message)queue.removeFirst();
	  }

	  public synchronized void putMessage(Message msg){
		queue.addLast(msg);
		notifyAll();
	  }
	}

put和get分别加上同步锁。同时在get时，需要加上size()判断，在条件内wait()，起到保护性暂挂作用。等待的对象为queue中再次通过put方法被塞入一个或一个以上的消息，再让put方法显式地notifyAll来唤醒等待者。

这个是Sender线程，这里模拟消息的不停地发送1000次。发送的过程相当于往queue中塞消息。

	public class Sender extends Thread {
	  private MessageQueue queue;
	  public Sender(MessageQueue q){
		queue = q;
	  }
	  public void run(){
		for (int i=0; i<1000; i++){
		  Message msg = new Message("Message: " + i);
		  System.out.println("Sent " + msg);
		  queue.putMessage(msg);
		}
	  }
	}

而Receiver做的动作刚好相反。

	public class Receiver extends Thread{
	  private MessageQueue queue;
	  Receiver(MessageQueue q){
		queue = q;
	  }
	  public void run(){
		for (int i=0; i<1000; i++){
		  Message msg = queue.getMessage();
		  System.out.println("Received " + msg);
		}
	  }
	}
	

	
执行这样的消息队列非常简单，就是创建Sender和Receiver的两个线程并start他们。但是这个例子里我故意让Receiver先启动，并在3秒后才启动Sender，目的是为了观察MessageQueue.getMessage（）到底有没有起到保护性暂挂Reciver的作用。

	public class Main {
	  public static void main(String args[]){
		MessageQueue queue = new MessageQueue();

		new Receiver(queue).start();
		try {
		  Thread.sleep(3000);
		} catch (InterruptedException e) {
		  e.printStackTrace();
		}

		new Sender(queue).start();
	  }
	}

实验的结果可以看到，Receiver虽然先行执行了3秒钟，但还是需要等待Sender调用MessageQueue.putMessage()，因为MessageQueue的条件判断queue.size()<=0起到了保护作用。

	Sent Message: 0
	Received Message: 0
	Sent Message: 1
	Received Message: 1
	Sent Message: 2
	...
	...
	Sent Message: 998
	Sent Message: 999
	Received Message: 998
	Received Message: 999