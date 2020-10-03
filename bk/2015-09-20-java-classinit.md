---
title: 类与成员变量的初始化
author: Jie Chen
date: 2015-09-20
categories: [Java,JVM]
tags: []
---

之前研究过变量（<a href="/2015/09/05/java-classinstancevariable" target="_blank" class="bodyA">类变量与实例变量的内存存储</a>），顺便这次分析一下类变量的初始化问题，也能从另外一个角度来证明类变量的内存分配的共享性质。

假设我定义一个ClassA，含有一个实例变量s，一个默认构造函数（无参构造器），一个有参构造函数，还有一个实例方法check(String)。
	
	public class ClassA {
		String s;
		public ClassA(){
			s = "hello";
			System.out.println("ClassA.ClassA(): s=" + s);
		}
		public ClassA(String name){
			System.out.println("ClassA.ClassA(Stirng name): name=" + name);
		}
		public void check(String name){
			System.out.println("ClassA.check(Stirng name): name= "+ name);
		}
	}

	
同时我在另外一个ClassB中大量引用ClassA。引用的方式我都是用三种方法：

* 不指定构造器
* 指定默认构造器
* 指定有参构造器

其中

* a11，a12，a13为实例变量
* a21，a22，a23为静态类变量
* a31，a32，a33为静态类变量
* sstatic1和sstatic2为静态字符串变量
* scommon为实例变量

ClassB中我交错打乱了它们的定义顺序。

同时在ClassB中定义了一些静态

	public class ClassB {
		ClassA a11;
		ClassA a12 = new ClassA();
		ClassA a13 = new ClassA("a13");
		String scommon = "common string";
		
		static ClassA a21;
		static{System.out.println("a21 = " + a21);}
		
		static ClassA a22 = new ClassA();
		static{System.out.println("a22 = " + a22);}
		
		static ClassA a23 = new ClassA("a23");
		static{System.out.println("a23 = " + a23);}
		
		static String sstatic1 = "static string1";
		static{System.out.println("sstatic1 = " + sstatic1);}
		
		static ClassA a31;
		static String sstatic2;
		static ClassA a32;
		static ClassA a33;
		static {
			System.out.println("a31 = " + a31);
			a32 = new ClassA("a32");
			System.out.println("a32 = " + a32);
			sstatic2 = new String("static string2");
			System.out.println("sstatic2 = " + sstatic2);
			a33 = new ClassA("a33");
			System.out.println("a33 = " + a33);
		}
		public ClassB(){
			System.out.println("ClassB.ClassB()");
		}

	}
	

## 引用静态变量

在调用类中，首先我使用ClassB.a33的静态变量的类引用去调用check(String)方法。
	
	
	public static void main(String args[]){
        ClassB.a33.check("from ClientMain");  
    }
	
	
得到的结果有一大堆。

	a21 = null
	ClassA.ClassA(): s=hello
	a22 = cn.xwiz.jvm.initial.ClassA@70dea4e
	ClassA.ClassA(Stirng name): name=a23
	a23 = cn.xwiz.jvm.initial.ClassA@5c647e05
	sstatic1 = static string1
	a31 = null
	ClassA.ClassA(Stirng name): name=a32
	a32 = cn.xwiz.jvm.initial.ClassA@33909752
	sstatic2 = static string2
	ClassA.ClassA(Stirng name): name=a33
	a33 = cn.xwiz.jvm.initial.ClassA@55f96302
	ClassA.check(Stirng name): name= from ClientMain

-----------------------

由于a33是静态变量，可以直接引用，而这个时候，JVM会先过滤掉所有的实例变量，而把所有的静态变量全部初始化一次。程序执行的流程是：

1. 第一个被初始化的是a21，因为没有指定构造器，所以无法为a21分配内存。
2. 第二个被初始化的是a22，因为指定了默认构造器ClassA()，JVM为ClassA创建了一个实例对象，并调用了该构造器方法，字符串s被赋值，并执行里面的打印语句。
3. 第三个被初始化的是a23，因为指定了有参构造器ClassA(String)，所以也创建了一个实例对象，调用了该构造器方法，并执行里面的打印语句。
4. 第四个被初始化的是sstatic1，静态字符串变量
5. 第五个被初始化的是a31，情况同a21一样，没有分配到内存，也就没有对象
6. 第六个被初始化的是a32，情况同a22一样
7. 第七个被初始化的是a33，情况同a23一样
8. 到这一步后，所有的静态变量全部被初始化，程序才真正执行 ClassB.a33.check(String)方法


