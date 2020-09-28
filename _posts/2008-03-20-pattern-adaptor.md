---
title: 类复用-adaptor-修复现有API和需求的差异
author: Jie Chen
date: 2008-03-20
categories: [Design]
tags: [java,pattern]
---

Java IO的SDK中用了大量的adaptor模式来解决不同格式不同方式的文件的读写。它能为客户掩盖掉底层实现的细节。在实际的开发中，它应用于这种情形：

* 现有的SDK/API只是一部分满足自己的需求

所以，不满足的部分只能通过适配的方法来弥补。

比如我有一个220V的电源插座，现在要为不同的设备生产不同的插头，比如手机和笔记本。220V是属于需要被改造适配的adaptee，5V的手机插头和12V的笔记本插头是adaptor。有两种适配器的设计方式。

## 类继承的适配器方式

旧的API无法适应新的设备

~~~
public class Power220V {
    public void use(){
        System.out.println("Using power 220V...");
    }
}
~~~

定义一个通用的插座规格的接口

~~~
public interface Plug {
    public void plug();
}
~~~

笔记本插头继承220V，并按照插座规格实现接口设计，改造这个电压。

~~~
public class LaptopPlug extends Power220V implements Plug {
    @Override
    public void plug() {
        super.use();
        // add additional specific handle for laptop
        System.out.println("this is for laptop");
    }
}
~~~

同样，手机插头也是按照这个规则设计

~~~
public class PhonePlug extends Power220V implements Plug {
    @Override
    public void plug() {
        super.use();
        // add additional specific handle for phone
        System.out.println("this is for phone");
    }
}
~~~

对于客户调用者而言，它完全不知道底层220V的细节，它只面对它的插头。

~~~
Plug phonePlug = new PhonePlug();
Plug laptopPlug = new LaptopPlug();
phonePlug.plug();
laptopPlug.plug();
~~~

## 对象委托的适配器方式

上面的这个适配器模式使用了类继承的方式。还有一种方式是通过对象委托。插头规格不再定义接口，而是本身就是个抽象类。在类成员中，引用220V的一个实例。

~~~
public abstract class Plug2 {
    Power220V p220 = new Power220V();
    public abstract void plug();
}
~~~

在子类插头中，调用这个类成员获取220V的输出电压，然后进行改造。

~~~
public class LaptopPlug2 extends Plug2 {
    @Override
    public void plug() {
        p220.use();
        // add additional specific handle for laptop
        System.out.println("this is for laptop 2");
    }
}
~~~

~~~
public class PhonePlug2 extends Plug2 {
    @Override
    public void plug() {
        p220.use();
        // add additional specific handle for phone
        System.out.println("this is for phone 2");
    }
}
~~~