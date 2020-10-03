---
title: 类变量与实例变量的内存存储
author: Jie Chen
date: 2015-09-05
categories: [Java,JVM]
tags: []
---

变量有类变量、实例变量和局部变量。局部变量比较好区别。类变量与实例变量的区别就在于前者在类中被定义时有static修饰符。 类变量属于类级别，因此在多个线程中引用时是共享的，因此只存在于一个内存区域。而实例变量仅仅属于类定义的具体实例，多个实例之间不共享实例变量，因此他们使用不同的内存地址。

这里就出现了一个问题。实例都是类的具体定义，都是保存在heap堆中，因为堆都是保存具体的对象，所以实例变量也一定会保存在heap堆中。heap堆中因此一定不会存储类变量，因为类变量不属于任何对象。废话那么多，那类变量储存在哪个内存区域呢？

通过具体的例子来看看就明白了。

	package cn.xwiz.lab.jvm.Static;

	public class ClassA {
		static String[] s1 = new String[99999];
		private String[] s2 = new String[88888];
		
		public ClassA() {
			//
		}
		public void doNothing(){
			System.out.println("do nothing");
		}
	}

在ClassA类中，我定义了s1的静态变量，它就是类变量；s2为非静态变量，他就是实例变量。变量也可以叫做成员，没什么区别。

	package cn.xwiz.lab.jvm.Static;

	import java.io.BufferedReader;
	import java.io.IOException;
	import java.io.InputStreamReader;

	public class Main {
		public static void main(String[] args) {
			ClassA a1 = new ClassA();
			ClassA a2 = new ClassA();
			ClassA a3 = new ClassA();
			a1.doNothing();
			a2.doNothing();
			a3.doNothing();
			try {
				new BufferedReader(new InputStreamReader(System.in)).readLine();
			} catch (IOException e) {
			}
		}
	}

main主程序中，创建三个实例（三个ClassA的具体对象）。根据前面的啰嗦，一定能够猜到，在heap中存在a1, a2, a3三个对象，每个对象持有一份s2实例变量，并且每个s2的实例变量的内存地址一定不同。

因此在内存堆中，清晰地列出了三个实例的三个实例变量。

![](/assets/res/java-jvm-classinstancevariable-1.png)

但是三个实例并没有列出那个99999的字符串数组。它肯定不再heap堆中，因为它在方法区中。注意看cn.xwiz.lab.jvm.Static.ClassA前面的"class"标识字符。

![](/assets/res/java-jvm-classinstancevariable-2.png)
	
方法区，在逻辑上和堆紧密联系，但又是一个特殊的区，一般叫做non-heap非堆，它保存虚拟机的类加载信息，常量和静态变量等，所有的线程都共享这块区域。在上面的第一张图中，如果点开cn.xwiz.lab.jvm.Static.ClassA，就能看到Class的类变量，拥有相同的内存地址：0x9d50000

![](/assets/res/java-jvm-classinstancevariable-3.png)