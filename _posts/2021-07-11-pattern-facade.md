---
title: Facade模式-隐藏细节
author: Jie Chen
date: 2021-07-11
categories: [Design,Pattern]
tags: [csharp]
---

Facade模式，每个人都在不知不觉地使用这个模式，它没有什么复杂的概念和技巧，其实类似一站式服务中心，或者像房产中介，它把后面的所有流程细节对调用者全部隐藏了。调用者无须知道复杂的细节，所有的事情交给一站式服务中心处理即可。

调用者有两种方式和一站式服务中心打交道：

* 知道具体功能的对象，把对象告诉服务中心

* 不知道有哪些对象，全权让服务中心处理

## 复杂系统的多个对象

比如有两个功能对象：

~~~
public class SubsystemA
{
  public void DoA()
  {
    Console.WriteLine("do A");
  }
}
~~~
.
~~~
public class SubsystemB
{
  public void DoB()
  {
    Console.WriteLine("do B");
  }
}
~~~

调用者使用他们时，必须亲自调用他们的方法。

~~~
SubsystemA a = new SubsystemA();
a.DoA();
SubsystemB b = new SubsystemB();
b.DoB();
~~~

## 一站式服务中心

~~~
public class Facade
{
  SubsystemA a;
  SubsystemB b;
  public Facade(SubsystemA a, SubsystemB b)
  {
    this.a = a;
    this.b = b;
  }

  public Facade()
  {
    a = new SubsystemA();
    b = new SubsystemB();
  }

  public void Do()
  {
    a.DoA();
    b.DoB();
  }
}
~~~

调用者只需要和这个服务中心打交道就可以了。

要么：
~~~
// new way 1
SubsystemA a1 = new SubsystemA();
SubsystemB b1 = new SubsystemB();
Facade f1 = new Facade(a1, b1);
f1.Do();
~~~


要么：
~~~
// new way 2
Facade f2 = new Facade();
f2.Do();
~~~

## 优缺点

优点也就是缺点。正因为调用者完全对后面的子功能的细节完全不知情，所以当在第一种调用情况下，一旦后端子功能变化（增加或者减少），调用者也不得不被动地做修改。
