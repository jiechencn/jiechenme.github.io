---
title: try-with-resources和异常处理
author: Jie Chen
date: 2015-12-01
categories: [Java]
tags: []
---

Java 7开始有一个有意思的功能，就是结合了自动关闭资源和异常处理的try-with-resources语句。按照旧式的处理方式，关闭资源一般放在try-catch的finally块中，需要显式地关闭。比如关闭文件句柄、数据库中的连接、语句和记录集等。而try-with-resources不需要显式声明关闭，它会自动调用close()方法，防止程序员忘记关闭资源而引起内存可能的泄漏。因此这种方式必须要求资源具有统一性，即必须实现了java.lang.AutoCloseable接口和它的唯一方法close()。

## 实现AutoCloseable

假设有两个资源操作类MyIOA和MyIOB，首先需要实现AutoCloseable接口和它的唯一方法close()。他们的构造函数中打开资源，具体操作定义在act()方法中。同时在实现的close()中实现资源的关闭。

	package cn.xwiz.lab.autoclose;

	public class MyIOA implements AutoCloseable {
	  @Override
	  public void close() throws Exception {
		System.out.println("A is closed");
	  }
	  public MyIOA(){
		System.out.println("A is opened");
	  }
	  public void act(){
		System.out.println("A is acting...");

	  }
	}

   
第二个类：

	package cn.xwiz.lab.autoclose;

	public class MyIOB implements AutoCloseable {
	  @Override
	  public void close() throws Exception {
		System.out.println("B is closed");
	  }
	  public MyIOB(){
		System.out.println("B is opened");
	  }
	  public void act(){
		System.out.println("B is acting...");

	  }
	}


调用类使用try-with-resources时，MyIOA和MyIOB的引用赋值给局部变量a和b，因此a和b的生命周期只在try-with-resources块范围内。

	package cn.xwiz.lab.autoclose;

	public class Client {
	  public static void main(String args[]){
		try(MyIOA a = new MyIOA();
			MyIOB b = new MyIOB()){

		  a.act();
		  b.act();

		}catch(Exception e){

		}
	  }
	}


执行结果可以看到两个引用变量都自动执行了close()方法。

	A is opened
	B is opened
	A is acting...
	B is acting...
	B is closed
	A is closed


## 多资源的倒序关闭

从上面的执行结果发现，catch块内自动调用资源的关闭方法。如果有多个资源同时需要关闭，最后声明的资源引用被首先关闭，最先声明的资源反而是最后关闭。


###继发异常的获取###
在老式的try-catch-finally中，如果finally中的关闭方法需要捕获异常时，则需要额外的try-catch来处理。比如下面的就是传统的处理方式。特别是当包含这块try-catch-finally的方法需要往调用者throws异常的时候，更是需要额外处理标识到底是来自第一个catch还是finally中的catch。

	try{
		//...
	}catch(Exception e){
		// ..
	}finally{
		try{
			if (conn != null)
				conn.close();
		}catch(Exception e){
			e.printStackTrace();
		}
	}

使用try-with-resources就不用担心这些细枝末节的东西，它的catch是永远只抓住首要的异常，即来自try块中的异常。如果在资源关闭close()中出现多个异常，则被以数组的方式内置在首要异常内部，使用Exception.getSuppressed()就可以获取那些在老式方法中抛出的继发的异常数组。

比如在调用MyIOB.act()时抛出异常被捕获，程序停止try块中后续的代码，转而进入catch块内处理资源关闭。假设MyIOB.close()关闭时又抛出异常，则会被suppress，使用Exception.getSuppressed()就能获取到。

	package cn.xwiz.lab.autoclose;

	public class MyIOB implements AutoCloseable {
	  @Override
	  public void close() throws Exception {
		System.out.println("B is closed");
		throw new Exception("I am B's close exception");
	  }
	  public MyIOB(){
		System.out.println("B is opened");
	  }
	  public void act() throws Exception{
		System.out.println("B is acting...");
		throw new Exception("I am B's act exception");
	  }
	}


调用方法中使用Throwable ts[] = e.getSuppressed()就能获取所有继发异常。

	package cn.xwiz.lab.autoclose;

	public class Client {
	  public static void main(String args[]){
		try(MyIOA a = new MyIOA();
			MyIOB b = new MyIOB()){

		  a.act();
		  b.act();

		}catch(Exception e){
		  System.out.println(e.getMessage());
		  Throwable ts[] = e.getSuppressed();
		  for (Throwable t:ts)
			System.out.printf(t.getMessage());
		}
	  }
	}

执行结果中，第一个是catch的首要异常。第二个是从e.getSuppressed()获取的异常数组中取得，至于具体怎么处理这些多个异常，完全看应用的逻辑而确定。

	A is opened
	B is opened
	A is acting...
	B is acting
	B is closed
	A is closed
	I am B's act exception
	I am B's close exception

个人觉得try-with-resources没有太多技巧性的东西或者说多少实质性的变革。它唯一的好处就是把代码变得相对简洁一点。至于说能防止程序员忘记关闭资源，可能也只是一个小小的优点。