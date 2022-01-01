---
title: 为什么对super的调用必须是类构造器中的第一个语句
author: Jie Chen
date: 2015-09-30
categories: [Java,JVM]
tags: []
---

仔细研究（<a href="/posts/java-classinit" target="_blank" class="bodyA">类与成员变量的初始化</a>），可以解释很多疑问。这里顺便分析一下Java界一直存在的疑惑。

>为什么对super的调用必须是当前类构造器函数中的第一个语句？

先说明一下JLS语言规范对super调用的规则

* 如果父类存在默认构造函数（就是无参构造函数），子类的构造函数中，Java会隐式地在第一行语句前填入 super(); 用户也可以显式地调用 super(); 且必须保证是第一个语句。
* 如果父类不存在默认构造函数，但存在有参构造函数，那么在子类的构造函数中，程序员必须显式地调用父类的有参构造函数，如： super(xxx);

第一种情况下，Grandpa是个父类，存在无参构造函数，就是默认的构造函数。

	public class Grandpa {
		protected String address = "China";
		public Grandpa(){
			System.out.println("Grandpa lives in " + address);
		}
	}

在子类Father中，Father的构造函数中，第一行语句可以是super();，也可以不写，编译器编译的时会隐式地填入super();来调用父类Grandpa的默认构造函数。

	public class Father extends Grandpa{
		public Father(String addr){
			super(); //这一行可以写，也可以不写。
			address = addr;
			System.out.println("Father lives in " + address);
		}
	}



第二种情况下， Son是Father的子类，由于Father没有默认构造函数，只有有参构造函数，则在Son中必须显式地调用 super(addr)，来初始化父类。

	public class Son extends Father{
		public Son(String addr){
			super(addr); // 必须写
			System.out.println("Son lives in " + address);
		}
	}


现在问题来了，我可不可以这么写？将第一句改为其他。

	public class Son extends Father{
		public Son(String addr){
			System.out.println("Son lives in " + address);
			super(addr);
		}
	}

JLS语言规范不允许这么做，一般编辑环境会直接报错：

	对super的调用必须是构造器的第一个语句

用我前面的文章[类与成员变量的初始化]("/posts/java-classinit"){:target="_blank"}来解释就非常容易。

父类必须初始化后，子类才能在自己的构造器中引用父类的成员变量（类变量、实例变量）、成员方法。就像在上述错误类中的第一个语句一样， 打印的address还没有初始化，内存都没有建立。

有人会说：我保证不在子类中引用父类不就可以避免空引用了吗？这么解释其实完全错误。设计继承关系，子类肯定会对父类有引用，这么小心地规避还不如设计两个毫无关联的类。

与其让所有的程序员小心规避这种不存在的引用关系，还不如JLS从语言规范级别直接限定。


