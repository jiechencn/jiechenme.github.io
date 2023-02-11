---
title: 责任链模式-首问责任制或甩锅行为
author: Jie Chen
date: 2021-12-04
categories: [Design,Pattern]
tags: [csharp]
---

Chain of Responsibility，从名字看，就是在一个链条上部署了多个责任部门，每个部门只负责自己的那块，各司其职，绝不越权，处理完后，丢给下一个部门处理。有时候也可以修改这个责任链的逻辑，就是在责任链上找到正确的部门处理完后，马上退出其他部门的处理。

这两种情况，非常类似于我们日常生活中的现象：去政府部门一站式窗口办事，第一个窗口是首要窗口，处理完后，到第二个窗口，再到第三个窗口。有时在责任链上的一些行为，也可以看成是甩锅，就是不属于自己的责任，就不会处理，顺延到下个部门身上。

这种处理方式，和 .NetCore中Http管道处理一模一样。在Http管道中，NetCore通过Middleware在定一个一个责任链，多个责任链组成一个管道，依次处理http。处理完后的response再沿着管道回来，在回来的过程中可能又会被当前部门处理一次。

下面的代码是一个比较常见的情形。比如处理用户注册的时候，我们需要验证用户的年龄，国别，性别，拒绝不符合条件的用户，最后获得这个用户的所有错误信息。

首先定义个简单的用户类

~~~
public class User
{
    public string Name { get; set; }
    public int Age { get; set; }
    public string Country { get; set; }
    public char Gendar { get; set; } = 'M';
}
~~~

我们设想，现在有多个部门，每个部门的职责分得很细，而且不允许越权。检查年龄的部门，只负责检查年龄，不检查其他任何东西。检查国别的部门，也只负责检查国别。

为了定义每一个责任人的行为，我们先为他们定义好接口类规范他们的行为，并通过抽象类作为父类定义好统一的责任链的连接方法。

~~~
public interface IValidator<T> where T : class
{
    IValidator<T> SetNext(IValidator<T> next);
    void Handle(T request);
}
~~~
.
~~~
public abstract class AbstractValidator<T> : IValidator<T> where T : class
{
    public AbstractValidator(IList<Exception> exceptions)
    {
        this.exceptions = exceptions;
    }
    protected IList<Exception> exceptions; // 用来保存最终归总之后的错误信息
    private IValidator<T>? Next { get; set; }
    public virtual void Handle(T request)
    {
        Next?.Handle(request);  // 如果 Next == null，就停止执行
    }

    public IValidator<T> SetNext(IValidator<T> next)
    {
        Next = next;
        return Next;
    }
}
~~~

责任链的连接通过这个方法来实现。

~~~
public IValidator<T> SetNext(IValidator<T> next)
{
	Next = next;
	return Next;
}
~~~

具体到每个责任部门，比如检查年龄的，在构造器中，传入一个IList<Exception>，将它传给父类的exceptions，用来统一归总所有收集到的错误信息。在Handle方法中，负责检查自己的那一块，就是年龄。检查完后，任务回到责任链的链条上去，让父类负责责任的后延工作，就是父类里的虚拟方法所执行的下一个部门的执行： Next?.Handle(request);

~~~
public class AgeValidator : AbstractValidator<User>
{
    public AgeValidator(IList<Exception> exceptions) : base(exceptions)
    {
    }

    public override void Handle(User request)
    {
        if (request.Age<18)
        {
            exceptions.Add(new Exception("illegal age"));
        }
        base.Handle(request);
    }
}
~~~

对应的，检查国别和性别，也可以做类似的检查。

~~~
public class CountryValidator : AbstractValidator<User>
{
    public CountryValidator(IList<Exception> exceptions) : base(exceptions)
    {
    }

    public override void Handle(User request)
    {
        if (request.Country == "USA")
        {
            exceptions.Add(new Exception("illegal country"));
        }
        base.Handle(request);
    }
}
~~~
.
~~~
public class GendarValidator : AbstractValidator<User>
{
    public GendarValidator(IList<Exception> exceptions) : base(exceptions)
    {
    }

    public override void Handle(User request)
    {
        if (request.Gendar != 'M' && request.Gendar != 'F')
        {
            exceptions.Add(new Exception("illegal gendar"));
        }
        base.Handle(request);
    }
}
~~~

上面定义好了三种责任部门，分别检查年龄的，检查国别的，检查性别的，各有各的分工。最后，通过调用他们来验证责任链的执行情况。

~~~
User user = new User()
{
	Name = "Tom",
	Age = 10,
	Country = "USA",
	Gendar = 'X'
};
IList<Exception> exceptions = new List<Exception>{ };

AbstractValidator<User> ageValidator = new AgeValidator(exceptions);
AbstractValidator<User> countryValidator = new CountryValidator(exceptions);
AbstractValidator<User> gendarValidator = new GendarValidator(exceptions);

ageValidator.SetNext(countryValidator).SetNext(gendarValidator);

ageValidator.Handle(user);

foreach(Exception ex in exceptions)
{
	Console.WriteLine(ex.ToString());
}
~~~

上面的代码中，ageValidator.SetNext(countryValidator).SetNext(gendarValidator) 负责创建一条连续的责任链，首问责任人是ageValidator。我们的程序会按照这个链条的顺序依次做检查，最终的检查结果归总到IList<Exception>中。

最后的执行结果：

~~~
System.Exception: illegal age
System.Exception: illegal country
System.Exception: illegal gendar
~~~

## 责任链的优缺点

* 优点：处理单元很灵活，很容易配备一个新的责任部门到链条上去，而且处理顺序可以根据业务需求，自用挪动。
* 缺点：责任甩锅就像生活中的甩锅一样，容易引起已定义的处理的延迟。这个需要业务上的权衡评估，不一定是坏事。

