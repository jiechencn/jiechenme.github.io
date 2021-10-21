---
title: 将Simple Factory改造成 Factory Method
author: Jie Chen
date: 2021-10-21
categories: [Design,Pattern]
tags: [csharp]
---

这两种工厂模式，目的是通过一个工厂类，创建一个具体的对象。但是何时创建，以及在哪里创建，创建哪种对象，有很大区别。

* 简单工厂：由工厂类根据输入的参数，来选择创建何种对象。对象是在工厂的某个方法内直接创建的。具体的创建何种对象，由输入参数决定。如果有多种不同的对象，创建的方法会通过switch语句来选择，容易引起代码膨胀。

* 工厂方法：创建对象不再在工厂类中直接创建，而是引入一个抽象工厂的一个方法，让工厂子类实现这个方法，将创建的过程延迟到了工厂子类的方法实现里去。创建何种对象，需要外部指定一个具体的工厂子类。如果不小心，很容易会把工厂子类的选择变成了简单工厂模式，这个选择可以通过反射或者字典对象映射出来。

## 例子

假设需要实现一个功能，让某种鱼能游起来，比如让小鱼游，或者让大鱼游。例子代码能够看出两者的区别，以及对工厂方法的工厂子类选择的改进。

## 简单工厂

定义一个IFish的接口，和它的一个方法。

~~~
public interface IFish
{
    void Swim();
}
~~~

在这个接口上，定义两种鱼，大鱼和小鱼

~~~
class LittleFish : IFish
{
    public void Swim()
    {
        Console.WriteLine($"{nameof(LittleFish)} is swimming");
    }
}

class BighFish : IFish
{
    public void Swim()
    {
        Console.WriteLine($"{nameof(BighFish)} is swimming");
    }
}
~~~

然后就是一个简单的工厂类，根据输入的参数，生成具体的一种鱼类并返回。在调用类里，再执行fish.Swim()就实现了上面的需求。

所以，创建何种鱼，是由工厂直接决定的。

~~~
public class FishFactory
{
    public static IFish BuildFish(string size)
    {
        switch (size)
        {
            case "little":
                return new LittleFish();
            case "big":
                return new BighFish();
            default:
                return null;
        }
    }
}
~~~

客户类

~~~
static void Main(string[] args)
{
    IFish littleFish = FishFactory.BuildFish("little");
    littleFish.Swim();
    // 或者
    IFish bigFish = FishFactory.BuildFish("big");
    bigFish.Swim();
}
~~~

可以想象，如果后续有更多种类的鱼对象需要添加，工厂类必须在switch里添加，导致switch越来越庞大。

## 工厂方法

工厂方法，就是把具体的对象的创建，放到工厂子类里去创建。

对象（鱼 IFish, BigFish, LittleFish）的类，保持不变。需要改变的是工厂类，改造成一个抽象类。

~~~
abstract class AbstraceFishFactory
{
    protected abstract IFish BuildFish();

    public void LetFishSwim()
    {
        var fish = BuildFish();
        fish.Swim();
    }
}
~~~

在这个抽象工厂类AbstraceFishFactory里，提供了BuildFish的方法，让工厂子类去继承，让工厂子类在继承的方法里决定创建何种鱼类。这就是区别于简单工厂的地方。

然后通过LetFishSwim实现让鱼游动的需求。

所以，现在就定义两个工厂子类，实现抽象工厂的抽象方法，每个工厂子类负责创建一种对象鱼。

~~~
class BigFishFactory : AbstraceFishFactory
{
    protected override IFish BuildFish()
    {
        return new BigFish();
    }
}


class LittleFishFactory : AbstraceFishFactory
{
    protected override IFish BuildFish()
    {
        return new LittleFish();
    }
}
~~~

客户类改造成：

~~~
class Program
{
    static void Main(string[] args)
    {
        var factory1 = new LittleFishFactory();
        factory1.LetFishSwim();

        // 或者
        var factory2 = new BigFishFactory();
        factory2.LetFishSwim();
    }
}
~~~

如果有新的种类的鱼加入进来，只需要做两件事情：创建类实现IFish，并创建一个该类的鱼的工厂，继承抽象工厂。不需要改动抽象工厂本身。

## 功能扩展

上面的例子，再总结一下，如果有新的鱼的种类添加进来：

简单工厂：
* 新建一个新的鱼类，实现IFish
* 修改FishFactory，添加switch分支

工厂方法：
* 新建一个新的鱼类，实现IFish
* 新建AbstraceFishFactory的新的子工厂，继承BuildFish方法，在这个方法里专门负责这种鱼类的创建


## 工厂方法的工厂子类的选择问题

在工厂方法的模式中，客户类需要实现这个功能，比如让大鱼游起来，必须要事先知道大鱼的工厂类名是BigFishFactory还是LittleFishFactory，这样的选择方法必须是客户类自己知道的。这样一来，好像又回到了简单工厂的switch那种实现上来了。

如果要把它和简单工厂彻底区别开来，可以通过下面的方法来解决。

### 用Dictionary来映射

定义一个鱼类种类的enum类型，把所有的子类工厂和enum类型匹配起来。然后通过一个manager类来管理工厂，负责工厂的选择。

~~~
enum FishTypes
{
    Little,
    Big
}
class FishFactoryManagerA
{
    private readonly IDictionary<FishTypes, AbstraceFishFactory> factories;
    public FishFactoryManagerA()
    {
        factories = new Dictionary<FishTypes, AbstraceFishFactory>
        {
            { FishTypes.Big, new BigFishFactory() },
            { FishTypes.Little, new LittleFishFactory() },
        };
    }

    public AbstraceFishFactory GetFactory(FishTypes type)
    {
        return factories[type];
    }
}
~~~

客户类调用是： 

~~~
var factory3 = new FishFactoryManagerA().GetFactory(FishTypes.Little);
factory3.LetFishSwim();
~~~

缺点是，当鱼种类需要扩展时，我必须要额外再修改这个enum和dictionary，工作量又多了，这种方法和简单工厂在选择何种鱼的方式上没什么区别。

### 使用反射来动态地获取子类工厂

上面是通过dictionary通过mapping关系来建立 “鱼种类 -> 对应工厂的实例”。这里可以通过反射，把这种对应关系修改为 “鱼的种类 -> 对应工厂类的类名”，巧妙地连接起来。

~~~
enum FishTypes
{
    Little,
    Big
}
class FishFactoryManagerB
{
    private readonly IDictionary<FishTypes, AbstraceFishFactory> factories;
    public FishFactoryManagerB()
    {
        factories = new Dictionary<FishTypes, AbstraceFishFactory>();
        foreach (FishTypes t in Enum.GetValues(typeof(FishTypes)))
        {
            // 创建出 FactoryMethod.LittleFishFactory 这样的实例来
            var factory = (AbstraceFishFactory)Activator.CreateInstance(Type.GetType("FactoryMethod." + Enum.GetName(typeof(FishTypes), t) + "FishFactory"));
            factories.Add(t, factory);
        }
    }

    public AbstraceFishFactory GetFactory(FishTypes type)
    {
        return factories[type];
    }
}
~~~

同样需要定义enum类型。FishFactoryManagerB负责工厂选择问题。扩展鱼类种类时，这个manager类不需要修改，只额外添加enum就可以了。