如果换一种方式，我不调用静态变量的方法，我直接引用静态变量本身，比如下面：

    public static void main(String args[]){
        System.out.println(ClassB.sstatic1);
    }
	
	
得到的初始化步骤和上面也完全一致。

	a21 = null
	ClassA.ClassA(): s=hello
	a22 = cn.xwiz.jvm.initial.ClassA@70dea4e
	ClassA.ClassA(Stirng name): name=a23
	a23 = cn.xwiz.jvm.initial.ClassA@5c647e05
	sstatic1 = static string1
	a31 = null
	ClassA.ClassA(Stirng name): name=a32
	a32 = cn.xwiz.jvm.initial.ClassA@33909752
	sstatic2 = static string2
	ClassA.ClassA(Stirng name): name=a33
	a33 = cn.xwiz.jvm.initial.ClassA@55f96302
	static string1


## 引用实例变量

    public static void main(String args[]){
        ClassB b1 = new ClassB();       
    }


这一次，我直接定义ClassB的一个实例，指定默认构造器，会发现执行的结果非常相似。
	
	run:
	a21 = null
	ClassA.ClassA(): s=hello
	a22 = cn.xwiz.jvm.initial.ClassA@70dea4e
	ClassA.ClassA(Stirng name): name=a23
	a23 = cn.xwiz.jvm.initial.ClassA@5c647e05
	sstatic1 = static string1
	a31 = null
	ClassA.ClassA(Stirng name): name=a32
	a32 = cn.xwiz.jvm.initial.ClassA@33909752
	sstatic2 = static string2
	ClassA.ClassA(Stirng name): name=a33
	a33 = cn.xwiz.jvm.initial.ClassA@55f96302
	ClassA.ClassA(): s=hello
	ClassA.ClassA(Stirng name): name=a13
	ClassB.ClassB(): common string



这是因为JVM首先要对所有的静态变量一一初始化，所以上面任然会执行上面的第一到第七的步骤。而在这个结果里，唯一的不同是多了这三个输出。

	ClassA.ClassA(): s=hello
	ClassA.ClassA(Stirng name): name=a13
	ClassB.ClassB(): common string

其实这里有4个步骤：

1. 第一步，JVM初始化实例变量a11，因为没有指定构造器，所以无法分配内存，为null
2. 第二步，初始化a12，默认构造器
3. 第三步，初始化a13，有参构造器
4. 第四步，初始化scommon，分配字符串变量
5. 第五步，执行ClassB()构造器方法


## 混合引用静态变量和实例变量

    public static void main(String args[]){
        System.out.println(ClassB.sstatic1);
        System.out.println("-------------");
        ClassB b1 = new ClassB();
    }
	
查看结果

	a21 = null
	ClassA.ClassA(): s=hello
	a22 = cn.xwiz.jvm.initial.ClassA@70dea4e
	ClassA.ClassA(Stirng name): name=a23
	a23 = cn.xwiz.jvm.initial.ClassA@5c647e05
	sstatic1 = static string1
	a31 = null
	ClassA.ClassA(Stirng name): name=a32
	a32 = cn.xwiz.jvm.initial.ClassA@33909752
	sstatic2 = static string2
	ClassA.ClassA(Stirng name): name=a33
	a33 = cn.xwiz.jvm.initial.ClassA@55f96302
	static string1
	-------------
	ClassA.ClassA(): s=hello
	ClassA.ClassA(Stirng name): name=a13
	ClassB.ClassB(): common string

在这个执行里面，得出一个很重要的规律，静态变量只会被初始化一次，当ClassB()无参构造器初始化b1的时候，所以的静态成员不会再次被初始化。原因是我在<a href="/2015/09/05/java-classinstancevariable" target="_blank" class="bodyA">类变量与实例变量的内存存储</a>中分析的那样，静态变量在内存中是共享的，所以只会被分配一次。

## 一个类的初始化顺序

这三个实验，可以得出很好的结论。一个类的初始化顺序，有三个特点：

* 先按照定义的先后顺序初始化所有的静态变量-类变量，只此一次，并且静态变量被多个类实例共享
* 再按顺序初始化所有的非静态变量-实例变量
* 最后初始化相应的被指定的构造器
