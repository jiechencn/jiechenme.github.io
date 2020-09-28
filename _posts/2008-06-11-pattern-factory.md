---
title: 让子类处理-factory method-子类生成实例
author: Jie Chen
date: 2008-06-11
categories: [Design,Pattern]
tags: [java]
---

前面实验了template method的组装方法，父类中规定了子过程和最后的组装。子过程的实现是在子类中完成的，组装是父类完成。如果改造一下这个template method，把子过程用来生成一个具体的其他类的实例，就变成了工厂方法模式。


子过程可以生成一个其他类，如果有多个template method抽象类的子类，就可以生成多个不同类型的类。工厂模式的目的是根据条件生成不同的具体的类实例。

简单一点来举例。我有一个生产OEM的电脑厂ComputerFactory，贴牌生产不同品牌的电脑，比如Dell和IBM（继承于父类Computer），分别是在DellWorkshop和IBMWorkshop两条生产线上完成的。ComputerFactory是个抽象类，定义了design和produce的抽象方法，这两个方法由DellWorkshop和IBMWorkshop具体去实现，生成不同的Dell或者IBM的实例，也就是说template method模式中的子过程负责Computer的子类（Dell/IBM）实例的生成。最后ComputerFactory用template method模式进行组装完成。


定义抽象的Computer和两个子类 Item/Dell。它们将会被template method模式中的ComputerFactory的子过程实例化。

~~~
public abstract class Computer {
    public Computer(){}
    public abstract String toString();
    public abstract void use();
}
~~~
~~~
class Dell extends Computer {
    public String toString(){
        return "one dell computer is produced";
    }

    @Override
    public void use() {
        System.out.println("method of generated instance: IBM");
    }
}
~~~
~~~
public class IBM extends Computer {
    public String toString(){
        return "one ibm computer is produced";
    }
    @Override
    public void use() {
        System.out.println("method of generated instance: IBM");
    }
}
~~~

抽象的ComputerFactory，用template method模式设计。design()和produce()抽象方法交给子类去实现，负责完成子类的实例化。而create()负责最后的组装并返回实例(Dell/IBM)。

~~~
public abstract class ComputerFactory {

    public final Computer create(){
        Computer comp = design();
        produce(comp);
        return comp;
    }

    protected abstract void produce(Computer comp);

    protected abstract Computer design();
}
~~~

DellWorkshop和IBMWorkshop必须继承ComputerFactory，生成各自的产品类实例。

~~~
class DellWorkshop extends ComputerFactory {

    @Override
    protected void produce(Computer comp) {
        System.out.println(comp.toString());
    }

    @Override
    protected Computer design() {
        return new Dell();
    }
}
~~~
~~~
public class IBMWorkshop extends ComputerFactory{
    @Override
    protected void produce(Computer comp) {
        System.out.println(comp.toString());
    }

    @Override
    protected Computer design() {
        return new IBM();
    }
}
~~~

最后测试它们的生成行为。分别定义两个不同的workshop，向上转型为ComputerFactory，调用ComputerFactory的组装方法create()，返回Computer的某个子类实例。

~~~
ComputerFactory cf1 = new IBMWorkshop();
Computer cm1 = cf1.create();
cm1.use();

ComputerFactory cf2 = new DellWorkshop();
Computer cm2 = cf2.create();
cm2.use();
~~~
		
