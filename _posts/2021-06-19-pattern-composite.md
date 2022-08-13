---
title: Composite模式-用统一的眼光看事物
author: Jie Chen
date: 2021-06-19
categories: [Design,Pattern]
tags: [csharp]
---

Composite模式，就是组合模式，是一个比较有意思的思维，它没有什么设计技巧，完全就是一种思想，就是怎么看待一组关联、或者有包容关系的事物。

一棵树，树干上有树叶，树干上有树枝，然后树枝上也有树叶，他们之间存在一定的包容关系。树干是树枝和树叶的容器，树枝是树叶的容器。

文件夹和文件的概念，也有包容和嵌套包容的关系。在Windows中我们用Folder（或者Directory）和File来区分。但是在Linux中，它们其实都是一个Node。在Linux眼里，他们其实是同一个东西，因为他们有相同的特点，但是又有点不一样。

所以，将一组有包容关联的事物，看做一个东西，同时又通过某种方法区别出他们的不通点，这个就是组合模式里要解决的问题。

抽象出文件夹和文件的相同的特点，定义成一个抽象的类，一些不一样的东西，放在抽象类里作为virtual方法让文件夹和文件去做各自有区别的处理。这个就是组合模式。

## 将文件夹和文件抽象成同一个Node

~~~
abstract class AbstractNode
{
  public string Name { get; protected set; } = string.Empty;
  
  public int Size { get; protected set; } = default;
  
  public virtual void Add(AbstractNode node)
  {
  }
  
  public virtual void Remove(AbstractNode node)
  {
  }
}
~~~

文件夹和文件统一定义成为AbstractNode，具体的相似点和区别在于：

* 他们都有 Name和Size的属性

* 文件夹具有Add和Remove其他Node的功能，定义成virtual，让有条件的Node去做。这里只有文件夹有这个功能

## 不同类型的Note的内部处理

对于文件而言，完全不需要做Add和Remove。

~~~
class File : AbstractNode
{
  public File(string name, int size)
  {
    Name = name;
    Size = size;
  }
}
~~~

而对于Folder而言，它可以也可以不实现Add和Remove，取决于业务。这里我们允许它添加其他子文件夹和文件。

~~~
class Folder : AbstractNode
{
  private IList<AbstractNode> children = new List<AbstractNode>();

  public Folder(string name)
  {
    Name = name;
  }

  public override void Add(AbstractNode node)
  {
    children.Add(node);
    Size += node.Size;
  }

  public override void Remove(AbstractNode node)
  {
    children.Remove(node);
    Size -= node.Size;
  }
}
~~~

## 组合起来的效果

假设我们有这样的文件夹层次结构：

~~~
folder3
  |___folder1
         |____ file1 ： size = 111
  |___folder2
         |____ file21 : size = 222
         |____ file22 : size = 555
~~~

用代码把这个文件夹层次表达出来，最后的结果：

~~~
AbstractNode file1 = new File(nameof(file1), 111);
AbstractNode folder1 = new Folder(nameof(folder1));
folder1.Add(file1);
Console.WriteLine($"folder1=" + folder1.Size);


AbstractNode file21 = new File(nameof(file21), 222);
AbstractNode file22 = new File(nameof(file22), 555);
AbstractNode folder2 = new Folder(nameof(folder2));
folder2.Add(file21);
folder2.Add(file22);
Console.WriteLine($"folder2=" + folder2.Size);

AbstractNode folder3 = new Folder(nameof(folder3));
folder3.Add(folder1);
folder3.Add(folder2);
Console.WriteLine($"folder3=" + folder3.Size);
~~~

结果：

~~~
folder1=111
folder2=777
folder3=888
~~~

## Composite模式的适应场景

Composite模式比较适合有层次、包容、嵌套等容器特性的一组事物，前提是他们有共同的能被抽象出来的共同点，一些不相通的属性或者功能可以通过子类来区别。