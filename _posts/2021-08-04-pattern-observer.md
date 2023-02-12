---
title: Observer模式-Publish-Subscribe订阅
author: Jie Chen
date: 2021-08-04
categories: [Design,Pattern]
tags: [csharp]
---

Observer模式其实就是Publish-Subscribe模式，用于对于消息的响应。这里Observer的词语不是很恰当，因为Observer是要求订阅者主动去查询，而subscribe模式则是subject主动推送消息过来。两者恰恰是相反的。

了解到它的本质就是订阅模式，接下来就比较好设计了。对于消息的推送，是基于subject的某个状态变化的。这里假设有个手机商店，卖iphone和android手机。顾客来店里登记一下手机号，当他想要的手机到货的时候，让商店主动打电话通知他。

所以这里有几个注意点，就是subscriber可能订阅了多种手机，同一个手机有多个订阅者。

这里的subject就是手机。创建一个Subject类：

~~~
public class Subject
{
    private List<Subscriber> observers = new List<Subscriber>();
    private int state;

    public string Name { get; set; }

    public void Attach(Subscriber observer)
    {
        observers.Add(observer);
    }

    public void Dettach(Subscriber observer)
    {
        observers.Remove(observer);
    }

    public void SetState(int state)
    {
        this.state = state;
        NotifyObservers();
    }

    private void NotifyObservers()
    {
        foreach(var observer in observers)
        {
            if (state == 1)
            {
                observer.Update(this);
            }
        }
    }
}
~~~

Attach方法用来登记顾客。SetState用状态来标识是否手机到货了。一旦有状态更新，就发送通知给全体订阅的顾客。

然后定义订阅者类，他主要可以做的事情是：订阅某种手机的消息 Subscribe，以及得到通知后触发他做什么 Update

~~~
public class Subscriber
{
    public string Name { get; set; }

    public void Subscribe(Subject subject)
    {
        subject.Attach(this);
    }

    public void Unsubscribe(Subject subject)
    {
        subject.Dettach(this);
    }

    public void Update(Subject subject)
    {
        Console.WriteLine($"{Name} is notified that {subject.Name} comes");
    }
}
~~~

这样，发布和订阅的机制都定义完成。通过调用来观察一下：

~~~
Subject iphone = new Subject() { Name = "iphone" };
Subject android = new Subject() { Name = "android" }; ;

Subscriber tom = new Subscriber() { Name = "Tom" };
Subscriber jerry = new Subscriber() { Name = "Jerry" };
tom.Subscribe(iphone);
tom.Subscribe(android);
jerry.Subscribe(iphone);
jerry.Subscribe(android);
Subscriber mary = new Subscriber() { Name = "Mary" };
mary.Subscribe(iphone);

iphone.SetState(1);
android.SetState(1);
~~~

输出：

~~~
Tom is notified that iphone comes
Jerry is notified that iphone comes
Mary is notified that iphone comes
Tom is notified that android comes
Jerry is notified that android comes
~~~
