---
title: Java多线程 - 对象不可变模式
author: Jie Chen
date: 2009-09-02
categories: [Java]
tags: [multithread]
---


对象不可变模式，关注的是多线程的数据安全，是说参与多线程的对象一旦在第一个线程内建立，便不会再被第二个线程的外部方法所改变。假设通过Point和Line类做图形的画线程序。定义起始点p1和终点p2，通过Line构造函数来定义一个线段，并简单通过System.out.println(line)来表示画一条坐标为(1,100)-(2,200)的线段。

	public class Main {
	  public static void main(String args[]){
		Point p1 = new Point(1, 100);
		Point p2 = new Point(2, 200);
		Line line1 = new Line(p1, p2);

		System.out.println(line1);

		p1.x = 3;
		p1.y = 300;
		p2.x = 4;
		p2.y = 400;

		System.out.println(line1);

	  }
	}

相应的Line类

	public class Line {
	  private final Point startPoint;
	  private final Point endPoint;

	  public Line(Point start, Point end) {
		this.startPoint = start;
		this.endPoint = end;
	  }

	  public double getStartX() { return startPoint.x; }

	  public double getStartY() {
		return startPoint.y;
	  }

	  public double getEndX() {
		return endPoint.x;
	  }

	  public double getEndY() {
		return endPoint.y;
	  }

	  public String toString() {
		return "Line: " + startPoint + "-" + endPoint;
	  }
	}

以及Point类

	public class Point {
	  public int x;
	  public int y;

	  public Point(int x, int y){
		this.x = x;
		this.y = y;
	  }
	  public String toString(){
		return "(" + x + "," + y + ")";
	  }
	}


此时，假设有第二个线程对两个坐标点进行修改，比如下列，这时再重新画线时，会发现坐标点完全被篡改了。

    p1.x = 3;
    p1.y = 300;
    p2.x = 4;
    p2.y = 400;
	System.out.println(line1);

两次画线的结果为：

	Line: (1,100)-(2,200)
	Line: (3,300)-(4,400)

分析其原因，在于Point类的x和y定义为public，这是不安全的。而Line构造函数又是直接引用了Point实例p1和p2，因为是引用关系，所以p1.x被第二个线程赋值后，Line实例内部的startPoint的x也会被修改。

## 解决方法一

因为Point内的x和y为public很容易被修改，如果加上修饰符final就限定他们只能在Point构造函数内一次性赋值，避免在构造函数外赋值。另外给Point类也加上final属性，防止派生子类修改父类Point的x和y。修改后Point类为：

	public final class Point {
	  public final int x;
	  public final int y;

	  public Point(int x, int y){
		this.x = x;
		this.y = y;
	  }
	  public String toString(){
		return "(" + x + "," + y + ")";
	  }
	}

此时，第二个线程将无法给p1.x和p1.y赋值，因为受final保护作用，发生运行时错误。

## 解决方法二

因为Line构造函数直接引用了Point实例，而Point又是不安全的，突破不安全的方法就是让Line构造函数产生的坐标startPoint不直接引用Point，而是根据Point的x和y重新new一个新的Point实例。这样的作用显而易见，即使构造函数传递进来的参数p1如何修改，都不会影响到函数体内重新new出来的新Point对象。因为如下图所示，他们的地址是完全不一样的。

![](/assets/res/java-threads-immutablepattern-1.png)

因此，保持Point类不做修改，只是修改Line的构造函数，就可以保证实例过程之后不会被修改。

	public Line(Point start, Point end) {
		this.startPoint = new Point(start.x, start.y);
		this.endPoint = new Point(end.x, end.y);
	}


执行一开始的Main客户类调用，两次画坐标都能保证他们是完全相同的同一个对象并且对象值完全相等。

	Line: (1,100)-(2,200)
	Line: (1,100)-(2,200)