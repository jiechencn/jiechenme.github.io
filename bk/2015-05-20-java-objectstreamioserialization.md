---
title: Java IO - ObjectOutputStream串行化二进制数据分析
author: Jie Chen
date: 2015-05-20
categories: [Java]
tags: []
---

ObjectOutputStream和ObjectInputStream配对使用来处理实现了Serializable接口的对象。Serializable，串行化，这种计算机专用术语太过于拗口，如果按照我自己的解释，说得最通俗一点就是把对象里面各个需要写出的数据按照事先设计好的的规则和格式排列，组成一个二进制的数据行，写出到目标地。目标地再按照一定的格式把数据解析出来，还原成原始的对象。

## 串行化二进制文件格式分析

Java对于如何设计这个规则，有专门的文档（<a href="https://docs.oracle.com/javase/8/docs/platform/serialization/spec/protocol.html" target="_blank" class="bodyA">Object Serialization Stream Protocol</a>）解释写出的数据的二进制各个标段的含义。

我可以通过一个简单的例子来看看Java到底是怎么写这个二进制的字节序列的。

	package cn.xwiz.lab.io;

	import java.io.Serializable;

	public class Employee implements Serializable{
	  private static final long serialVersionUID = 9L;
	  public int no;
	  public String email;
	  public float salary;
	  public boolean active;
	  public char sex;
	}

上面的类，定义了serialVersionUID为长整型的数值9。下面通过ObjectOutputStream来写出一个Employee实例对象的值。
	
	package cn.xwiz.lab.io;

	import java.io.*;

	public class ObjectInputStreamExample {
	  public static void main(String[] args){
		try (ObjectOutputStream oo = new ObjectOutputStream(new FileOutputStream("data2.bin"))){

		  Employee emp = new Employee();
		  emp.no = 123;
		  emp.email = "jie@xwiz.cn";
		  emp.salary = 3000.45F;
		  emp.active = true;
		  emp.sex = 'm';

		  oo.writeObject(emp);

		} catch (Exception e) {
		  e.printStackTrace();
		}


		try (ObjectInputStream oi = new ObjectInputStream(new FileInputStream("data2.bin"))){
		  Employee emp = (Employee) oi.readObject();

		  System.out.println(emp.no);
		  System.out.println(emp.email);
		  System.out.println(emp.salary);
		  System.out.println(emp.active);
		  System.out.println(emp.sex);

		}catch(Exception e){
		  e.printStackTrace();
		}
	  }
	}

在输出的二进制文件中，能大致看出里面的一些字面含义，但是其他不可见的字符需要参考Oracle的协议文档来理解了。

![](/assets/res/java-io-objectstreamioserialization-1.png)

根据协议规则，上图中的二进制解析为：

	STREAM_MAGIC (2 bytes) 0xACED 
	STREAM_VERSION (2 bytes) 0x0005
	newObject
		TC_OBJECT (1 byte) 0x73
		newClassDesc
			TC_CLASSDESC (1 byte) 0x72
			className
				length (2 bytes) 0x0017 = 23
				text (23 bytes) "cn.xwiz.lab.io.Employee"
			serialVersionUID (8 bytes) 0x0000000000000009 = 9L
			classDescInfo
				classDescFlags (1 byte) 0x02 = SC_SERIALIZABLE
				fields
					count (2 bytes) 0x0005
					field[0]
						primitiveDesc
							prim_typecode (1 byte) 0x005A Z = boolean
							fieldName
								length (2 bytes) 0x0006
								text (6 bytes) "active"
					field[1]
						primitiveDesc
							prim_typecode (1 byte) 0x0049 I = integer
							fieldName
								length (2 bytes) 0x0002
								text (2 bytes) "no"
					field[2]
						primitiveDesc
							prim_typecode (1 byte) 0x0046 F = float
							fieldName
								length (2 bytes) 0x0006
								text (6 bytes) "salary"
					field[3]
						primitiveDesc
							prim_typecode (1 byte) 0x0043 C = char
							fieldName
								length (2 bytes) 0x0003
								text (3 bytes) "sex"
					field[4]
						objectDesc
							obj_typecode (1 byte) 0x004C L = object
							fieldName
								length (2 bytes) 0x0005
								text (5 bytes)  "email"
							className1
								TC_STRING (1 byte) 0x74
									length (2 bytes) 0x12 = 18
									text (18 bytes) "Ljava/lang/String;"

				classAnnotation
					TC_ENDBLOCKDATA (1 byte) 0x78

				superClassDesc
					TC_NULL (1 byte) 0x70
		classdata[]
			classdata[0] (1 byte boolean) active=true
			classdata[1] (4 bytes int) no=123
			classdata[2] (4 bytes float) salary=3000.45F
			classdata[3] (2 bytes char) sex='m'
			classdata[4]
				TC_STRING (1 byte) 0x74
				length (2 bytes) 0x0008 
				text (8 bytes) email="jie@xwiz.cn"

