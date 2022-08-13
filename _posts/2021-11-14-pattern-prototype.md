---
title: Prototype模式-简化类实例的生成
author: Jie Chen
date: 2021-11-14
categories: [Design,Pattern]
tags: [csharp]
---

实例的创建一般都是通过 new 方法初始化出来的。但是在一些比如下面的复杂情况下，生成一个类实例并不是一件容易的事情。

* 构造函数的实现太过复杂

* 构造函数的实现太耗费资源

* 需要创建大量的非常雷同的实例

如果我们能将一个已经创建出来的类实例保存起来，下次再创建一个新的时候直接从某个地方拿出来用，极大简化这个创建过程。这个就是Prototype模式。

这里需要注意的一个重点是：常规类都是引用类型的，为了避免根据保存起来的实例而复制出来的其他多个实例之间互不关联，需要注意浅拷贝和深度拷贝的区别。

## 用Prototype制造一个机器人

假设我们要生产一批机器人，智障的和高智商的，各种各样。我们可以先制定一个机器人的原型。

~~~
public abstract class AbstractRobotPrototype
{
  public Brain Brain { get; set; }
  public Body Body { get; set; }
  public virtual AbstractRobotPrototype CreateClone()
  {
    return this.MemberwiseClone() as AbstractRobotPrototype;
  }

  public override string ToString()
  {
    return $"{this.GetType().Name} = {this.GetHashCode()}: Brain={Brain.Think.Width}, Body={Body.Skin.Color}";
  }
}
~~~

这个原型类提供了 CreateClone 一个克隆方法，用来实现深浅拷贝的功能。确保克隆出来的机器人都是独立的，一个机器人的修改不会影响另外的机器人。

现在计划生产两类机器人：智障猫和智慧人。

~~~
public class CatRobot : AbstractRobotPrototype
{
}

public class HumanRobot : AbstractRobotPrototype
{
  //public override AbstractRobotPrototype CreateClone()
  //{
  //// customized clone or deep clone
  //}
}
~~~


## 管理原型对象的辅助类

Prototype对象的管理可以通过一个辅助类来完成。RobotManager用来完成各类原型机器人的注册（比如简单地保存在一个字典里），以及一个Create方法，从字典里拿出同样类型的机器人原型的一份克隆对象。

~~~
public class RobotManager
{
  private IDictionary<string, AbstractRobotPrototype> robots = new Dictionary<string, AbstractRobotPrototype>();

  public void Register(string robotType, AbstractRobotPrototype robot)
  {
    robots.Add(robotType, robot);
  }

  public AbstractRobotPrototype Create(string robotType)
  {
    if (robots.TryGetValue(robotType, out var robot))
    {
      return robot.CreateClone();
    }
    return null;
  }
}
~~~


## 使用

现在通过调用方法来检验这个模式。我们创建两类机器人，黑白色智障猫和黄种智慧人。

~~~
CatRobot cat = new CatRobot
{
  Brain = new Brain { Think = new Think { Width = "Poor" } },
  Body = new Body { Skin = new Skin { Color = "Black and white" } }
};

HumanRobot human = new HumanRobot
{
  Brain = new Brain { Think = new Think { Width = "Good" } },
  Body = new Body { Skin = new Skin { Color = "Yellow" } }
};
~~~

将这两类机器人通过RobotManager注册好。

~~~
RobotManager robotManager = new RobotManager();

robotManager.Register("cat", cat);
robotManager.Register("human", human);
~~~

然后从RobotManager中获取2个智障猫和2个智慧人，并修改其中一只猫和一个人的属性，观察是不是会影响另外的机器人。

~~~
AbstractRobotPrototype robot1a = robotManager.Create("cat");
AbstractRobotPrototype robot1b = robotManager.Create("cat");
robot1b.Brain = new Brain { Think = new Think { Width = "Good" } };
robot1b.Body = new Body { Skin = new Skin { Color = "Colorful" } };

AbstractRobotPrototype robot2a = robotManager.Create("human");
AbstractRobotPrototype robot2b = robotManager.Create("human");
robot2b.Brain = new Brain { Think = new Think { Width = "Poor" } };
robot2b.Body = new Body { Skin = new Skin { Color = "Black" } };

Console.WriteLine(robot1a.ToString());
Console.WriteLine(robot1b.ToString());
Console.WriteLine(robot2a.ToString());
Console.WriteLine(robot2b.ToString());
~~~

下面的结果显示，创建出来的四个机器人，完全独立。一个修改不会影响另一个。
~~~
CatRobot = 58225482: Brain=Poor, Body=Black and white
CatRobot = 54267293: Brain=Good, Body=Colorful
HumanRobot = 18643596: Brain=Good, Body=Yellow
HumanRobot = 33574638: Brain=Poor, Body=Black
~~~
