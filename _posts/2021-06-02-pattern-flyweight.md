---
title: Flyweight模式-共享笨重实例
author: Jie Chen
date: 2021-06-02
categories: [Design,Pattern]
tags: [csharp]
---

Flyweight英文是轻量级的意思，表示把笨重的对象轻量化。这个怎么做到呢，其实做不到的。这里的设计的意图是：假设有大量的比较笨重的对象（创建时间长或者消耗内存大），每次实例化都需要消耗大量的资源，而这些对象如果恰恰是一样的，或者可以抽取出部分可以重用的那部分，如果将他们事先保存为一份，等到下次需要实例化的时候直接从内存里拿出来用，而不再重新创建。说最直接一点，就是最大化地共享可以重复使用的那部分对象，避免新建。如果内存里不存在，那么这个时候再创建一个，创建完后同样驻留在内存里供下次共享。

举一个例子，假设一个部门里需要多个不同开发技能的员工，有的会前端开发，有的会后端。每次有新项目来的时候，需要从队伍里拉人。如果已经存在前端的人，直接用他就可以了。如果有一种技能大家都不会，再去市场上招聘一个新人。

这个模式，可以通过简单工厂模式和单实例模式组合实现。

* 简单工厂负责实例的生成，可以是从内存中拿出来一个可以共享的，也可以新建一个内存中没有的。
* 单实例模式是保证这个工厂只有一个实例，避免同时存在两个工厂。


## 可以作为Flyweight的类

~~~
public class Employee
{
	public string Type { get; set; }

	public int Id { get; set; }

	public void Work()
	{
		Console.WriteLine($"Employee ID {Id} is working on {Type} project");
	}
}
~~~

## 单实例模式，负责Flyweight工厂的唯一性
public class EmployeeFactory
{
	private static ConcurrentDictionary<string, Employee> employees = new ConcurrentDictionary<string, Employee>();
	private static EmployeeFactory instance = null;
	private static readonly object objectLock = new object();

	public EmployeeFactory(Employee[] emps)
	{
		emps.AsEnumerable().ToList().ForEach(e => employees.TryAdd(e.Type, e));
	}
	public static EmployeeFactory GetInstance(Employee[] emps)
	{
		// double checked locking
		if (instance == null)
		{
			lock (objectLock)
			{
				if (instance == null)
				{
					instance = new EmployeeFactory(emps);
				}
			}
		}
		return instance;
	}

	public Employee GetEmployee(string type)
	{
		// 见下面;
	}
}

## 简单工厂，负责Flyweight对象的生成

这里的生成，就是上面提到的：要么从共享库里直接拿出来，要么新建一个放到共享库里并返回

public Employee GetEmployee(string type)
{
	if (employees.TryGetValue(type, out var emp))
	{
		return emp;
	}

	Employee e = new Employee { Type = type, Id = employees.Count + 1 };
	_ = employees.TryAdd(type, e);

	return e;
}

	
## 使用

~~~
Employee[] employees =
{
	new Employee() { Type = "Frontend", Id = 1 },
	new Employee() { Type = "Backend", Id = 2 },
};
EmployeeFactory flyweightFactory = EmployeeFactory.GetInstance(employees);

Employee e1 = flyweightFactory.GetEmployee("Frontend");
Employee e2 = flyweightFactory.GetEmployee("Mobile");
Employee e3 = flyweightFactory.GetEmployee("Mobile");


e1.Work();
e2.Work();
e3.Work();
~~~

结果

~~~
Employee ID 1 is working on Frontend project
Employee ID 3 is working on Mobile project
Employee ID 3 is working on Mobile project
~~~


## 注意点

* 这里有个比较难的一点是，怎么精确定义什么可以作为Flyweight的对象，也就是这个可以共享的对象，颗粒度一定要大，尽可能地把能重用的范围扩大。
* 另外注意一个，这个对象一旦内部做了修改，会影响所有的实例引用，因为，其实，他们都是同一个实例。