每一个字段解析详细内容为：
				
* AC ED: short，2个字节， STREAM_MAGIC 
* 00 05: short，2个字节， STREAM_VERSION
* 73： byte，单字节，TC_OBJECT
* 72： byte，单字节，TC_CLASSDESC，后面会跟上类名
* 00 17： 用2个字节长度表示后面的是23个字符长的类名 cn.xwiz.lab.io.Employee
* 63 6E 2E 78 77 69 7A 2E 6C 61 62 2E 69 6F 2E 45 6D 70 6C 6F 79 65 65: 实现序列化的类名cn.xwiz.lab.io.Employee
* 00 00 00 00 00 00 00 09： 8个字节长整型long的serialVersionUID
* 02: 单字节的SC_SERIALIZABLE 标识
* 00 05: 用2个字节长度表示后面该序列化的对象有5个成员（active, no, salary, sex, email）
* 5A: 代表字母Z，标示当前有一个boolean的成员
* 00 06： 字符长度为6的成员名称（active）
* 61 63 74 69 76 65： 成员名称“active”
* 49： 字母I，表示当前有一个integer类型的成员
* 00 02： 长度为2的成员名称
* 6E 6F： 成员名称no
* 46： 字母F，标识float类型
* 00 06：　长度为6的成员
* 73 61 6C 61 72 79： float类型的成员名称salary，字符长6
* 43： 字母C，char类型
* 00 03： 长度3
* 73 65 78： 长度为3的成员名称sex
* 4C： 字母L，表示是一个object类型
* 00 05： object类型的成员名长度为5
* 65 6D 61 69 6C： object类型的成员名称email
* 74： TC_STRING，表示对象类型是个String
* 00 12： 该对象类型的类名长度为18
* 4C 6A 61 76 61 2F 6C 61 6E 67 2F 53 74 72 69 6E 67 3B： “Ljava/lang/String;”，千万不能忘记最后必须有一个分号
* 78： classAnnotation，因为没有用到Annotation，所以设TC_ENDBLOCKDATA
* 70： superClassDesc，因为没有父类，所以设标志位TC_NULL
* 01： boolean值true，active成员的值
* 00 00 00 7B： 4字节的整型，表示十进制的123，成员no的值
* 45 3B 87 33： 4字节的浮点值，十进制的3000.45，salary的值
* 00 6D: 2个字节的char，sex的值m
* 74： TC_STRING，表示后面为String
* 00 0B： 采用的是DataOutputStream.writeUTF写UTF-8字符的表示方法，2个字节的长度表示十进制的11，即后面会跟上11个字符
* 6A 69 65 40 78 77 69 7A 2E 63 6E： UTF-8表示法，即email的值： jie@xwiz.cn

## Java串行化的优缺点
Java串行化能将复杂的类对象中的数据处理成二进制数据序列，方便存储和网络传输。但是它有几个缺点，现在已经不是太适合流行的网络应用。

* 输出的二进制数据序列字节总数大小比实际需要的数据字节来的大，因为它额外加入了很多标志位和解释字段，这样会增大开销（文件存储开销或网络传输开销）。
* 输出的数据必须而且只能通过Java的ObjectInputStream来读出并解析，其他语言无法理解并还原数据。

Java串行化现在非常流行用JSON来处理，因为它数据字节小，人机都非常容易理解，因此适合跨语言通信。
