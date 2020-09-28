---
title: 让子类处理-template method-子类处理具体的方法
author: Jie Chen
date: 2008-04-10
categories: [Design]
tags: [Java,Pattern]
---

template method模式可以让我们顶一个类实现某个功能的的具体的流程，这个流程是规范的，不能被改变。每一个流程是一个方法，模板不定义方法体，方法体由不同的子类去做具体实施。

这就好比盖房这样的工程。盖房的流程是：挖地基、盖房、粉刷装饰。工程模板定义了这个流程的先后顺序，但是地基怎么挖，盖房怎么盖，由不同的房子（就是子类）的特点来决定。

加入盖房工程设计成一个类，我可以做成一个抽象类，build()方法内规定了工程的先后顺序，同时用final来限制，不允许子类重写这个方法修改顺序。

dig，construct和decorate为protected，只允许子类或者package内方法。

~~~
public abstract class HouseTemplate {
    private String hourseName;
    public HouseTemplate(){}

    public HouseTemplate(String hourseName){
        this.hourseName = hourseName;
    }
    public final void build(){
        System.out.println("begin to build " + hourseName);
        dig();
        construct();
        decorate();
        System.out.println(hourseName + " is built");
    }

    protected abstract void dig();
    protected abstract void construct();
    protected abstract void decorate();
}
~~~

当我要盖不同的房屋时，只要详细设计dig，construct和decorate就可以了。

~~~
public class ApartmentImpl extends HouseTemplate {

    public ApartmentImpl(){
        super("apartment");
    }

    @Override
    protected void dig() {
        System.out.println("dig 10 metres deep");
    }

    @Override
    protected void construct() {
        System.out.println("simple construct");
    }

    @Override
    protected void decorate() {
        System.out.println("simple decoration");

    }
}
~~~

~~~
public class VillaImpl extends HouseTemplate {

    public VillaImpl(){
        super("villa");
    }
    @Override
    protected void dig() {
        System.out.println("dig 5 metres deep");
    }

    @Override
    protected void construct() {
        System.out.println("construct villa with europe style");
    }

    @Override
    protected void decorate() {

        System.out.println("build beautiful garden");
        System.out.println("and luxury decoration");

    }
}
~~~

测试这个程序，会注意到只要调用父类的build方法，内部的流程会在具体子类中去执行。

~~~
HouseTemplate villa = new VillaImpl();
villa.build();

HouseTemplate apart = new ApartmentImpl();
apart.build();
~~~

模板方式的好处，是在父类中用抽象方法设计好了一组规范的流程，子类只要去实现流程内部规定的每个方法就可以了。