---
title: 构造模式 Builder - 通过Director来调度指定Builder的一组方法
author: Jie Chen
date: 2021-10-29
categories: [Design,Pattern]
tags: [csharp]
---

Builder 模式和抽象工厂模式非常相像。区别在于：

* 抽象工厂模式： 抽象工厂模式要实现的功能是： 让消费者指定具体工厂，交给Client调用抽象工厂的接口方法，将一组多个抽象的零件组合成一个抽象的产品。

* 构造模式 让消费者指定具体工厂，交给监督者Director调用抽象工厂的部分或全部接口，将部分或者全部零件(或者不同的过程)组装成一个抽象产品，并直接返回给消费者。


# 例子

比如银行要为客户制作借记卡或者信用卡，银行指定这个卡的builder，向银监局申请监理服务，银监局负责调度builder的各个方法完成生产，最后builder直接向银行返回卡产品。

角色定义：

Builder： 只负责接口定义，这个例子里是ICardBuilder，接口类似：生产空白卡片，卡片上打印姓名和卡号，最后一步是设置消费额度和年费。最终生成一个产品 ICard。

ConcreteBuilder： 具体的实现类： CreditCardBuilder或者DebigCardBuilder，

Director： 负责指导Builder的构造card的过程，在调用DebigCardBuilder的时候，可以不必调用“设置消费额度”。



## ICard产品和具体实现

~~~
    interface ICard
    {
        string Name { get; set; }
        string CardType { get; set; }
        int Limit { get; set; }
        int AnnualCharge { get; set; }
        string CardDetail { get; }
    }
~~~

~~~
    class CreditCard : ICard
    {
        public string Name { get; set; }
        public CreditCard(string name)
        {
            Name = name;
        }
        public string CardType { get; set; }
        public int Limit { get; set; }
        public int AnnualCharge { get; set; }
        public string CardDetail { get => $"Card: {nameof(CreditCard)}, Limit: {Limit}, Charge: {AnnualCharge}"; }

    }
~~~

~~~
    class DebitCard : ICard
    {
        public string Name { get; set; }
        public DebitCard(string name)
        {
            Name = name;
        }
        public string CardType { get; set; }
        public int Limit { get; set; }
        public int AnnualCharge { get; set; }
        public string CardDetail { get => $"Card: {nameof(DebitCard)}, Limit: {Limit}, Charge: {AnnualCharge}"; }
    }
~~~

## ICardBuilder和实现

ICard产品是在这里完成的。

~~~
    interface ICardBuilder
    {
        public ICard Card { get; set; }   // 生成ICard产品
        public void BuildBlankCard();
        public void PrintName();
        public void PrintCardNumber();
        public void SetLimit();
        public void SetCharge();
    }
~~~

~~~
    class DebitCardBuilder : ICardBuilder
    {
        public ICard Card { get; set ; }
        public DebitCardBuilder(string name)
        {
            Card = new DebitCard(name);
        }
        public void BuildBlankCard()
        {
            Card.CardType = "DebitCard";
        }

        public void PrintCardNumber()
        {
            Console.WriteLine($"{Card.CardType} card number is printed");
        }

        public void PrintName()
        {
            Console.WriteLine($"{Card.Name} is printed");

        }

        public void SetCharge()
        {
            Card.AnnualCharge = 0;
        }

        public void SetLimit()
        {
            Card.Limit = -1;
        }
    }
~~~

~~~
    class CreditCardBuilder : ICardBuilder
    {
        public ICard Card { get; set ; }

        public CreditCardBuilder(string name)
        {
            Card = new CreditCard(name);
            
        }
        public void BuildBlankCard()
        {
            Card.CardType = "CreditCard";
        }

        public void PrintCardNumber()
        {
            Console.WriteLine($"{Card.CardType} card number is printed");
        }

        public void PrintName()
        {
            Console.WriteLine($"{Card.Name} is printed");

        }

        public void SetCharge()
        {
            Card.AnnualCharge = 200;
        }

        public void SetLimit()
        {
            Card.Limit = 20000;
        }
    }
~~~


## Director类

负责调用builder的各个接口方法

~~~
    class BankDirector
    {
        public ICardBuilder Builder { get; set; }
        public void Construct()
        {
            Builder.BuildBlankCard();
            Builder.PrintCardNumber();
            Builder.PrintName();
            Builder.SetCharge();
            Builder.SetLimit();
        }
    }
~~~

如果有特殊的需求，比如不需要设置姓名的卡片，可以创建一个adhoc的方法

~~~
        public void ConstructSpecialCard()
        {
            Builder.BuildBlankCard();
            Builder.PrintCardNumber();
            //Builder.PrintName(); -- 不印上姓名
            Builder.SetCharge();
            Builder.SetLimit();
        }
~~~

这样就完成了整个模式的设计。

## 使用

通过Director充分使用了Builder模式。
~~~
	// with director
	BankDirector director = new BankDirector();
	ICardBuilder builder = new CreditCardBuilder("Jie Chen");
	director.Builder = builder;
	director.Construct();
	ICard card = builder.Card;
	System.Console.WriteLine(card.CardDetail);
~~~

如果不通过Director，也可以实现（但也失去了设计模式的意义）：

~~~
	ICardBuilder builder2 = new DebitCardBuilder("Chen Jie");
	builder2.BuildBlankCard();
	builder2.PrintCardNumber();
	builder2.PrintName();
	builder2.SetCharge();
	builder2.SetLimit();
	ICard card2 = builder2.Card;
	System.Console.WriteLine(card2.CardDetail);
~~~


# 功能扩展

* 添加一个新的Builder很简单，实现ICardBuilder的接口即可。
* 自定义Builder的某个流程也很简单，只要在Director中新建一个方法，调用Builder中的一部分方法


