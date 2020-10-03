---
title: Java IO - ByteArray流的重用
author: Jie Chen
date: 2015-05-07
categories: [Java]
tags: []
---

ByteArrayInputSteam类将内存中的字节数组作为数据源，利用这个特性，可以通过ByteArrayInputSteam重用一些需要特殊处理、或者重复使用的字节数组或大字符串，比如XML格式的内容。

一个有用的场景是对于异常的特殊处理。一般而言，我们简单地通过 Exception.printStackTrace()方法直接输出异常栈到标准输出stdout。
	
## 异常的一般性处理

    try {
      int x = 1 / 0;  // <--- 
    } catch (Exception e) {
      e.printStackTrace();
    }
	
这个简单例子演示被除数为0的异常输出。下面这个输出比较特殊，因为例子代码是在intellij编辑器中执行，所以栈中有调用者intellij的信息。

	java.lang.ArithmeticException: / by zero
		at cn.xwiz.lab.io.ByteArrayClient.main(ByteArrayClient.java:11)
		at sun.reflect.NativeMethodAccessorImpl.invoke0(Native Method)
		at sun.reflect.NativeMethodAccessorImpl.invoke(NativeMethodAccessorImpl.java:62)
		at sun.reflect.DelegatingMethodAccessorImpl.invoke(DelegatingMethodAccessorImpl.java:43)
		at java.lang.reflect.Method.invoke(Method.java:498)
		at com.intellij.rt.execution.application.AppMain.main(AppMain.java:120)
	
上面输出7行数据，大量的异常输出会导致日志文件越来越庞大，一个有效的做法是压缩异常的输出量，比如只输出异常栈的前面若干行。

## Throwable特殊处理

可以将Throwable对象通过PrintStream或者PrintWriter写入到ByteArrayOutputStream中。ByteArrayOutputStream内部保存了一个字节数组，可以作为ByteArrayInputStream的数据源。 接下来通过ByteArrayInputStream作为BufferedReader的输入流，处理每一行数据。


	public static void main(String args[]) {
		try {
		  int x = 1 / 0;
		} catch (Exception e) {
		  //e.printStackTrace();
		  printMyTrace(e);
		}
	}

	public static void printMyTrace(Throwable t) {
		final int line = 3;
		try {
		  ByteArrayOutputStream bos = new ByteArrayOutputStream();
		  PrintStream ps = new PrintStream(bos);
		  t.printStackTrace(ps);

		  ByteArrayInputStream bis = new ByteArrayInputStream(bos.toByteArray());
		  BufferedReader br = new BufferedReader(new InputStreamReader(bis));
		  for (int i = 0; i < line; i++) {
			String s = br.readLine().toString();
			System.err.println(s);
		  }
		} catch (Exception e) {
		  // do nothing
		}
	}

这里涉及到ByteArrayInputStream的重用性，虽然例子中只用了一次并作为stderr输出，但在方法体printMyTrace内可以重用ByteArrayInputStream的实例bis，做其他的处理，比如全部写入一个邮件内发送，或写入stdout，或使用另外一种形式的过滤，比如过滤每一行只输出含有cn.xwiz包的信息。

上面的代码输出结果就比较简洁。

	java.lang.ArithmeticException: / by zero
		at cn.xwiz.lab.io.ByteArrayClient.main(ByteArrayClient.java:11)
		at sun.reflect.NativeMethodAccessorImpl.invoke0(Native Method)

