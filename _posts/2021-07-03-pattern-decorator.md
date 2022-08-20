---
title: Decorator模式-用装修的概念自由组合功能
author: Jie Chen
date: 2021-07-03
categories: [Design,Pattern]
tags: [csharp]
---

Decorator模式从名字上来看就是有一点装修房子的感觉。比如在毛坯房的基础上，可以层层装修、丰富完善一个房子。它的使用场景针对一个已经存在的功能，想要在此基础上添加一些新的功能。但是这些新添加的功能不一定是确定的，可以根据需要添加一批功能，也可能想添加另外一组功能。

举例： 有一些紧急的通知，平时都是通过发送邮件的方式通知人员。后来有新的需求，想要同时发送邮件和短信。再然后，想同时发送邮件、短信和拨打电话给人员。又可能在邮件系统当机的情况下，只发送短信和拨打电话。这些发送通知的方式，可以是一个自由组合。

如果把发送邮件这个基本功能当成一个毛坯房，那发送短信和拨打电话就像是给毛坯房做一个装修，要么只发送短信，要么只拨打电话，要么同时都有。

## 构造毛坯房

把上面的逻辑用Decorator模式设计一下。先构造一个基本的毛坯房，就是一个基本功能。

~~~
abstract class AbstractAlert
{
  public abstract void Send();
}
~~~
.
~~~
class EmailAlert : AbstractAlert
{
  public override void Send()
  {
    Console.WriteLine("Send alert via Email");
  }
}
~~~

抽象方法 Send() 定义了规格。

## 设计一个装饰器的抽象

装饰器的目的是为了装修毛坯房本身，用对象委托的方式，将要被装修的毛坯房（或半成品房）包裹起来，在实现Send()的抽象方法时，调用这个被委托对象自己的 Send（）功能。

因为这个是装饰器是一个抽象的类，Send中不会定义具体的功能，但会调用被委托对象的Send的功能。至于后面的每一个装饰，会继承装饰器的Send，然后会定义自己的功能。

~~~
abstract class AbstractAlertDecorator : AbstractAlert
{
  protected AbstractAlert alert;
  public AbstractAlertDecorator(AbstractAlert alert)
  {
    this.alert = alert;
  }

  public override void Send()
  {
    if (alert != null)
    {
      alert.Send();
    }
  }
}
~~~

## 定义每一个装饰

比如现在发送短信和拨打电话，实现抽象装饰器。同时在Send实现中，必须做两件事情：

* 调用父类也就是抽象装饰器的Send，目的是间接地调用被委托对象的Send

* 做自己的具体的逻辑：发消息或打电话

~~~
class SmsAlert : AbstractAlertDecorator
{
  public SmsAlert(AbstractAlert alert) : base(alert)
  {
  }

  public override void Send()
  {
    base.Send();

    // the logic to send SMS
    Console.WriteLine("Send alert via SMS");
  }
}
~~~
.
~~~
class PhoneAlert : AbstractAlertDecorator
{
  public PhoneAlert(AbstractAlert alert) : base(alert)
  {
  }

  public override void Send()
  {
    base.Send();

    // the logic to send Phone
    Console.WriteLine("Send alert via Phone");
  }
}
~~~

## 开始装修

比如同时发送邮件、短信和电话，可以这么调用：

~~~
AbstractAlert email = new EmailAlert();
AbstractAlertDecorator sms = new SmsAlert(email);
AbstractAlertDecorator phone = new PhoneAlert(sms);
phone.Send();
~~~

如果只是短信和电话，可以这么用：

~~~
AbstractAlertDecorator sms = new SmsAlert(null);
AbstractAlertDecorator phone = new PhoneAlert(sms);
phone.Send();
~~~

无论哪一组功能的组合，委托都是通过抽象装饰器提供来实现，因为原始的毛坯房也就是 AbstractAlert 没有提供委托功能。

## 其他相似模式的比较

这种自由组合功能的方式，也许可以通过 AbstractAlert 的多个继承来实现，但是远没有这种装饰模式来得简洁。因为用继承的方式，要连续调用多个实例的Send方法，较为累赘。而装饰器模式，只调用最外面一层装修物的Send就可以了。

如果把Email当成毛坯房，SMS当成半成品房，那么Phone可能就是成品房，这三者都有一致性。后者把前者包裹起来，有点像Composite模式。但是Composite关注的是数据的容器特征。而Decorator关注的功能的组合叠加。
