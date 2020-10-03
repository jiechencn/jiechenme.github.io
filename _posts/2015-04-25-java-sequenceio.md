---
title: Java IO - 用SequenceInputStream合并文件
author: Jie Chen
date: 2015-04-25
categories: [Java]
tags: []
---

通过SequenceInputStream可以连接多个InputStream对象，按照排列顺序依次读取各个InputStream中的数据。这个特点很容易想到一个非常常见的场景，就是文件合并。下面的例子简单演示一个文件的拆分和合并的过程。

## 文件拆分

split函数根据指定的size参数，将文件拆分成每个size大小的小文件。按照 .1、 .2、 .3的顺序命名各个文件。

	public static void split(String filename, int size){
		try{
		  FileInputStream in = new FileInputStream(filename);
		  byte[] bs = new byte[size];
		  int n = -1;

		  int x = 1;
		  while ((n = in.read(bs)) != -1) {
			FileOutputStream out = new FileOutputStream(filename + "." + (x++));
			out.write(bs, 0, n);
			out.close();
		  }

		  in.close();

		}catch(Exception e){
		  e.printStackTrace();
		}
	}

## 文件合并

将多个小文件通过FileInputStream创建输入流，并入一个集合，然后通过SequenceInputStream的构造函数传入该集合。SequenceInputStream会按照先后顺序遍历集合中的输入流，依次读取。一旦读完第一个，将自动马上关闭第一个流对象，并开始读取第二个，以此类推。等到最后关闭SequenceInputStream自身时，close函数将再次自动确认最后的输入流对象已被关闭。因此没有必要显式地关闭集合中的每一个输入流。

	public static void merge(Vector<String> files, String newFile){
		try {
		  Vector<InputStream> streams = new Vector<>();
		  for (String f: files){
			InputStream in = new FileInputStream(f);
			streams.add(in);
		  }

		  SequenceInputStream seqInput = new SequenceInputStream(streams.elements());
		  FileOutputStream output = new FileOutputStream(newFile);

		  byte[] bs = new byte[2048];
		  int n = -1;

		  while ((n = seqInput.read(bs)) != -1) {
			output.write(bs, 0, n);
		  }

		  seqInput.close();
		  output.close();

		} catch (Exception e) {
		  e.printStackTrace();
		}
	}

	
## 调用


    split("./QQ.exe", 1024*1024*10); // 10M each file

    Vector<String> files = new Vector<>();
    files.add("./QQ.exe.1");
    files.add("./QQ.exe.2");
    files.add("./QQ.exe.3");
    files.add("./QQ.exe.4");
    files.add("./QQ.exe.5");
    files.add("./QQ.exe.6");
    merge(files, "./QQ2.exe");

