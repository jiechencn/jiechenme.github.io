---
title: ArrayList的动态数组扩容与收缩
author: Jie Chen
date: 2019-09-05
categories: [Java]
tags: []
---


最近在复习线性数据结构的时候，顺便拿了Java的ArrayList做了分析比较。ArrayList的本质是动态数组，当新加如的数据逼近数组容量时，自动扩容，删除数据后，自动收缩。这么做，虽然效率比较低，但是一定程度上能优化内存分配。

## 自动扩容

ArrayList内部有个elementData的Object数组，初始容量在Java 8中为10。当第11个数据需要添加时候，扩容1.5倍。这个1.5倍在ArrayList很巧妙，用了整数移位的方式。

~~~
int newCapacity = oldCapacity + (oldCapacity >> 1);
~~~

完整的扩容代码是：

~~~
private void grow(int minCapacity) {
	// overflow-conscious code
	int oldCapacity = elementData.length;
	int newCapacity = oldCapacity + (oldCapacity >> 1);
	if (newCapacity - minCapacity < 0)
		newCapacity = minCapacity;
	if (newCapacity - MAX_ARRAY_SIZE > 0)
		newCapacity = hugeCapacity(minCapacity);
	// minCapacity is usually close to size, so this is a win:
	elementData = Arrays.copyOf(elementData, newCapacity);
}
~~~

## 数组收缩

收缩数组的目的是为了节省内存，但是牺牲了时间。比如每一次的删除元素，都会触发一次System.arraycopy重写elementData，移动数组下标，再给无效的下标数据置空，隐式地等待gc清理。

~~~
public E remove(int index) {
	rangeCheck(index);
	modCount++;
	E oldValue = elementData(index);
	int numMoved = size - index - 1;
	if (numMoved > 0)
		System.arraycopy(elementData, index+1, elementData, index,
						 numMoved);
	elementData[--size] = null; // clear to let GC do its work
	return oldValue;
}
~~~

另外，ArrayList还提供了一个显式的收缩方法trimToSize，将elementData的容量大小（elementData.length）收缩为实际大小（ArrayList.size()）。

~~~
public void trimToSize() {
	modCount++;
	if (size < elementData.length) {
		elementData = Arrays.copyOf(elementData, size);
	}
}
~~~

## 内存分析

我用一段代码来查看ArrayList的实际size和capacity。由于capacity的大小就是elementData的数组大小，而elementData是内部数组，无法访问到，所以我设法采用reflection获取。

~~~
static int getCapacity(ArrayList<?> list) {
	try {
		Field dataField = ArrayList.class.getDeclaredField("elementData");
		dataField.setAccessible(true);
		return ((Object[]) dataField.get(list)).length;
	} catch (Exception e) {
		e.printStackTrace();
		return -1;
	}
}
~~~

而获取size直接用size()

这段代码的逻辑是：

1. 填充1000个数据
2. 获取size和capacity
3. 导出heap dump
4. trimToSize收缩
5. 获取capacity
6. 再次导出heap dump

~~~
ArrayList<String> list = new ArrayList<String>();

int ii=15;
ii = ii>> 1;
System.out.println(ii);
for(int i=0; i<1000; i++){
	list.add("hello-" + i);
}
System.out.println("size: " + list.size());
System.out.println("capacity: " + getCapacity(list));

try {
	Thread.sleep(1000*30);
} catch (InterruptedException e) {}

// capture heap dump 1

list.trimToSize();
System.out.println("capacity after trim: " + getCapacity(list));

System.gc();
try {
	Thread.sleep(1000*3600);
	// capture heap dump 2
} catch (InterruptedException e) {}
~~~

输出结果很简单：

~~~
size: 1000
capacity: 1234
capacity after trim: 1000
~~~

分析收缩前后的两次内存堆，发现elementData数组的大小差异为1234-1000=234，而shallow size和retained size都是936字节。

~~~
shallow size: 4952 - 4016 = 936
retained size: 68152 - 67216 = 936
~~~

![](/assets/res/java-arraylist-dynamic-1.jpg)

![](/assets/res/java-arraylist-dynamic-2.jpg)

这936字节的差异就是数组中扩容后产生的234个Object空引用。一个对象引用是4字节，shallow和retained size需要8字节对齐，所以：

~~~
234 * 4 = 936
936 % 8 = 0，所以不需要填充对齐
~~~
	
	

