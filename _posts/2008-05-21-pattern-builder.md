---
title: 实例生成-builder-组装生成复杂的实例
author: Jie Chen
date: 2008-05-21
categories: [Design]
tags: [java,pattern]
---

builder和template method非常相似，就是父类定义了一个组装过程中的每一个子过程。但是不同点是：最后所有这些子过程的的组装，是由不同的人完成的。template method模式中，父类负责所有总的组装；builder模式中，这些子过程的组装是由director来完成的。

builder抽象类只负责所有子过程的方法定义。

~~~
public abstract class Builder {
    protected abstract void dig();
    protected abstract void construct();
    protected abstract void decorate();
}
~~~

具体实施由子类去完成。

~~~
public class ApartmentBuilder extends Builder {
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
public class VillaBuilder extends Builder{
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

director角色负责对builder父类的所有子过程的组装。

~~~
public class Director {
    private final Builder builder;

    public Director(Builder builder){
        this.builder = builder;
    }
    public void build(){
        builder.dig();
        builder.construct();
        builder.decorate();
    }
}
~~~

director和builder子类没有直接交互，都是通过builder抽象类，但是传递给Director构造器的必须是builder的一个实例。由于Builder是抽象类，所以builder的实例必须子类化。

~~~
public static void main(String args[]){
	Director director1 = new Director(new ApartmentBuilder());
	director1.build();

	Director director2 = new Director(new VillaBuilder());
	director2.build();
}
~~~
