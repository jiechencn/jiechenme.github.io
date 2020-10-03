---
title: Java IO - 基本文件的顺序读写
author: Jie Chen
date: 2015-04-12
categories: [Java]
tags: []
---

一般性文件的顺序读写有2种方式：基于二进制；基于字符。无论哪种方法都非常简单。但有一些细节比较重要。

## 顺序读写

下面的例子是最常见的读写顺序文件的方式，以FileInputStream/FileOutputStream或者FileReader/FileWriter来处理。

### FileInputStream/FileOutputStream
	
	String filename1 = "./test/11.txt";
    String filename2 = "./test/12.txt";
    String filename3 = "./test/13.txt";
    String filename4 = "./test/14.txt";

    try (FileInputStream fi = new FileInputStream(filename1);
         FileOutputStream fo = new FileOutputStream(filename2)) {
      byte[] bs = new byte[8];
      int n = -1;
      while ((n = fi.read(bs)) != -1) {
        fo.write(bs, 0, n);
      }

    } catch (Exception e) {
      e.printStackTrace();
    }
	
### FileReader/FileWriter
	
	try (FileReader fr = new FileReader(filename1);
         FileWriter fw = new FileWriter(filename3)) {
      char[] cs = new char[8];
      int n = -1;
      while ((n = fr.read(cs)) != -1) {
        fw.write(cs, 0, n);
      }
    } catch (Exception e) {
      e.printStackTrace();
    }
	
	
		
## 文件编码问题

使用FileInputStream和FileOutputStream因为是以二进制处理字节的方式，所以文件的读写都是ANSI编码。而FileReader和FileWrite的文件读写编码取决于当前JVM设定的字符编码设定file.encoding。比如：
	
	-Dfile.encoding=UTF-8
	
因此假设当前JVM设定为UTF-8，而需要读取的文件是GB2312存在，FileReader则是以UTF-8编码读取，同时以FileWrite写出。因此采用后面基于字符的读写会产生中文乱码。解决的方法是使用FileReader/FileWriter的父类InputStreamReader和OutputStreamReader，在初始化实例方法中指定编码。比如：
	
    String encode = "GB2312";
    try (InputStreamReader isr = new InputStreamReader(new FileInputStream(filename1), Charset.forName(encode));
         OutputStreamWriter osw = new OutputStreamWriter(new FileOutputStream(filename4), Charset.forName(encode))) {
      char[] cs = new char[8];
      int n = -1;
      while ((n = isr.read(cs)) != -1) {
        osw.write(cs, 0, n);
      }
    } catch (Exception e) {
      e.printStackTrace();
    }
	
	
运行这段代码前，可以在JVM参数中指定-Dfile.encoding=UTF-8。执行结果可以看到生成的14.txt正确地包含了和源文件11.txt相同的GB2312编码，不受JVM默认编码的影响。
  
## 数组问题

上面三种读写文件的例子，都使用到了诸如 write(bs, 0, n) 的写文件方法。类的其他重载函数也允许存在下面的方式写文件。

	write(bs);

但是最佳的方式应该是使用 write(bs, 0, n) 。通过下面的例子可以看出write(bs)的问题。
	
	String filename = "./test/11.txt";
    String filename2 = "./test/12.txt";

    try(FileInputStream fi = new FileInputStream(filename1);
        FileOutputStream fo = new FileOutputStream(filename2)){
      byte[] bs = new byte[8];
      int n = -1;
      while ((n=fi.read(bs))!=-1){
        fo.write(bs);
		String s = new String(bs);
        System.out.println(s);
      }

    }catch(Exception e){
      e.printStackTrace();
    }


假设11.txt文件内容为26个英文字母

	abcdefghijklmnopqrstuvwxyz

期望的执行产生的12.txt文件也必须和11.txt完全一致。同时代码打印输出期望为

	abcdefgh
	ijklmnop
	qrstuvwx
	yz

实际执行后12.txt内容为

	abcdefghijklmnopqrstuvwxyzstuvwx

打印输出为

	abcdefgh
	ijklmnop
	qrstuvwx
	yzstuvwx

原因是数组byte[]在while循环中多次重用，文件流写入byte[]数组时，并不是覆盖整个数组，而是以一个下标一个下标的方式填入的。所以在最后一个循环内，read返回2，但数组内填入的是最后2个字母再加上上一次循环的下标为2~7的字符，即 yzstuvwx。
