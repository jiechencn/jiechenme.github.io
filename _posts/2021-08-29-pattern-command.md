---
title: Command模式-用类来表示对象的方法
author: Jie Chen
date: 2021-08-29
categories: [Design,Pattern]
tags: [csharp]
---

一般地，我们通过调用类的方法来实现某个功能。假设现在要执行一系列的方法实现复杂的完整功能，当需要取消这个完整功能时，Command模式就派上了用场。

Command模式要求每一个方法都定义成一个类，他们实现一个统一的接口。接口提供Execute和Unexecute方法来表示执行或者取消执行。然后会有一个Invoker通过Add方法将这些Command添加到一个列表（或者栈，或者队列都可以）里，然后调用Invoker自己的的Execute方法，该方法会循环调用这个队列的Command.Execute，完成整个功能的实现。如果要取消这个功能，调用Invokder.Cancel或者Rollback的方法，依次或者倒序循环调用 Command.Unexecute。

假设去网站上购物，有几个动作：客户添加购物车，客户付款，网站发货。这些动作可以定义成不同的类。这里演示的类的构造函数中有个DataReceiver，这里表示数据库的处理类，用来把订单信息更新到数据库。Command模式不要求一定有个Receiver，这里只是演示一个数据写到后台的操作。

## Command接口

提供Execute和Unexecute的回滚操作。

~~~
public interface ICommand
{
	public void Execute();

	public void Unexecute();
}

public class AddToCartCommand : ICommand
{
	private DataReceiver receiver;
	public AddToCartCommand(DataReceiver receiver)
	{
		this.receiver = receiver;
	}

	public void Execute()
	{
		receiver.Action(this);
	}

	public void Unexecute()
	{
		receiver.CancelAction(this);
	}
}
public class PayCommand : ICommand
{
	private DataReceiver receiver;
	public PayCommand(DataReceiver receiver)
	{
		this.receiver = receiver;
	}

	public void Execute()
	{
		receiver.Action(this);
	}

	public void Unexecute()
	{
		receiver.CancelAction(this);
	}
}

public class ShipCommand : ICommand
{
	private DataReceiver receiver;
	public ShipCommand(DataReceiver receiver)
	{
		this.receiver = receiver;
	}

	public void Execute()
	{
		receiver.Action(this);
	}

	public void Unexecute()
	{
		receiver.CancelAction(this);
	}
}
~~~

DataReceiver可有可无，这里演示写数据库。

~~~
public class DataReceiver
{
	public void Action(ICommand command)
	{
		Console.WriteLine($"execute {command.GetType()} and save to database");
	}

	public void CancelAction(ICommand command)
	{
		Console.WriteLine($"cancel {command.GetType()} and update to database");
	}
}
~~~
	
## Invoker

Invoker是一个命令集的调用者，负责调用所有的命令。这里它提供了一个队列来收集所有的command，供依次执行。

~~~
public class OrderInvoker
{
	private Queue<ICommand> commands = new Queue<ICommand>();
	public void AddCommand(ICommand command)
	{
		commands.Enqueue(command);
	}

	public void ProcessOrder()
	{
		commands.ToList().ForEach(o => o.Execute());
	}

	internal void Rollback()
	{
		commands.ToList().ForEach(o => o.Unexecute());
	}
}
~~~

客户端的调用非常直观，构造一系列命令，将他们添加到Invoker的一个队列里。然后让Invoker.ProcessOrder依次执行他们。

回滚订单也很简单，这里只是简单地演示依次调用command.Unexecute。

~~~
DataReceiver receiver = new DataReceiver();
OrderInvoker invoker = new OrderInvoker();
invoker.AddCommand(new AddToCartCommand(receiver));
invoker.AddCommand(new PayCommand(receiver));
invoker.AddCommand(new ShipCommand(receiver));

invoker.ProcessOrder();
invoker.Rollback();
~~~

输出

~~~
execute Command.AddToCartCommand and save to database
execute Command.PayCommand and save to database
execute Command.ShipCommand and save to database
cancel Command.AddToCartCommand and update to database
cancel Command.PayCommand and update to database
cancel Command.ShipCommand and update to database
~~~

## 扩展

只需要按照接口的定义，添加一个新的Command类，就可以，然后在client处的某个地方通过AddCommand插入这个新command，就可以添加到invoke的队列里去了。

另外，在调用 Invoker.AddCommand的时候，可以给Command的实例提供更加丰富的参数来表示一些重要的数据，比如用户id，下单时间等，放在Command的构造函数里。
