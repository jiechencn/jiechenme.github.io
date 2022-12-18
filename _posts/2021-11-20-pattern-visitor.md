---
title: Visitor模式-分离数据结构和数据处理
author: Jie Chen
date: 2021-11-20
categories: [Design,Pattern]
tags: [csharp]
---

首先了解一个开闭原则，对扩展开放，对修改关闭。也就是永远考虑扩展的可能，尽量不要修改类的原始定义。

由此想到一种常见的情形。 DTO （Data Transfer Object）是用来在不同的处理层交换数据的最简单的类对象，它可以被序列化。在某些情况下，我们可能在这个类的基础上，想要进行额外的复杂的处理，由于DTO的使用遵循最简单的原理，不推荐在DTO中直接添加额外方法，所以复杂操作可以这么处理：创建一个新的类，接收DTO作为参数，在新的类里处理。有一个不好的地方是，如果有多种不同种类的复杂操作，需要添加不同的方法。

如果采用Visitor模式，就能比较好地处理这种情况，虽然Visitor模式使用起来需要一点挑战，就是要对DTO未来的数据处理有前瞻性的预判。

Visitor模式的遵循的原理是：把数据结构的定义和数据的处理分离开来。数据结构预先添加一个 Accept(IVisitor)的方法，方法体内调用 IVisitor.Visit(this)，让Visitor直接访问自己.

假设有这样的例子，有File和Folder两种数据结构的DTO，在交付使用后，觉得有必要添加额外的复杂功能，处理File和Folder内的数据。在 Composite模式的基础上，增加一个接口：

~~~
public interface IBehavior
{
	public void Accept(INodeVisitor visitor);
	public IList<AbstractNode> Iterator();
}
~~~

AbstractNode抽象类提供抽象方法实现接口方法。其中Iterator的目的是为了能迭代Folder中的每个File或者 sub-folder的元素，让元素也能Accept(IVisitor)。

~~~
public abstract class AbstractNode: IBehavior
{
	public string Name { get; protected set; } = string.Empty;
	public int Size { get; protected set; } = default;

	public virtual void Add(AbstractNode node)
	{
	}

	public virtual void Remove(AbstractNode node)
	{
	}

	public abstract void Accept(INodeVisitor visitor);
	public abstract IList<AbstractNode> Iterator();
}

~~~

FileNode和FolderNode与Compositi模式中的定义一摸一样，只是额外增加了2个接口方法的实现：Accept和Iterator。

~~~
public class FileNode : AbstractNode
{
	public FileNode(string name, int size)
	{
		Name = name;
		Size = size;
	}

	public override void Accept(INodeVisitor visitor)
	{
		visitor.Visit(this);
	}

	public override IList<AbstractNode> Iterator()
	{
		throw new NotImplementedException();
	}
}

public class FolderNode : AbstractNode
{
	private IList<AbstractNode> children = new List<AbstractNode>();

	public FolderNode(string name)
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

	public override void Accept(INodeVisitor visitor)
	{
		visitor.Visit(this);
	}

	public override IList<AbstractNode> Iterator()
	{
		return children.ToList();
	}
}
~~~

然后定义IVisitor，分别访问FolderNode和FileNode，进行各自复杂的数据处理。

~~~
public interface INodeVisitor
{
	public void Visit(FolderNode folder);

	public void Visit(FileNode file);
}


public class NodeVisitor : INodeVisitor
{
	public void Visit(FolderNode folder)
	{
		Console.WriteLine($"{folder.Name} : {folder.Size}");
		foreach (var node in folder.Iterator())
		{
			// 迭代调用元素的 Accept方法，传入当前的 Visitor实例
			node.Accept(this);
		}
	}

	public void Visit(FileNode file)
	{
		Console.WriteLine($"{file.Name} : {file.Size}");
	}
}
~~~

客户端

~~~
FolderNode dir = new FolderNode("dir");
FolderNode dir1 = new FolderNode("dir1");
dir1.Add(new FileNode("file1", 111));
FolderNode dir2 = new FolderNode("dir2");
dir2.Add(new FileNode("file2", 222));
FileNode file3 = new FileNode ("file3", 300);
dir.Add(dir1);
dir.Add(dir2);
dir.Add(file3);

INodeVisitor visitor = new NodeVisitor();

dir.Accept(visitor);
Console.WriteLine();

dir1.Accept(visitor);
Console.WriteLine();

dir2.Accept(visitor);
Console.WriteLine();

file3.Accept(visitor);
~~~

输出

~~~
dir : 633
dir1 : 111
file1 : 111
dir2 : 222
file2 : 222
file3 : 300

dir1 : 111
file1 : 111

dir2 : 222
file2 : 222

file3 : 300
~~~

