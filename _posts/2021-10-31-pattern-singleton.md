---
title: Singleton 单例模式 - 单例和线程安全
author: Jie Chen
date: 2021-10-31
categories: [Design,Pattern]
tags: [csharp]
---

Singleton单例模式，应该是最简单的创建型的模式。它要求在运行时期间，只存在唯一的一个实例，不允许有多个实例的可能。

需要注意不好的单例模式可能会存在线程不安全的问题：当构造函数需要比较长的时间时，多个调用会同时调用到 new Singleton()，因为此时的_instance仍然为null

~~~
private static Singleton _instance;

public static Singleton GetInstance()
{
	if (_instance == null)
	{
		_instance = new Singleton(); // 此处线程不安全
	}
	return _instance;
}
~~~

解决线程不安全的问题，可以加锁，也可以通过static成员变量来初始化私有构造函（因为当类被首次加载时，会立即执行所有的static成员变量。类第二次被加载或者调用时，不会再执行static成员变量。）

# 加锁例子

~~~
public sealed class USA
{
	private static USA instance = null;
	private static readonly object objectLock = new object();
	private USA()
	{
		System.Console.WriteLine("I am the only one USA in the world");
	}
	public static USA GetInstance()
	{
		// double checked locking
		if (instance == null) // 第一次检查
		{
			lock (objectLock)  // 加锁
			{
				if (instance == null) // 第二次检查
				{
					instance = new USA();
				}
			}
		}
		return instance;
	}
}
~~~


# 通过static成员变量来初始化私有构造函数

可以通过 static成员变量，就能初始化一个线程安全的实例。

~~~
public sealed class China
{
	private static readonly china = new China(); // 编程语言层面保证了这里永远只执行一次（比如java和c#）
	private China()
	{
		System.Console.WriteLine("I am the only one China in the world");
	}

	public static China GetInstance()
	{
		return chinaLock.Value;
	}  
}
~~~

对于.Net而言，我们也可以通过Lazy延时加载的方式，结合static，来实现线程安全。
[Lazy Initialization](https://docs.microsoft.com/en-us/dotnet/framework/performance/lazy-initialization){:target="_blank"}


~~~
public sealed class China
{
	// lazy initialization (it is thread safe)
	private static readonly Lazy<China> chinaLock = new Lazy<China>(new China());
	private China()
	{
		System.Console.WriteLine("I am the only one China in the world");
	}

	public static China GetInstance()
	{
		return chinaLock.Value;
	}
}
~~~

# 使用

~~~
China china1 = China.GetInstance();
China china2 = China.GetInstance();

Console.WriteLine(china1 == china2);

USA usa1 = USA.GetInstance();
USA usa2 = USA.GetInstance();

Console.WriteLine(usa1 == usa2);
~~~

结果：

~~~
I am the only one China in the world
True
I am the only one USA in the world
True
~~~


