---
title: 类复用-adaptor-修复现有API和需求的差异
author: Jie Chen
date: 2021-05-23
categories: [Design,Pattern]
tags: [CSharp]
---

需要对现有的类进行功能改进，但是又不想破坏现有类的结构，同时修改现有类有可能会引入bug，所以可以通过适配器的方式，把希望改进的功能挪到适配器中。

## 现有的类

一个发送纯文本短消息的接口和类。

~~~
public interface IMessageSender
{
  public void Send(string words);
}


class TextSender : IMessageSender
{
  public void Send(string words)
  {
    Console.WriteLine(words);
  }
}
~~~

给其他人调用的类的方式：

~~~
IMessageSender smsSender = new TextSender();
smsSender.Send("hello :)");
~~~


## 功能扩展

现在希望改进这个短消息功能，可以发送Emoji表情符号。但是又不想给现有的TextSender 引入可能的bug，或者大幅修改它。 所以我们购买了第三方提供的SDK。如下：

~~~
public class ThirdPartyMessageSender
{
  public virtual void Post(string content, bool supportEmoj)
  {
    string realContent = content;
    if (supportEmoj)
    {
      // 编码处理，让字符串支持emoji
      realContent = content;
    }
    Console.WriteLine($"emjo supported: {supportEmoj} : {realContent}");
  }
}
~~~

将第三方引入的类和我们现有的类做功能上合并，可以通过两种方式： 类继承，或者对象委托。

## 类继承的适配器方式

创建一个adaptor，实现我们原先的接口，同时继承第三方类

~~~
public class MessengeSenderAdaptor : ThirdPartyMessageSender, IMessageSender
{
  public void Send(string words)
  {
    Post(words, true);
  }
}
~~~

对于客户调用者而言，它完全不知道底层的改变细节，它只面对它的adapor。

~~~
IMessageSender sender = new MessengeSenderAdaptor();
sender.Send("hello1 :)");
~~~

## 对象委托的适配器方式

上面的这个适配器模式使用了类继承的方式。还有一种方式是通过对象委托。

~~~
public class MessengeSenderAdaptor2 : IMessageSender
{
  ThirdPartyMessageSender _thirdPartySender;

  public MessengeSenderAdaptor2(ThirdPartyMessageSender thirdPartySender)
  {
    _thirdPartySender = thirdPartySender;
  }
  public void Send(string words)
  {
    _thirdPartySender.Post(words, true);
  }
}
~~~

在客户端调用时，必须创建一个第三方类的实例，传给adaptor。

~~~
IMessageSender sender2 = new MessengeSenderAdaptor2(new ThirdPartyMessageSender());
sender2.Send("hello2 :)");
~~~