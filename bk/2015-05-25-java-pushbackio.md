---
title: Java IO - PushbackInputStream判断Class文件有效性
author: Jie Chen
date: 2015-05-25
categories: [Java]
tags: []
---

PushbackInputStream可以让程序试探性地读取字节流的前若干个字节来判断是否该流是自己期望的。如果是，可以处理后续流数据。如果不是，可以回退读取的字节，把流交给其他人。

这个例子演示一个简单的场景，读取Java Class文件，判断是否是合法的Class

* 魔数是否为合法的"CAFEBABE"
* Major Version是否是期望的版本

首先查阅Java规范，可以知道Class的文件结构为：

	ClassFile {
		u4 magic;
		u2 minor_version;
		u2 major_version;
		u2 constant_pool_count;
		cp_info constant_pool[constant_pool_count-1];
		u2 access_flags;
		u2 this_class;
		u2 super_class;
		u2 interfaces_count;
		u2 interfaces[interfaces_count];
		u2 fields_count;
		field_info fields[fields_count];
		u2 methods_count;
		method_info methods[methods_count];
		u2 attributes_count;
		attribute_info attributes[attributes_count];
	}

Class文件的头部4个字节的十六进制为固定的“CAFEBABE” (咖啡店宝贝)。其次，能否用当前JVM加载这个类，要判断第六个字节开始的2字节Major Version。根据定义， Major Version的版本为：

![](/assets/res/java-io-pushbackio-1.png)

需求明确后，可以使用PushbackInputStream一次性读取4个字节，获取魔数，再skip(2)，获取下2个字节判断Major Version。这样的方式虽然可行，但PushbackInputStream不支持unread（）2次。变通的方法是一次性读取8个字节转换成16进制的字符串，再截取字符串获取魔数和Major Version进行判断。

	final int len = 8;
    byte[] bs = new byte[len];
    try(PushbackInputStream pis = new PushbackInputStream(new FileInputStream("Employee.class"), len)){
      int r = pis.read(bs);
	  // 将字节数组转成16进制，并以String类型返回
      String hexValue1 = javax.xml.bind.DatatypeConverter.printHexBinary(bs);
      System.out.println(hexValue1);
	  /*
      pis.unread(bs);
      byte[] bs2 = new byte[len];
      pis.read(bs2);
      String hexValue2= javax.xml.bind.DatatypeConverter.printHexBinary(bs2);
      System.out.println(hexValue2);
	  */
    }catch(Exception e){
      //
    }
	
