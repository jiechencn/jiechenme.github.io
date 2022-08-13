---
title: Strategy模式-选择不同的被委托者实现策略的改变
author: Jie Chen
date: 2021-06-10
categories: [Design,Pattern]
tags: [csharp]
---

Strategy模式的定义，就是委托者想要实现某种功能时，把具体的算法丢给被委托者去做，在一定时候，委托者还可以选择其他的被委托者实现不一样的效果。也就是说，每一个被委托者，实现特定的算法。至于这个算法是如何实现的，委托者不关心，它只负责交代任务给被委托者。 这种转嫁任务并可以选择不同被委托者的模式，就是策略模式。

可以自由选择不同的被委托者，从这句话的概念里肯定知道，委托者对象内部，一定会持有一个通用的被委托者的一个抽象、或者一个接口。

假设现在我有一个整数数组，我需要从中获取 Top N的一组数字。这个 Top N可以是最大的一组，也可以是最小的一组。具体是选择最大的还是最小的一组，由委托者去决定，被委托者去做实现。

## 策略的实现者：被委托者

选择Top N，就是一个排序的过程。所以不同的排序方法就对应一个策略。

~~~
public interface ISortStrategy
{
  public void Sort(ref IEnumerable<int> datas);
}

public class AscSort : ISortStrategy
{
  public void Sort(ref IEnumerable<int> datas)
  {
    datas = datas.OrderBy(d => d);
  }
}

public class DescSort : ISortStrategy
{
  public void Sort(ref IEnumerable<int> datas)
  {
    datas = datas.OrderByDescending(d => d);
  }
}
~~~

所以，定义了一个排序策略的接口，和两个具体的策略：正序排列和倒序排列。



## 策略的决定方：委托者

DataProcessor实例内部持有一个ISortStrategy的对象，通过构造器传入。然后通过GetTopN 得到Top N的一组数字。至于是最大的还是最小的一组，就是通过构造函数传入的ISortStrategy的一个实例来实现。

~~~
public class DataProcessor
{
  private ISortStrategy sort;
  private IEnumerable<int> datas;

  public DataProcessor(IEnumerable<int> datas, ISortStrategy sort)
  {
    this.datas = datas;
    this.sort = sort;
  }

  public IEnumerable<int> GetTopN(int n)
  {
    sort.Sort(ref datas);
    return datas.Take(n);
  }
}
~~~

## 策略的执行

现在通过客户端调用来实现策略的选择和执行。

先准备一组数据。

~~~
List<int> ints = new List<int>();
ints.Add(1);
ints.Add(3);
ints.Add(2);
ints.Add(5);
ints.Add(4);
ints.Add(6);
~~~

然后选择一个正序排列的策略。获取最小的一组排序数字。

~~~
DataProcessor dp = new DataProcessor(ints, new AscSort());
var topN1 = dp.GetTopN(3);

topN1.ToList().ForEach(d =>
{
  Console.Write(d + ","); // 1,2,3
});
~~~

再选择另一个倒序排列的策略。获取最大的一组排序数字。

~~~
dp = new DataProcessor(ints, new DescSort());
var topN2 = dp.GetTopN(3);

topN2.ToList().ForEach(d =>
{
  Console.Write(d + ","); // 6,5,4
});
~~~

## C#语言中的策略模式

策略模式其实是一种最简单的委托模式。在C#中大量使用了这种策略模式，也就是delegate action。

比如Netcore runtime里，List.Sort（Comparision<T> comparision） 就是一种策略模式。但是在C#语言中一般不说策略，直接说成delegate委托，但其实它就是设计模式中的策略。

~~~
public void Sort(Comparison<T> comparison)
{
  if (comparison == null)
  {
    ThrowHelper.ThrowArgumentNullException(ExceptionArgument.comparison);
  }
  if (_size > 1)
  {
    ArraySortHelper<T>.Sort(new Span<T>(_items, 0, _size), comparison);
  }
  _version++;
}
~~~