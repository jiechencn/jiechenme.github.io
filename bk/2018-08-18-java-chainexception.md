---
title: Java异常链与故障诊断
author: Jie Chen
date: 2018-08-18
categories: [Java]
tags: []
---

产品的故障诊断，都需要依靠日志文件中的异常栈来判断。很多时候，我不得不需要客户提供真实的数据库，一点点去调试。而客户提供的日志文件，很多时候都没有参考价值。原因就在于我们的代码中，Exception捕获的处理方式太随意，不注重后续跟踪。

Exception处理很容易被程序员忽略，大家都不注重，以为只要抛出来就对了，因为觉得它太简单了，too simple 所以 nobody cares。我把下面例子一讲，就会明白，道理如此简单，作用如此重要。

## 错误模拟

自定义一个AppException，再派生两个子类。

	package cn.xwiz.test.exception;

	public class AppException extends Exception{
		public AppException(String message){
			super(message);
		}
		public AppException (String message, Throwable t){
			super(message, t);
		}
	}

AppException的子类MyMathException：

	public class MyMathException extends AppException{
		public MyMathException(String message){
			super(message);
		}
		public MyMathException (String message, Throwable t){
			super(message, t);
		}
	}

和MyNullException

	public class MyNullException extends AppException{
		public MyNullException(String message){
			super(message);
		}
		public MyNullException (String message, Throwable t){
			super(message, t);
		}
	}


主程序做的事情是：

1. 接收用户输入的三个字符串
2. 判断是否为空，如果空，抛出MyNullException
3. 分别转换成Long类型，可能抛出NumberFormatException
4. 将三个Long实例相加求和，将NumberFormatException包装成MyMathException
  
代码如：   

	package cn.xwiz.test.exception;

	public class Client {
		public static void main(String args[]) {
			try {
				process();
			} catch (AppException apex) {
				apex.printStackTrace();
			}
		}

		private static void process() throws AppException {
			try {
				String s1 = "123";
				String s2 = "abc";
				String s3 = "xyz";
				validateUserInput(s1);
				validateUserInput(s2);
				validateUserInput(s3);
				calculate(s1, s2, s3);
			} catch (AppException ae) {
				throw new AppException("wrong in process");
			}
		}

		private static void validateUserInput(String s) throws MyNullException {
			if (s == null )
				throw new MyNullException("wrong in validateUserInput");
		}

		private static Long calculate(String s1, String s2, String s3) throws MyMathException {
			try {
				Long num1 = parseLong(s1);
				Long num2 = parseLong(s2);
				Long num3 = parseLong(s3);
				return num1 + num2 + num3;
			} catch (NumberFormatException nfe) {
				throw new MyMathException("wrong in calculate");
			}
		}

		private static Long parseLong(String s) throws NumberFormatException {
			return new Long(s);
		}
	}


上面的程序，我们期望它能正确抛出下列可能的异常结果之一。

* validateUserInput处理s1抛MyNullException
* validateUserInput处理s2抛MyNullException
* validateUserInput处理s3抛MyNullException
* parseLong处理s1抛MyMathException
* parseLong处理s2抛MyMathException
* parseLong处理s3抛MyMathException

但实际上，不管哪种情形，抛出的错都一样，AppException。

	cn.xwiz.test.exception.AppException: wrong in process
		at cn.xwiz.test.exception.Client.process(Client.java:27)
		at cn.xwiz.test.exception.Client.main(Client.java:11)
	
这是一种无效的异常提示，毫无用处。而且实际上，我们的产品里，都充斥着大量这样错误的处理。糟糕的问题在于，你无法知道问题到底在哪里。

## 解决方法

* 最不建议的方法：在所有的方法内都去printStackTrace。缺点也很明显：从外到里层层重复异常打印，你还不得不fillInStackTrace()覆盖异常的起始点。

* 最实际的方法：在所有Throwable的自定义异常类中，传递一个当前throwable对象并往上抛，在最外层打印异常。保持异常链。

代码改进：

    private static void process() throws AppException {
        try {
            ...
			...
			...
        } catch (AppException ae) {
            throw new AppException("wrong in process", ae); //  <--- 传递一个throwable参数
        }
    }


    private static Long calculate(String s1, String s2, String s3) throws MyMathException {
        try {
            ...
			...
			...
        } catch (NumberFormatException nfe) {
            throw new MyMathException("wrong in calculate", nfe); // <--- 传递一个throwable参数
        }
    }


最后异常打印结果非常具体：

	cn.xwiz.test.exception.AppException: wrong in process
		at cn.xwiz.test.exception.Client.process(Client.java:27)
		at cn.xwiz.test.exception.Client.main(Client.java:11)
	Caused by: cn.xwiz.test.exception.MyMathException: wrong in calculate
		at cn.xwiz.test.exception.Client.calculate(Client.java:43)
		at cn.xwiz.test.exception.Client.process(Client.java:25)
		... 1 more
	Caused by: java.lang.NumberFormatException: For input string: "abc"
		at java.lang.NumberFormatException.forInputString(NumberFormatException.java:65)
		at java.lang.Long.parseLong(Long.java:589)
		at java.lang.Long.<init>(Long.java:965)
		at cn.xwiz.test.exception.Client.parseLong(Client.java:48)
		at cn.xwiz.test.exception.Client.calculate(Client.java:39)
		... 2 more

* 指出了最直接的错误行数
* 指出了最清晰的过程逻辑：逻辑是由外到内，异常由栈顶到栈底。


所以，不要小看一个小小的改进，它提供的结果差异非常巨大。另外我总结异常的两个注意点：

* 永远不要屏蔽异常的cause
* 没事别乱用fillInStackTrace()，它会彻底掩盖root cause



	
