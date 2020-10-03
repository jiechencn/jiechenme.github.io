---
title: finalize与资源释放/对象销毁
author: Jie Chen
date: 2015-09-15
categories: [Java,JVM]
tags: []
---

在Oracle Agile的代码中有一段非常不好的设计，就是关闭FTP连接释放socket资源的过程被定义在封装类的finalize()方法中。比如下面的代码。
   
	private void disconnect() throws IOException {
	   // Disconnect from FTP site
	   if (this.m_connected) {
		   this.m_ftp.disconnect();
		   this.m_ftp = null;
		   this.m_connected = false;
	   }
	}


	@Override
	protected void finalize() throws IOException {
	   disconnect();
	}

## finalize()的已知问题
   
finalize()是个protected方法，只能被JVM的GC调用到。而何时会发生GC，并不是程序能够控制的。即便System.gc()被额外调用，如果没有内存方面的需求，就不能保证GC一定会执行。它造成的后果是，一旦有大量的封装类的实例同时运行，会产生的大量的FTP连接，无法释放。

## 演示finalize()的缺陷

finalize()现在都尽量不使用，因为负面效应太明显了：资源无法及时释放。下面演示一下finalize()的弊端。

	package cn.xwiz.jvm.finalizer;

	public class ClassA {
		private String bigString;

		public String getBigString() {
			return bigString;
		}

		public void setBigString(String bigString) {
			this.bigString = bigString;
		}
		
		@Override
		protected void finalize(){
			bigString = null; // close big objects
			System.out.println("big objects closed");
		}
	}


在ClassA中，定义finalize()方法，用来关闭一个大的对象。我期望的结果是：当ClassA的实例被回收时，GC能够快速执行finalize()方法，同时释放实例中的bigString。

通过下面的a=null来试图销毁ClassA对象。然而即使我等待20秒，依然没有看到finalize()被调用。原因很简单，在当前内存充裕的运行时里，GC没有发生的必要。

	public class ClientA {
		public static void main(String args[]){
			try {
				ClassA a = new ClassA();
				a.setBigString("hello a");
				System.out.println(a.getBigString());
			
				a = null;
					   
				Thread.sleep(20000);
			} catch (InterruptedException ex) {
				ex.printStackTrace();
			}
		}
	}


如果通过System.gc的显式调用，会增加GC发生的概率，但也并一定能确保一定会发生GC。

	a = null;

	System.gc();
	Runtime.getRuntime().runFinalization();

理想情况下，有可能会发生一次GC调用finalize()。很幸运啊。
			
	hello a
	big objects closed
			

## 杜绝使用finalize（）
			
为了避免finalize()这样的不可靠的问题，可以通过显式的额外方法，比如定义一个release()方法，关闭大的对象或者资源。同时设置一个布尔变量，表示已经释放。其他方法的开头都必须检查该布尔变量，判断实例本身是否还持有该资源或者已经被关闭。
	
	package cn.xwiz.jvm.finalizer;

	public class ClassB {
	   private String bigString;
	   private boolean released = false;

		public String getBigString() {
			if (released)
				throw new IllegalStateException("object is already released");
		  
			return bigString;
		}

		public void setBigString(String bigString) {
			this.bigString = bigString;
		}
		
		public void release(){
			if (!released){
				bigString = null; // close big objects
				released = true;
				System.out.println("big objects closed");
			}
		} 
	}

确保在调用类中，每次使用完对象都调用一次release()，比如可以在try{}finally{}中。

	public class ClientB {
		public static void main(String args[]){
			ClassB b = new ClassB();
			try {
				b.setBigString("hello b");
				System.out.println(b.getBigString());
			} finally{
				b.release();
				b = null;
			}
		}
	}

这样每次都能确保执行release()，及时快速释放资源。

	hello b
	big objects closed
	
