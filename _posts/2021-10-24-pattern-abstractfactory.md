---
title: 抽象工厂 Abstract Factory
author: Jie Chen
date: 2021-10-24
categories: [Design,Pattern]
tags: [csharp]
---

抽象工厂模式的目的是为了应对一组factory，他们生产各自的一组零件。Client只负责调用工厂接口的方法，组合成产品。至于是哪个工厂负责生产这些零件，Client不需要知道，他只要能够接受外部传入进来的具体的工厂即可。一句话解释：抽象工厂模式要实现的功能是： 让消费者指定具体工厂，交给Client调用抽象工厂的接口方法，将一组多个抽象的零件组合成一个抽象的产品。

抽象工厂模式和工厂方法模式的区别在于：

* 工厂方法需要调用者明确通过调用具体的工厂的具体方法，而抽象工厂模式只要让调用者明确一个具体的工厂类，传给Client，让Client去通过工厂接口来完成接口方法的调用。
* 抽象工厂模式其实是工厂的工厂。


# 例子

假设国家命令银监局通过某银行发行两种银行卡，信用卡和借记卡。现在有两个银行，CMB和ICBC，他们都可以发行这两类卡。

然后我们就可以根据抽象工厂模式来定义各自的角色。

## Client

这里的Client，其实就是银监局。调用者是国家。我们先构造好下面的代码框架。国家指定发行的银行为具体某一个银行，告诉银监局CardClient，CardClient负责调用工厂接口的 BuildDebitCard 和 BuildCreditCard即可生产两种卡（零件），完成产品（零件的组合）。它完全不需要指定具体是哪个银行。

~~~
    class CardClient
    {
        private ICardFactory cardFactory;
        public CardClient(ICardFactory cardFactory)
        {
            this.cardFactory = cardFactory;
        }

        public void Create()
        {
            ICreditCard credit =  cardFactory.BuildCreditCard();
            IDebitCard debit = cardFactory.BuildDebitCard();
            Console.WriteLine("{0} created by {1}", credit.GetType(), cardFactory.GetType());
            Console.WriteLine("{0} created by {1}", debit.GetType(), cardFactory.GetType());
        }
    }
~~~

## 工厂接口和工厂子类实现

定义工厂接口（这里的模式叫抽象工厂模式，但不一定非要定义成抽象类）和具体子类，负责具体的零件（信用卡和借记卡）。零件的组合由上面的CardClient负责组装。

~~~
    interface ICardFactory
    {
        public IDebitCard BuildDebitCard();
        public ICreditCard BuildCreditCard();
    }
~~~

~~~
    class IcbcFactory : ICardFactory
    {
        public ICreditCard BuildCreditCard()
        {
            return new IcbcCreditCard();
        }

        public IDebitCard BuildDebitCard()
        {
            return new IcbcDebitCard();
        }
    }
~~~

~~~
    class CmbFactory : ICardFactory
    {
        public ICreditCard BuildCreditCard()
        {
            return new CmbCreditCard();
        }

        public IDebitCard BuildDebitCard()
        {
            return new CmbDebitCard();
        }
    }
~~~

工厂子类在生产具体的零件的时候（卡类型），返回了诸如 IcbcCreditCard和CmbDebitCard的具体零件。应该需要为这些具体零件抽象成一个接口。

## 零件接口和具体零件的实现

卡接口

~~~
    interface ICard
    {
    }
	interface IDebitCard : ICard
    {
    }
	interface ICreditCard : ICard
    {
    }
~~~

创建具体的卡类，实现简单一点，没有定义任何方法和属性。

~~~
    class CmbCreditCard : ICreditCard
    {
    }
    class IcbcCreditCard : ICreditCard
    {
    }
    class CmbDebitCard : IDebitCard
    {
    }
    class IcbcDebitCard : IDebitCard
    {
    }
~~~

这样就完成了整个模式的设计。

## 使用

现在来调用一下。指定一个具体的工厂类 IcbcFactory， 传给CardClient， 让它去负责调用工厂接口的方法并完成组装。

~~~
    class Program
    {
        static void Main(string[] args)
        {
            ICardFactory cardFactory = new IcbcFactory();
            CardClient cardClient = new CardClient(cardFactory);
            cardClient.Create();
        }
    }
~~~

结果

~~~
IcbcCreditCard created by IcbcFactory
IcbcDebitCard created by IcbcFactory
~~~


# 功能扩展

优点： 添加一个新的工厂很简单.比如BOC银行 BocFactory，然后照猫画虎地新建 BocDebitCard和BocCreditCard两个类，去实现card接口。

缺点： 增减一个新的零件很繁琐。例子里有2种银行卡，假设需要增加第三种银行卡比如理财卡，那就需要修改大量的类： 

* 需要修改工厂接口和已有的所有的工厂子类添加BuildLicaiCard（）
* 需要增加ILicaiCard接口，然后为不同的工厂增加ILicaiCard的实现类 IcbcLicaiCard, CmbLicaiCard类

