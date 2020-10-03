---
title: Java中static依赖导致的IncompatibleClassChangeError
author: Jie Chen
date: 2017-09-25
categories: [Java,JVM]
tags: []
---

处理客户的一个问题请求时，碰到了一个错误。

	Expected static field com.agile.extract.server.text.TextOutputStreamWriter.formatter
	java.lang.IncompatibleClassChangeError: Expected static field com.agile.extract.server.text.TextOutputStreamWriter.formatter


根据官方的JDK API解释，大概的意思是：当前class所依赖的某些定义，在被依赖的类中做了修改，导致当前类的方法无法执行。花了一点时间分析了一下，通过一个例子，重现了问题。记录下来。

根据API的解释，两个类有依赖关系，最简单的关系可能就是继承关系。

首先定义一个父类，并且定义静态成员字符串 s 。
 

	package cn.xwiz.jvm.incomp;

	public abstract class ParentClass {
		 protected static final String s = new String("hello xwiz.cn");
	}


定义依赖类，子类，在实例方法中直接引用父类的s。

	package cn.xwiz.jvm.incomp;

	public class ChildClass extends ParentClass{
		public ChildClass(){
			System.out.println("s=" + s);
		}
	}


创建一个执行类，调用子类去执行父类的静态调用。

	package cn.xwiz.jvm.incomp;

	public class Client {
		public static void main(String args[]){
			ChildClass cc = new ChildClass();
		}
	}

执行结果非常简单。

	java -classpath . cn.xwiz.jvm.incomp.Client
	s=hello xwiz.cn


接下来，修改父类的静态成员s，去除static标识。

	package cn.xwiz.jvm.incomp;

	public abstract class ParentClass {
		 protected final String s = new String("hello xwiz.cn");
	}

重新编译这个父类，注意此时我们依然使用先前的那个子类ChildClass，而并不是重新编译的子类。


	java -classpath . cn.xwiz.jvm.incomp.Client
	Exception in thread "main" java.lang.IncompatibleClassChangeError: Expected static field cn.xwiz.jvm.incomp.ChildClass.s
			at cn.xwiz.jvm.incomp.ChildClass.<init>(ChildClass.java:5)
			at cn.xwiz.jvm.incomp.Client.main(Client.java:6)
		

从上面的错误来看，子类依然试图去引用父类的static成员。获取ClientClass前后两次编译后的的字节码可以看到区别：

父类修改前的编译的子类

	D:\temp>javap -c cn.xwiz.jvm.incomp.ChildClass
	Compiled from "ChildClass.java"
	public class cn.xwiz.jvm.incomp.ChildClass extends cn.xwiz.jvm.incomp.ParentClass {
	  public cn.xwiz.jvm.incomp.ChildClass();
		Code:
		   0: aload_0
		   1: invokespecial #1                  // Method cn/xwiz/jvm/incomp/ParentClass."<init>":()V
		   4: getstatic     #2                  // Field java/lang/System.out:Ljava/io/PrintStream;
		   7: new           #3                  // class java/lang/StringBuilder
		  10: dup
		  11: invokespecial #4                  // Method java/lang/StringBuilder."<init>":()V
		  14: ldc           #5                  // String s=
		  16: invokevirtual #6                  // Method java/lang/StringBuilder.append:(Ljava/lang/String;)Ljava/lang/StringBuilder;
		  19: getstatic     #7                  // Field s:Ljava/lang/String;
		  22: invokevirtual #6                  // Method java/lang/StringBuilder.append:(Ljava/lang/String;)Ljava/lang/StringBuilder;
		  25: invokevirtual #8                  // Method java/lang/StringBuilder.toString:()Ljava/lang/String;
		  28: invokevirtual #9                  // Method java/io/PrintStream.println:(Ljava/lang/String;)V
		  31: return
	}

父类修改后的编译的子类

	D:\project\jvm\xwiz\build\classes>javap -c -private cn.xwiz.jvm.incomp.ChildClass
	Compiled from "ChildClass.java"
	public class cn.xwiz.jvm.incomp.ChildClass extends cn.xwiz.jvm.incomp.ParentClass {
	  public cn.xwiz.jvm.incomp.ChildClass();
		Code:
		   0: aload_0
		   1: invokespecial #1                  // Method cn/xwiz/jvm/incomp/ParentClass."<init>":()V
		   4: getstatic     #2                  // Field java/lang/System.out:Ljava/io/PrintStream;
		   7: new           #3                  // class java/lang/StringBuilder
		  10: dup
		  11: invokespecial #4                  // Method java/lang/StringBuilder."<init>":()V
		  14: ldc           #5                  // String s=
		  16: invokevirtual #6                  // Method java/lang/StringBuilder.append:(Ljava/lang/String;)Ljava/lang/StringBuilder;
		  19: aload_0
		  20: getfield      #7                  // Field s:Ljava/lang/String;
		  23: invokevirtual #6                  // Method java/lang/StringBuilder.append:(Ljava/lang/String;)Ljava/lang/StringBuilder;
		  26: invokevirtual #8                  // Method java/lang/StringBuilder.toString:()Ljava/lang/String;
		  29: invokevirtual #9                  // Method java/io/PrintStream.println:(Ljava/lang/String;)V
		  32: return
	}


重编译前的ChildClass字节码中，记录了对父类静态成员s的调用

	19: getstatic     #7                  // Field s:Ljava/lang/String;


JVM规范中对这个现象做了描述。

http://docs.oracle.com/javase/specs/jls/se7/html/jls-13.html#jls-13.4.10


	13.4.10. static Fields
	If a field that is not declared private was not declared static and is changed to be declared static, or vice versa, then a linkage error, specifically an IncompatibleClassChangeError, will result if the field is used by a pre-existing binary which expected a field of the other kind. Such changes are not recommended in code that has been widely distributed.


		
		



