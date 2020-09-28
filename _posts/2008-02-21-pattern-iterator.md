---
title: 类复用-iterator-迭代模式替代循环数组
author: Jie Chen
date: 2008-02-21
categories: [Design Pattern]
tags: [java,design]
---

一般性地，用数组来保存同类型的对象，然后用for循环遍历这个数组就能取出所有的对象，这过程非常简单直接，很好用。

但是如果数组不适应了当前应用，开发人员决定用类似Vector或者ArrayList来保存这组对象，循环遍历就要重新改写。

迭代模式很好地解决了这个问题，循环遍历时，我不用担心对象组在内部是具体怎么保存的，到底是保存为数组还是保存为集合，完全不用操心。我只要知道统一的迭代方法就可以了。

假设我有一个果篮，里面存放了一堆苹果，我在取苹果的时候，完全不用去管苹果的堆放方式，我只要知道取完一个后自动指向下一个苹果。

果篮是一个集合的实现，取苹果的动作类似通过一个指针，一个迭代的指针的实现，指向下一个对象（苹果）。这个具体迭代指针拥有果篮的实例。果篮与苹果的关系是一对多的聚合关系。

用户能操作的动作是：

* 直接把苹果放入果篮内
* 通过一个指针，从果篮内取苹果

![](/assets/res/pattern-iterator-1.png)

所以我先定义一个集合接口

~~~
public interface Aggregate {
    Iterator getIterator();
}
~~~

集合接口的指针，判断下一个对象。

~~~
public interface Iterator {
    boolean hasNext();
    Object next();
}
~~~

Basket是集合的具体实现，它维护了一个count，保存对象组的数量。

~~~
import java.util.ArrayList;

public class Basket implements Aggregate {
    private int count = 0;
    private ArrayList<Apple> apples = new ArrayList<>();

    @Override
    public Iterator getIterator() {
        return new BasketIterator(this);
    }

    public void putApple2Basket(Apple p) {
        apples.add(p);
        count++;
    }

    protected Apple getApple(int i) {
        return apples.get(i);
    }

    protected int getCount() {
        return count;
    }
}
~~~

BasketIterator是取苹果的指针，用户通过它来取下一个苹果。

~~~
public class BasketIterator implements Iterator {
    private final Basket basket;
    private int pointer;

    public BasketIterator(Basket basket) {
        this.basket = basket;
        pointer = 0;
    }

    @Override
    public boolean hasNext() {
        return pointer < basket.getCount();
    }

    @Override
    public Object next() {
        return basket.getApple(pointer++);
    }
}
~~~

对象

~~~
public class Apple {
    private String name;

    public Apple(String name) {
        this.name = name;
    }

    @Override
    public String toString() {
        return name;
    }
}
~~~

客户首先创建一个果篮，通过putApple2Basket方法存放苹果，然后获取迭代指针，通过这个指针一个个地获取下一个水果。

~~~
public class Main {
    public static void main(String... args) {
        Basket b = new Basket();
        b.putApple2Basket(new Apple("Red apple"));
        b.putApple2Basket(new Apple("Yellow apple"));
        b.putApple2Basket(new Apple("Green apple"));
        b.putApple2Basket(new Apple("Small apple"));
        b.putApple2Basket(new Apple("Big apple"));

        Iterator it = b.getIterator();
        while (it.hasNext()) {
            Apple p = (Apple) it.next();
            System.out.println(p);
        }
    }
}
~~~

通过自定义的iterator，还可以实现很多功能，比如向前向后遍历，跳步遍历等。只要BasketIterator修改内部实现方法就可以，对于用户而言，用户完全不用关心取水果的时候是按照顺序取的还是随机取的。