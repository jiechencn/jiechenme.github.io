---
title: Proxy模式-形形色色的代理角色
author: Jie Chen
date: 2021-08-12
categories: [Design,Pattern]
tags: [csharp]
---

Proxy有很多应用场景，比如：

* 虚拟代理（如果实例初始化很复杂很耗时，通过代理延迟到真正使用的时候再创建）
* 访问代理（在处理真正的请求时进行一定的验证）
* 远程代理（客户端像访问本地的资源一样，透明地访问远程资源，而无需关心到底资源在哪里）
 
所有这些，相同点都是：当实例不方便直接处理时，通过proxy包裹真正的对象，做适当的处理，再调用真正的对象。

通过一个Http代理的例子来演示，非常容易理解。假设我要访问某网站，只需要类似 调用第三方的方法，比如internet.Connect(url)这样就可以了。但是如果我们需要对访问进行一定的控制，比如禁止访问有害网站，并对所有访问进行日志记录，因为我们无法修改第三方类，所以就可以采用proxy模式。

## Proxy和真正对象的通用接口

定义一个IInternet接口，暴露 Connect方法。

~~~
public interface IInternet
{
	public void Connect(string url);
}
~~~

## 真正对象

真正的对象只负责原始的访问操作。

~~~
public class RealInternet : IInternet
{
	public void Connect(string url)
	{
		Console.WriteLine("connected to " + url);
	}
}
~~~

## Proxy对象

Proxy对象拥有RealInternet的一个实例引用。在访问前会过滤有害网址，访问后，做日志记录。中间就是调用 realInternet.Connect(url)

~~~
public class ProxyInternet: IInternet
{
	private IInternet realInternet = new RealInternet();
	public void Connect(string url)
	{
		RejectBadUrl(url);
		realInternet.Connect(url);
		LogAccess(url);
	}

	private void LogAccess(string url)
	{
		Console.WriteLine("someone is accessing " + url);
	}

	private void RejectBadUrl(string url)
	{
		// 检查有害网址，抛异常;
		if (url.Contains("bad"))
		{
			throw new Exception("bad url");
		}
	}
}
~~~
	
## 使用

~~~
IInternet internet = new ProxyInternet();
internet.Connect("https://google.com");
internet.Connect("https://bad.com");
~~~

## 和其他模式的区别

* Adapter：适配器是针对两个不同的接口的对象，捷星适配，使得他们可以一同工作。而Proxy里的Proxy和Real对象是同一个接口。
* Decorator：装饰器的目地是为了增加新的功能。而Proxy是针对real对象进行其他辅助增强。
