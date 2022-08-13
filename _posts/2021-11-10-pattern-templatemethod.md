---
title: TemplateMethod模式-将固定流程模板化
author: Jie Chen
date: 2021-11-10
categories: [Design,Pattern]
tags: [csharp]
---

类似于工厂的流水线，或者审批流程的工作流，很多类为了实现某个功能，会将一系列的子方法组合起来，形成一套固定的模板，暴露出一个公共方法。调用者只要调用这个公共方法即可实现功能。

对于类扩展而言，子类沿用父类的这个公共方法，但是这个方法里面的各个子方法的实现，可以有多种体现：

* 父类定义子方法，禁止子类override

* 父类定义子类的抽象方法，要求子类必须实现

* 父类定义子类的虚拟方法，子类可以实现也可以不实现

无论哪一种方式，所有这些子方法组合起来的大的方法，一定是不允许子类覆盖的。上面的公共方法和子方法，组合起来形成设计模式里的模板方法模式。

## 模板方法的创建

假设我们要生产一辆汽车

~~~
public class Car
{
  public string Brand { get; set; }

  public string Engine { get; set; }

  public string Tyre { get; set; }
}
~~~


它有一个完整的生产线流程：

~~~
CreateBody();
CreateEngine();
CreateTyre();
AssemblyAll();
~~~

我们定义一个抽象的汽车工厂，将流水线设计成模板方法模式。
~~~
public abstract class AbstractCarBuilder
{
  protected Car car = new Car();
  public Car Create()
  {
    CreateBody();
    CreateEngine();
    CreateTyre();
    AssemblyAll();
    return car;
  }

  private void CreateEngine()
  {
    Console.WriteLine("Generic engine");
  }

  protected abstract void CreateTyre();

  protected abstract void CreateBody();

  protected virtual void AssemblyAll()
  {
    Console.WriteLine("Generic assemble line");
  }
}
~~~

这里的公共方法是 public Car Create() ， 就是工厂里的流水线。里面定义了多个子方法。

这些子方法的实现，到底是由这个抽象类完成还是子类（具体的骑车工厂）完成，取决于业务需要。因此，假设轮胎和车身的制造，必须由子类实现，我们就定义成 abstract访问限制。引擎的制造必须由父类生产，就定义成 private。对于最后的装配 AssemblyAll，抽象类提供了默认的装配实现，也允许子类可以覆盖。因此设置成 virtual 。

## 子类实现模板里的子方法

现在，模板里的各个子方法必须由子类来实现。比如一个特斯拉工厂，实现上面提到的各个 abstract方法。至于AssemblyAll要不要做override在子类里做覆盖，取决于业务。

~~~
class TeslaBuilder : AbstractCarBuilder
{
  protected override void CreateBody()
  {
    car.Brand = "Tesla";
  }

  protected override void CreateTyre()
  {
    car.Tyre = "Tesla tyre";
  }

  protected override void AssemblyAll()
  {
    Console.WriteLine("Tesla assemble line");
  }
}
~~~


## 使用

使用这个模式创建一辆汽车，就非常简单了。

~~~
AbstractCarBuilder builder = new TeslaBuilder();
Car car = builder.Create();
~~~

## 功能扩展

模板方法模式适合于那种有固定组合方法的设计，也就是流程固定，流程内部的细节的实现，可以由父类或者子类的设计者根据业务需要协商决定由谁来完成，配合语言层面的方法访问修饰符（abstract，virtual，private等），能灵活控制各自的具体角色。

如果有新增的工厂加入进来，可以照搬TeslaBuilder的代码。