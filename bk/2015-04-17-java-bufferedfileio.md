---
title: Java IO - 带Buffer的文件二进制读写及问题
author: Jie Chen
date: 2015-04-17
categories: [Java]
tags: []
---

一般性文件的顺序读写提供的函数或者一个字节/字符地读写，或者以字节/字符数组的方式一次性读入一定数量的数据进入数组。后者其实相当于一个缓存。但JDK又额外提供了BufferedInputStream和BufferedOutputStream。他们的处理方式非常简单。

看个例子

    String srcFile = "./test/qq.exe";
    String destFile = "/test/qq2.exe";

    try (BufferedInputStream bis = new BufferedInputStream(new FileInputStream(srcFile),8192*100);
         FileOutputStream fo = new FileOutputStream(destFile);
         BufferedOutputStream bos = new BufferedOutputStream(fo, 8192*100);
    ){
      byte[] buffer = new byte[8192];
      int read = 0;
      while ((read = bis.read(buffer))!=-1){
        bos.write(buffer, 0, read);
      }
    } catch (Exception e) {
      e.printStackTrace();
    }
	
设计的逻辑是：BufferedInputStream或者BufferedOutputStream它包装了一个输入/输出流（比如例子中的FileInputStream和FileOutputStream），内部提供了一个buf数组，数组大小默认一定数量（在JDK8中是8192），也可以在构造函数中人为地设定自己想要的buf大小。这个buf和后面我自己定义的buffer完全不是同一个概念。

假设我设置BufferedInputStream内部缓存数组大小为8192x100，程序每次希望处理8192个字节，BufferedInputStream在读取数据时，一次IO操作就尽量预读取buf数组大小的数据，即8192x100个字节的数据，暂存入buf中，通过read返还时，每次返还8192个自己，第二次while循环中再read下一个8192字节时，不再从原始流/原始文件中直接读取，而是从内部buf中获取下一批的8192个字节数据。这样就能避免99次的额外IO开销。

再使用BufferedOutputStream写入数据的时候，我设置它内部缓存大小为8192x100,但每次调用write的时候，只写入8192个自己。BufferedOutputStream将8192个字节暂存入内部buf，并等待下一个循环的write再写入8192个字节，等到内部buf被塞满8192x100字节时，内部再调用flush，告诉磁盘可以实际写文件了，一次性写入8192x100字节数据，这个时候才真正地发生一次IO，也就是避免了99次IO。

通过上述的逻辑，BufferedInputStream和BufferedOutputStream尽量将数据的读和写的准备工作在内存中完成，迫不得已才发生一次IO，因为IO（比如磁盘读写）比内存处理来的非常慢。

## 缓存的争议性问题

实际上，一直存在着针对这两个方法的讨论。包括我自己，一直面临这3个有争议的地方，始终无法从官方得到合理的解释。

### 多大的缓存大小才合适

内部buf的大小和磁盘一次IO能读取多少字节数据息息相关。假设磁盘IO一次可以读1024字节，那我的内部buf大小势必要比1024大一点，并且最好是1024x8的倍数，就假设是1024x16，BufferedInputStream每次读取这么多数据发生实际的IO的次数并不是1，而是16。但是Java的可移植性引起一个蹊跷的疑问就是我怎么知道当前运行的机器的磁盘读写速度是多少呢？我设置buf的大小是磁盘速度的2倍和8倍，实际上是没有任何区别的。不管你Java的上层数据流的包装如何精巧，数据的读写任务最终还是要交给磁盘来做，并且受限于磁盘的速度。

### 内部buf大小和外部byte数组大小

实际上，我在构造函数中指定了内部buf的大小，即：

	new BufferedInputStream(new FileInputStream(srcFile),8192*100)

而我每次处理的数组大小为：

	byte[] buffer = new byte[8192];

理论上，BufferedInputStream需要循环第101次时才会让被包装的FileInputStream去磁盘那里读取下一批8192x100的数据。现在的问题时，我每次处理的数组大小多少才是合适的？我可以和内部buf一样大吗？完全可以，比如：

	byte[] buffer = new byte[8192*100];

这和上面的定义有区别吗？除了while循环体内的专门针对buffer这个byte数组进行具体的操作之外（即byte处理次数有差异），对磁盘的IO或者说buffer的优势没有任何差异。


### 基本Input/Output也自带Buffer

第三个有争议的地方是InputStream和OutputStream也自带buffer。比如它的子类FileInputStream有个read重载方法：

    public int read(byte b[]) throws IOException {
        return readBytes(b, 0, b.length);
    }

子类FileOutputStream的write重载方法：

    public void write(byte b[]) throws IOException {
        writeBytes(b, 0, b.length, append);
    }
	public void write(byte b[], int off, int len) throws IOException {
        writeBytes(b, off, len, append);
    }

这两个方法，实际上是调用操作系统的原生IO操作，使用了同样的buffer（byte数组）。如果我把byte数组设的和Buffered IO的内部buf大小一样大，那BufferedInputStream和BufferedOutputStream的优势体现在哪里呢？


欢迎探讨。


