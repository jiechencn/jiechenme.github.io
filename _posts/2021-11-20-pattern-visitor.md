---
title: Visitor模式-分离数据结构和数据处理
author: Jie Chen
date: 2021-11-20
categories: [Design,Pattern]
tags: [csharp]
---

首先了解一个开闭原则，对扩展开放，对修改关闭。也就是永远考虑扩展的可能，尽量不要修改类的原始定义。

由此想到一种常见的情形。 DTO （Data Transfer Object）是用来在不同的处理层交换数据的最简单的类对象，它可以被序列化。在某些情况下，我们可能在这个类的基础上，想要进行额外的复杂的处理，由于DTO的使用遵循最简单的原理，不推荐在DTO中直接添加额外方法，所以复杂操作可以这么处理：创建一个新的类，接收DTO作为参数，在新的类里处理。有一个不好的地方是，如果有多种不同种类的复杂操作，需要添加不同的方法。

如果采用Visitor模式，就能比较好地处理这种情况，虽然Visitor模式使用起来需要一点挑战，就是要对DTO未来的数据处理有前瞻性的预判。

Visitor模式的遵循的原理是：把数据结构的定义和数据的处理分离开来。数据结构预先添加一个 Accept(IVisitor)的方法，方法体内调用 IVisitor.Visit(this)，让Visitor直接访问自己的方法。

听起来有点模糊。举个简单的例子。一个学校的学生，是一个简单的DTO数据结构。他们可能会接受外部的一些访问：体检、调研考试等。我们可以这么定义：将学生定义为DTO，体检和考试定义为Visitor，因为这两个Visitor会对DTO做不同的操作，所以对Element的数据访问操作，应该放在Visitor内。

定义一个数据结构 AbstractPeople，简单演示，所以只包含一个Name属性。在这个DTO中，添加一个Accept方法，接受Visitor。方法体内，调用Visistor的visit方法，传入this，访问DTO自己。

~~~
public abstract class AbstractPeople
{
	public string Name { get; set; }
	public void Accept(IVisitor vistor)
	{
		vistor.Visit(this);
	}
}

public class Student : AbstractPeople
{
	public int Score { get; set; }
}

public class Teacher : AbstractPeople
{
}
~~~

然后定一个Visitor，因为它只有方法，所以定义成接口。不同的Visitor，针对DTO的处理方式是不一样的。比如，Examiner就不需要对老师进行考试，这里对学生的分数随机打个分数。

~~~
public interface IVisitor
{
	public void Visit(AbstractPeople people);
}
public class Doctor : IVisitor
{
	public void Visit(AbstractPeople people)
	{
		Console.WriteLine($"{people.Name} is in healthy check");
	}
}

public class Examiner : IVisitor
{
	public void Visit(AbstractPeople people)
	{
		if (people is Student)
		{
			Console.WriteLine($"{people.Name} is in testing");
			((Student)people).Score = new Random().Next(80, 100);
		}
	}
}
~~~

为了演示方便，定义一个School的辅助类。用来表示整个学校的教职工，放在一个列表内。InviteVisitor方法用来传入一个Visitor，对列表内的教职工进行访问（要么体检，要么考试）。

~~~
public class School
{
	private List<AbstractPeople> peoples = new List<AbstractPeople>();

	public void Add(AbstractPeople people)
	{
		peoples.Add(people);
	}

	public void InviteVisitor(IVisitor visitor)
	{
		foreach(AbstractPeople people in peoples)
		{
			people.Accept(visitor);
		}
	}
}
~~~

客户端通过School辅助类的管理，来调用Visitor

~~~
Student stu1 = new Student() { Name = "Tom" };
Student stu2 = new Student() { Name = "Jerry" };
AbstractPeople teacher1 = new Teacher() { Name = "Mr.Chen" };
AbstractPeople teacher2 = new Teacher() { Name = "Mr.Wang" };

School school = new School();
school.Add(stu1);
school.Add(stu2);
school.Add(teacher1);
school.Add(teacher2);

IVisitor doctor = new Doctor();
IVisitor examiner = new Examiner();

school.InviteVisitor(doctor);
school.InviteVisitor(examiner);

Console.WriteLine($"{stu1.Name} score is {stu1.Score}");
Console.WriteLine($"{stu2.Name} score is {stu2.Score}");
~~~

输出结果表明不同的Visitor对DTO数据结构的处理，按照预期的设想作用。

~~~
Tom is in healthy check
Jerry is in healthy check
Mr.Chen is in healthy check
Mr.Wang is in healthy check
Tom is in testing
Jerry is in testing
Tom score is 98
Jerry score is 85
~~~

# Double Dispatch

在School的辅助类中，我们调用了 people.Accept(visitor);

~~~
public void InviteVisitor(IVisitor visitor)
{
	foreach(AbstractPeople people in peoples)
	{
		people.Accept(visitor); // <----
	}
}
~~~

被调用的 people.Accept(visitor)又调用了 vistor.Visit(this)。这种模式叫做 Double Dispatch。确保了visitor的最终的方法的执行。

~~~
public void Accept(IVisitor vistor)
{
	vistor.Visit(this); // <----
}
~~~

# 优缺点
* 数据结构和数据的处理严格隔离开了。数据处理在单独的Visitor中负责。方便添加新的数据结构的类、或者数据处理的类。
* 较为复杂，需要严谨的设计。