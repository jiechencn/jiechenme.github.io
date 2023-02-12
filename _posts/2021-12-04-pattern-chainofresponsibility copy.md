---
title: Mediator模式-中央集权统一管理
author: Jie Chen
date: 2021-12-12
categories: [Design,Pattern]
tags: [csharp]
---

Mediator有调停和斡旋的意思，在设计模式中可以用来做统一管理。设想一个系统中有多个组件，每个组件之间可能相互耦合、交互。如果添加一个新的组件进来，有可能要对所有已经存在的每一个组件都要修改，以使得老的组件能够和新的组件做交互。

这种强耦合造成的问题是修改范围太广，牵扯太大，很难扩展或缩编。如果有一个统一的中间部门做调和，所有的事情交给这个部门做，就能解决强耦合的问题，杜绝组件之间的直接交互。这个中间部门就是 Mediator。

在真实社会中，也存在大量的这种Mediator。比如二战时期，美国对日本宣战后，日本在太平洋战场不停地打败仗。前线把失败的消息发回日本国内后，天皇对失败的消息进行统一造假，篡改成胜利消息对国内发布。这个天皇，就是Mediator，他的职责就是杜绝不同战场互相透漏消息，统一发布和谐新闻。

假设现在有这样的中央集权的新闻社，负责对各类媒体机构进行注册登记，统一发布和谐新闻。先有一个接口，规范接口的行为。

public interface IPress
{
    void ReleaseNews(string news, AbstractMedia media);
    void Register(AbstractMedia media);
}

然后创建一个中央统一的新闻社，实现这两个接口方法。其中ReleaseNews的任务是和谐关键字，然后调度所有的媒体来接收和谐之后的新闻。

public class CentralPress : IPress
{

    private List<AbstractMedia> medias = new List<AbstractMedia>();

    public void Register(AbstractMedia media)
    {
        medias.Add(media);
    }

    public void ReleaseNews(string news, AbstractMedia media)
    {
        news = news.Replace("bad", "good");

        foreach (var m in medias)
        {
            m.Receive(news);
        }
    }
}

接着创建不同的媒体机构。首先通过抽象类，供上面的接口来引用抽象媒体。在构造器中，调用 press.Register(this)来向中央登记。

public abstract class AbstractMedia
{
    protected IPress press;
    protected string name;
    public AbstractMedia(IPress press, string name)
    {
        this.press = press;
        this.name = name;
        this.press.Register(this);
    }

    public abstract void Send(string news);
    public abstract void Receive(string news);
}

具体到Media类，可以简单地集成抽象类，在Send方法中，把消息传给press，调用press.ReleaseNews，让中央统一修改发布。

public class Media : AbstractMedia
{
    public Media(IPress press, string name) : base(press, name)
    {
    }

    public override void Receive(string news)
    {
        Console.WriteLine(this.name + ": received news:" + news);
    }

    public override void Send(string news)
    {
        Console.WriteLine(this.name + ": Sending news=" + news + "\n");
        press.ReleaseNews(news, this);
    }
}

一条和谐的通道就建成了。看看各个媒体是如何发布真实消息、而中央又是如何修改统一口径的。

IPress press = new CentralPress();
AbstractMedia m1 = new Media(press, "m1");
AbstractMedia m2 = new Media(press, "m2");
AbstractMedia m3 = new Media(press, "m3");

Console.WriteLine("-----------------");
m1.Send("hello, this is a bad news");

Console.WriteLine("-----------------");
m2.Send("hello, this is anoter bad news");

Console.WriteLine("-----------------");
m3.Send("hello, this is a real good news");

从输出中就能看到，新闻其实是可以造假的。

-----------------
m1: Sending news=hello, this is a bad news

m1: received news:hello, this is a good news
m2: received news:hello, this is a good news
m3: received news:hello, this is a good news
-----------------
m2: Sending news=hello, this is anoter bad news

m1: received news:hello, this is anoter good news
m2: received news:hello, this is anoter good news
m3: received news:hello, this is anoter good news
-----------------
m3: Sending news=hello, this is a real good news

m1: received news:hello, this is a real good news
m2: received news:hello, this is a real good news
m3: received news:hello, this is a real good news



## Mediator的优缺点

* 优点：解决组件之间的强耦合。组件的增加删减互相不受影响。
* 缺点：权利过分集中，会导致臃肿。如果有某些组件之间的交互需要特殊的处理，会增加Mediator的逻辑难度。如果多组组件都需要特殊交互，Mediator的处理会异常复杂。

