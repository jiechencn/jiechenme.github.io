---
title: State模式-用类来表示物体的状态
author: Jie Chen
date: 2021-07-20
categories: [Design,Pattern]
tags: [csharp]
---

State模式非常适合那种用优先状态机来表达的工作流应用。比如有个文档的发布系统，创建文档的人要经历编辑、审核、批准到最后发布的几个状态。在某个状态上切换到其他状态，可能需要满足一定的条件。比如在批准阶段，就不允许再编辑了。在发布阶段，就不允许编辑、审核和批准。这样的工作流的状态切换最直观的解决方式是使用 if-else或者swtich-case来判断。但是有一个很大的问题是，一旦需要添加一个新的状态，整个逻辑就要重新修改，越高越复杂。

State模式就是为了专门解决这种状态切换的问题，通过把每一个状态定义成一个单独的state类，然后把这个状态类赋给工作流的对象，工作流只负责状态的下一步切换，里面的逻辑判断完全由各个State类自行判断。下面的例子演示如何实现这种文档发布系统的状态切换。

## 文档对象定义

把文档对象定义为DocumentContext，添加一个State类属性。初始化时状态为编辑状态EditState。

当文档挪到下一个状态的时候，我们只要定义 Next()，然后通过调用State.SetNext(this)，把当前对象交给State，通过State设置下一个阶段。

SetState的目地是为了切换State类本身。因为通过上面的State.SetNext(this)，间接地反向调用this.State = state 将新的状态类替换掉当前的状态类，这样，新的状态类才能知道下一个状态是什么。

后面的Edit，Review等，是文档对象具体的操作，里面调用State的对应方法用来校验在当前状态下的这个动作是否被允许。

~~~
public class DocumentContext
{
	public State State { get; private set; }

	public DocumentContext()
	{
		State = new EditState();
	}

	public void Next()
	{
		State.SetNext(this);
	}

	public void SetState(State state)
	{
		this.State = state;
	}
	
	public void Edit()
	{
		if (State != null )
		{
			State.Edit(this);
		}
	}
	public void Review()
	{
		if (State != null)
		{
			State.Review(this);
		}
	}

	public void Approve()
	{
		if (State != null)
		{
			State.Approve(this);
		}
	}

	public void Publish()
	{
		if (State != null)
		{
			State.Publish(this);
		}
	}
}
~~~

## 状态类

把有限状态机定义成一个State类，SetNext接收文档对象做状态的替换，Edit、Review是用来对文档进行验证操作，判断是否允许。

~~~
public abstract class State
{
	private string Name { get; }
	public State(string name)
	{
		this.Name = name;

		Console.WriteLine(name);
	}

	public abstract void SetNext(DocumentContext context);

	public abstract void Edit(DocumentContext context);

	public abstract void Review(DocumentContext context);

	public abstract void Approve(DocumentContext context);

	public abstract void Publish(DocumentContext context);
}
~~~

假设现在有一个子类EditState 标识文档处于编辑状态，这个状态下，不允许Approve也不允许Publish。

~~~
public class EditState : State
{
	public EditState() : base("Editing")
	{
	}

	public override void SetNext(DocumentContext context)
	{
		context.SetState(new ReviewState());
	}
	public override void Edit(DocumentContext context)
	{
		Console.WriteLine("It is editing");
	}

	public override void Review(DocumentContext context)
	{
		SetNext(context);
	}

	public override void Approve(DocumentContext context)
	{
		Console.WriteLine("Cannot approve because not reviewed");
	}

	public override void Publish(DocumentContext context)
	{
		Console.WriteLine("Cannot publish because not approved");
	}
}
~~~

同样的道理，其他的状态类，在相应的方法里对状态进行验证。

~~~
public class ReviewState : State
{

	public ReviewState() : base("Reviewing")
	{
	}

	public override void SetNext(DocumentContext context)
	{
		context.SetState(new ApproveState());
	}
	public override void Edit(DocumentContext context)
	{
		Console.WriteLine("Cannot edit because reviewing");
	}

	public override void Review(DocumentContext context)
	{
		Console.WriteLine("It is reviewing");
	}

	public override void Approve(DocumentContext context)
	{
		SetNext(context);
	}

	public override void Publish(DocumentContext context)
	{
		Console.WriteLine("Cannot publish because not approved");
	}
}

public class ApproveState : State
{
	public ApproveState() : base("Approved")
	{
	}

	public override void SetNext(DocumentContext context)
	{
		context.SetState(new PublishState());
	}
	public override void Edit(DocumentContext context)
	{
		Console.WriteLine("Cannot edit because approved");
	}

	public override void Review(DocumentContext context)
	{
		Console.WriteLine("Cannot review because approved");
	}

	public override void Approve(DocumentContext context)
	{
		Console.WriteLine("Already approved");
	}

	public override void Publish(DocumentContext context)
	{
		SetNext(context);
	}
}

public class PublishState : State
{
	public PublishState() : base("Published")
	{
	}

	public override void SetNext(DocumentContext context)
	{
		// no next state
	}
	public override void Edit(DocumentContext context)
	{
		Console.WriteLine("Cannot edit because published");
	}

	public override void Review(DocumentContext context)
	{
		Console.WriteLine("Cannot review because published");
	}

	public override void Approve(DocumentContext context)
	{
		Console.WriteLine("Cannot approve because published");
	}

	public override void Publish(DocumentContext context)
	{
		Console.WriteLine("Already published");
	}
}
~~~

从上面的设计里，可以很清晰地看到，状态验证和切换两个动作，很好地挪到了具体的状态类里，而不是集中在if-else或者switch-case语句中。这样的好处是状态的处理实现了分而治之。

这里的一个需要注意的地方时，状态的迁移，明面上是文档对象，但其实还是State的某个子类负责迁移。具体的逻辑是： 文档对象调用State做切换，State再调用文档对象做状态的替换。

## 验证整个工作流的迁移和校验

~~~
DocumentContext doc = new DocumentContext();
doc.Next(); // to reviewing
doc.Next(); // to approved
doc.Next(); // to published
doc.Edit(); // try to edit
doc.Review(); // try to review
doc.Approve(); // try to approve
doc.Publish(); // try to publish
~~~
			
观察结果，很好地看清楚了它的迁移的过程和校验的行为。

~~~		
Editing
Reviewing
Approved
Published
Cannot edit because published
Cannot review because published
Cannot approve because published
Already published
~~~

## 优缺点

优点也就是缺点。因为状态的切换和校验，都需要State类来参与，所以每一个状态必须知道其他状态的存在。当添加或者修改一个状态的时候，其他所有的状态类都需要做调整。但，即便这样，也要好过if-else这样的集中式的复杂处理。
