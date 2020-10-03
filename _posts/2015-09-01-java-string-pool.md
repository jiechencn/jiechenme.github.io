---
title: String对象常量池和内存分配
author: Jie Chen
date: 2015-09-01
categories: [Java,JVM]
tags: []
---

创建String变量的时候，对象的产生和内存分配会因为创建方式不同而有显著的差别。

## 直接赋值

	String a1 = "hello";
	String a2 = "hello";
	String a3 = "hello";
	System.out.println("a1=a2?" + (a1==a2));
	System.out.println("a1=a3?" + (a1==a3));

创建a1的时候，JVM发现这是字符串常量的赋值操作。因为编译期间就已经把字面常量hello的对象保存在class文件结构中，存储在字符串常量池中。所以执行时首先从常量池中获取值相同（equals比较）的对象并返回引用，赋值给a1。a1保存在虚拟机栈中。

同样道理，a2和a3也是直接从字符串中返回对字面hello的对象的引用，保存在虚拟机栈中。

三个引用变量实际上都是指向同一个字符串常量池中hello对象。因为地址相同，所有==比较符的值均为true。

 
## 使用new

    String b1 = new String("world");
    String b2 = new String("world");
    System.out.println("b1=b2?" + (b1==b2));

world字面通过双引号标识，则JVM在编译期间就将这个字符串常量保存在Class结构中，存储在字符串常量池内。

运行期间，创建b1时，通过构造器new出一个String对象，存储在堆中，它并不管在常量池中是否存在相同字面的对象，JVM都会给新变量在Heap中创建一个新的内存空间。并将引用返回给b1，b1保存在栈中。

b2也是一样地在堆中new出一个新的String对象。

 
## 使用intern

    String c1 = new String("welcome");
    String c2 = c1.intern();
    String c3 = "welcome";
    System.out.println("c1=c2?" + (c1==c2));
    System.out.println("c2=c3?" + (c2==c3));

编译期间，字面welcome已经存储在字符串常量池中。运行期间，变量c1将在堆中创建welcome的一个新对象。这个对象和常量池中的welcome对象没有任何关系。

接着，c1.intern()将查找字符串常量池中是否存在字面为welcome的对象。发现已经存在，则直接将引用返回给c2。（如果没有找到，则将自身的字面值保存在字符串常量池中，并返回对这个字面对象的引用）

执行到c3时，因为是常量赋值操作，c3直接从常量池中获取字面welcome的引用。

c1变量指向堆中的welcome对象，c2和c3都指向常量池中的welcome对象。所以只有c2和c3才拥有相同的地址，并相等。

 
## 内存

![](/assets/res/java_string_pool_1.png)

从这个内存图中可以看到，第一种方法中产生的三个hello对象其实都是对字符串常量池中字面hello对象的引用，所以地址都相同均为0x781630f68

第二种方法产生的2个world对象，地址都不同。

第三种方法产生的三个welcome对象，只有c2和c3地址相同，因为他们都是对常量池中welcome字面对象的引用。而只有c1是指向堆中welcome对象的引用。













 