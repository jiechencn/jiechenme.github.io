---
title: Iterator模式 - 集合的遍历
author: Jie Chen
date: 2021-11-03
categories: [Design,Pattern]
tags: [csharp]
---

Iterator模式存在的意义在于给调用者按照顺序依次访问集合/容器内的元素的，他可以实现：

* 调用者负责往集合或者容器内塞东西，不用关心这个容器的具体实现
* 调用者可以从容器内获取东西
* 调用者可以按照顺序获取容器内的全部东西

对调用者而言，它只关心面向它的那个容器，和容器提供给他的一个迭代器。这样的好处是，容器可以修改它内部的实现，不用通知迭代器，更不用通知调用者。迭代器又是一个统一的接口，调用者只需要从迭代器里不停地次拿东西就好了。

一个集合可以是一个抽象类，不同的子类可以实现它自己的集合的算法来存储保存进来的元素。所以，假设我们可以定义这样的抽象集合。

# 抽象集合

~~~
public abstract class AbstractCollection
{
    public abstract AbstractIterator CreateIterator();
    public abstract void Add(object data);
    public abstract int Length();
    public abstract object Get(int index);
}
~~~

这个抽象集合是调用者直接接触的对象，它提供了数据的存储、单个元素的获取和长度，最重要的，它提供了一个迭代器的输出 AbstractIterator 。这个迭代器就是调用者也要直接接触的，用来顺序访问集合内的元素。

# 抽象迭代器

~~~
public abstract class AbstractIterator
{
    protected AbstractCollection collection;
    protected int index;
    public AbstractIterator(AbstractCollection collection)
    {
        this.collection = collection;
        this.index = -1;
    }

    public abstract bool HasNext();
    public abstract object Next();
}
~~~

AbstractIterator 将AbstractCollection集合本身作为自己的内部变量，通过HasNext 和 Next 来从这个集合中判断元素和获取元素。 HasNext 和 Next方法是调用者直接使用的。

# 具体的集合和迭代器

框架搭好了，接下来就是具体的实现了。

创建一个普通的集合类，这里的集合算法，就是如何存储元素，用List偷懒的方式模拟一下。 CreateIterator返回一个具体的 NormalIterator 迭代器。

~~~
public class NormalCollection : AbstractCollection
{
    private List<object> datas = new List<object>();

    public override AbstractIterator CreateIterator()
    {
        return new NormalIterator(this);
    }

    public override void Add(object data)
    {
        datas.Add(data);
    }

    public override int Length()
    {
        return datas.Count;
    }

    public override object Get(int index)
    {
        return datas[index];
    }
}
~~~

NormalIterator 也很简单，继承父类完成方法即可。

~~~
public class NormalIterator : AbstractIterator
{
    public NormalIterator(AbstractCollection normalCollection)
        : base(normalCollection)
    {
        index = 0;
    }

    public override bool HasNext()
    {
        return index < collection.Length();
    }

    public override object Next()
    {
        object data = collection.Get(index);
        index++;
        return data;
    }
}
~~~

# 调用集合和集合的迭代器

这样，就完成了一个迭代模式的设计。看看调用者怎么使用呢：

~~~
AbstractCollection collection = new NormalCollection();
for (int i = 0; i < 10; i++)
{
    collection.Add($"data-{i}");
}

AbstractIterator iterator = collection.CreateIterator();
while(iterator.HasNext())
{
    var data = iterator.Next();
    Console.WriteLine(data);
}
~~~

很容易看出，它就是顺序访问里面的元素然后输出。

这个调用者，它只面对一个集合，和一个抽象迭代器，非常简单，其他的都交给集合和迭代器去完成查询。

# 如何扩展

看一下如何扩展这个迭代模式，有几种情况：

1. 改变集合的存储结构

保持所有的方法不变，只需要改变NormalCollection集合的存储结构

2. 改变迭代器的工作方式

保持所有的方法不变，只需要改变NormalIterator 的Next()内部的算法

3. 添加新的的集合和迭代器

比如我现在觉得NormalCollection和NormalIterator无法满足我的需求，我要实现一个新的集合，它提供反向的遍历。很简单，照着现有的集合和迭代器，依葫芦画瓢。

一个新的集合（假设我内部使用Dictionary代表新的集合存储的算法）

~~~
public class ReverseCollection: AbstractCollection
{
    private Dictionary<int, object> datas = new Dictionary<int, object>();

    public override AbstractIterator CreateIterator()
    {
        return new ReverseIterator(this);
    }

    public override void Add(object data)
    {
        datas.Add(datas.Count, data);
    }

    public override int Length()
    {
        return datas.Count;
    }

    public override object Get(int index)
    {
        return datas[index];
    }
}
~~~

创建一个反向的迭代算法，具体的就是Next（）方法内部。

~~~
public class ReverseIterator : AbstractIterator
{
    public ReverseIterator(ReverseCollection collection)
        :base(collection)
    {
        index = collection.Length() - 1;
    }

    public override bool HasNext()
    {
        return index > 0;
    }

    public override object Next()
    {
        object data = collection.Get(index);
        index--;
        return data;
    }
}
~~~

调用者使用也是差不多：

~~~
AbstractCollection collection2 = new ReverseCollection();
for (int i = 0; i < 10; i++)
{
    collection2.Add($"data-{i}");
}

AbstractIterator iterator2 = collection2.CreateIterator();
while (iterator2.HasNext())
{
    var data = iterator2.Next();
    Console.WriteLine(data);
}
~~~


最后要说的是，我们现在几乎不会重新设计一个迭代器实现某个集合或者容器了，因为所有的语言和框架都提供了现成的SDK。但是上面可以帮助我们可以了解迭代器模式它是怎么设计的。