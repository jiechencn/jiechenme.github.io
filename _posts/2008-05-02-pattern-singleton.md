---
title: 实例生成-singleton-每时每刻只产生一个单一的实例
author: Jie Chen
date: 2008-05-02
categories: [Design]
tags: [Java,Pattern]
---


singleton模式可能是最简单最容易理解的一种设计了。就是在JVM运行时内，某个类永远只有一个实例存在。比如一个用来读写property属性文件的类，在整个应用中只需要一个单实例。

~~~
public class MySingleton {
    private static MySingleton instance = null;
    private MySingleton(){
        //do initialization job
    };
    public static MySingleton getInstance(){
        if (instance == null)
            instance = new MySingleton();
        return instance;
    }
}
~~~

有几个注意点：

## 构造函数的访问权限

构造函数的访问权限必须是private，否则会被调用构造函数产生多个类实例。

## 同步访问

上面的getInsance如果在密集多线程情况下，多个线程会同时访问 if (instance == null) 分支判断，从而多个线程同时进入构造函数，从而产生多个类实例。模拟这样的情形很简单，只要在构造函数中加个睡眠让它运行一段时间，会有多个实例被产生。

解决方法就是方法的同步
~~~
public static synchronized MySingleton getInstance(){
~~~

