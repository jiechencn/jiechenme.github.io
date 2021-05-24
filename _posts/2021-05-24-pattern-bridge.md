---
title: bridge模式-将类的功能和实现从两个维度分离开来
author: Jie Chen
date: 2021-05-24
categories: [Design,Pattern]
tags: [csharp]
---

桥接模式用于最开始的设计阶段，需要对一个对象进行不同维度的分析，并把不同维度独立出来，也就是把功能和实现进行分离

比如对于饮料这个对象而言，从大小的角度看有大杯、中杯和小杯三种功能，从口味的角度看有加冰可乐，鲜榨柠檬橘子汁等两种实现。反过来，也可以把口味定义为功能，把大小当成实现。

具体哪个维度作为功能，哪个维度作为实现，可以选择相对单一简单的那个维度为功能，复杂的维度作为实现。

所以对于杯子的大小，可以定义为一个功能的多个继承； 口味的实现比较复杂，就把这个维度定义为多个实现。


## 抽象的功能

~~~
public abstract class Drink
{
  private Taste taste;
  
  public Drink(Taste taste)
  {
    this.taste = taste;
  }

  public virtual void Order(int count)
  {
    taste.Mix();
    Console.WriteLine($"Ordered {count} glasses of {taste.ToString()}");
  }
}
~~~

功能类的具体子类继承，其中大杯还赠送礼物。

~~~
public class SmallCupDrink : Drink
{
  public SmallCupDrink(Taste taste) : base(taste)
  {
  }
}


public class MiddleCupDrink : Drink
{
  public MiddleCupDrink(Taste taste) : base(taste)
  {
  }
}

public class LargeCupDrink : Drink
{
  public LargeCupDrink(Taste taste) : base(taste)
  {
  }

  public override void Order(int count)
  {
    base.Order(count);
    Console.WriteLine("Free gift");
  }
}
~~~


## 功能的实现

为口味定义多个实现，可以让它们共同继承于一个抽象类。

~~~
public abstract class Taste
{
  public List<string> Types { get; set; } = new List<string>();
  public virtual string Name { get; }

  public virtual void Mix()
  {
    System.Console.WriteLine($"mix {string.Join(", ", Types.ToArray())}");
  }

  public override string ToString()
  {
    return $"{Name}: {string.Join(",", Types.ToArray())}";
  }
}
~~~

不同口味的实现部分

~~~
public class LemonOrangeTaste : Taste
{
  public override string Name  => "LemonOrange";
  public override void Mix()
  {
    Types.Add("lemon");
    Types.Add("orange");
    base.Mix();
  }
}
~~~

~~~
public class IceCokeTaste : Taste
{
  public override string Name => "IceCoca";

  public override void Mix()
  {
    Types.Add("Ice");
    Types.Add("Coca");
    base.Mix();
  }
}
~~~

## 客户端调用

通过对象委托的方式，把实现类（比如 IceCokeTaste）注入到功能抽象类（比如 LargeCupDrink）中。

~~~
Drink iceCoca = new LargeCupDrink(new IceCokeTaste());
iceCoca.Order(2);

Drink lemon = new SmallCupDrink(new LemonOrangeTaste());
lemon.Order(1);
~~~

## 后续扩展

今后无论是增加杯子大小，还是增加口味，只需要在功能类(Drink)或者实现类（Taste）中进行各自的扩展就可以了，两个维度互不干扰。