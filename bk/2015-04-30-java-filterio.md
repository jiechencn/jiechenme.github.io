---
title: Java IO - FilterInputStreadm/FilterOutStream使用场景
author: Jie Chen
date: 2015-04-30
categories: [Java]
tags: []
---

FilterInputStream和FilterOutputStream的使用场景比较少，但是提供了一个可扩展的流处理的可能性，它采用了装饰者设计模式，FilterInputStream是所有具体装饰者的父类，内部包裹一个InputSteam的原始类作为被装饰对象，通过具体的FilterInputStream的子类实现来调用InputStream的流处理功能，并在流基础上附加额外的功能。

比如前面提到的BufferedInputStream，它就是装饰者子类实现，文件流的读取依然调用父类FilterInputStream所包裹的InputStream，但额外提供了内部buf的缓存功能。

下面创建一个场景，程序需要通过HTTP访问远程的一个文件或者网页，最后需要返回这个文件或者网页的具体文件大小。最原始最简陋的设想就是通过InputStream的某个子类，顺序地读取远程文件，一边读取一边计数。但如果通过FilterInputStream的装饰者设计模式，代码就精巧了很多，同时也实现了类的可重用性。

首先创建一个装饰者的具体子类CountingInputStream。



	package cn.xwiz.lab.io;

	import java.io.FilterInputStream;
	import java.io.InputStream;

	public class CountingInputStream extends FilterInputStream {
	  private int count = 0;
	  protected CountingInputStream(InputStream inputStream) {
		super(inputStream);
	  }

	  public int read() throws java.io.IOException{
		int n = super.read();
		if (n!=-1) count += 1;
		return n;
	  }

	  public int read(byte[] bytes) throws java.io.IOException {
		int n = super.read(bytes);
		if (n!=-1) count += n;
		return n;
	  }

	  public int read(byte[] bytes, int i, int j) throws java.io.IOException {
		int n = super.read(bytes, i, j);
		if (n!=-1) count += 1;
		return n;
	  }
	  
	  public int getCount(){
		return count;
	  }
	}

上面的代码在具体read的多个重载方法中，用内部count计数器累计读取字节数，并通过公共方法getCount()返回。另外我们可以看到每个read方法都首先使用父类的read（父类的read具体实现其实依靠的是父类包装的InputStream）。看到这里很容易想到，单单使用FilterInputStream和使用FileInputStream完全没有差别。

最后调用类的使用和其他的InputStream包装器都类似。注意到下面的URL.openStream的实例其实就是一个InputStream类型的装饰对象。

	package cn.xwiz.lab.io;

	import java.net.MalformedURLException;
	import java.net.URL;

	public class MyCountingClient {
	  public static void main(String[] args) throws MalformedURLException {
		URL url = new URL("http://xwiz.cn");
		try(CountingInputStream cis = new CountingInputStream(url.openStream())) {
		  byte[] buffer = new byte[1024];
		  int read = 0;
		  while ((read = cis.read(buffer)) != -1) {
			// do something else to process buffer data ...
		  }
		  System.out.println("http page length: " + cis.getCount());

		} catch (Exception e) {
		  e.printStackTrace();
		}
	  }
	}


程序运行结果比如：

	http page length: 26708






