---
title: 通过JMX对类路径问题进行诊断
author: Jie Chen
date: 2014-12-04
categories: [Java]
tags: []
---

Java应用类中经常会在生产环境碰到ClassNotFoundException之类的错误。对于一个庞大的Java应用来说，从成百上千的jar文件中去核查这个类很困难。除了这个错误之外，类似的和Class相关的错误还有：

* java.lang.NoClassDefFoundError
* java.lang.ClassNotFoundException
* java.lang.NoSuchMethodException
* java.lang.NoSuchMethodError

另外一个问题时，当多个Jar文件引用了相同名称的不同Class时，你无法直到到底哪个Class才是被JVM加载并生效的。

下面我演示一个非常有用的例子代码来动态地获取任何想获取的Class信息，取名叫ClasspathLocator，它可以通过JMX方式远程让运维人员操作读取。

## JMX服务

创建 JMX MBean interface，方法为：findLocation

	public interface ClasspathLocatorMBean {
		public String findLocation(String klass);
	}

实现这个接口。

	import java.io.File;
	import java.net.URL;
	import java.net.URLDecoder;
	import java.security.CodeSource;
	import java.security.ProtectionDomain;

	public class ClasspathLocator implements ClasspathLocatorMBean {

		@Override
		public String findLocation(String klass) {
			klass = klass.trim();
			try {
				Class clazz = Class.forName(klass);
				if (clazz == null) {
					return “Invalid class: ” + klass;
				}

				ProtectionDomain protectionDomain = clazz.getProtectionDomain();
				CodeSource codeSource = protectionDomain.getCodeSource();
				File jarFile;

				if (codeSource != null && codeSource.getLocation() != null) {
					jarFile = new File(codeSource.getLocation().toURI());
				} else {
					String path = clazz.getResource(clazz.getSimpleName() + “.class”).getPath();
					String jarFilePath = path.substring(path.indexOf(“:”) + 1, path.indexOf(“!”));
					jarFilePath = URLDecoder.decode(jarFilePath, “UTF-8”);
					jarFile = new File(jarFilePath);
				}

				return klass + “ -> ” + jarFile.getAbsolutePath();
			} catch (Throwable e) {
				e.printStackTrace();
				return klass + “ Not found”;
			}
		}

	}

一个Class文件的存在方式有两种，无非就是在Jar压缩包文件中，或者物理文件方式存在。所以在上面的代码中对这两种方式分别处理。


注册这个JMX MBean

	import java.lang.management.ManagementFactory;
	import javax.management.MBeanServer;
	import javax.management.ObjectName;

	public class AgileSupportJMXAgent {
		private final MBeanServer mbs;

		public AgileSupportJMXAgent(){
			mbs = ManagementFactory.getPlatformMBeanServer();
		}

		public void register() throws Exception {
			ObjectName objectName = new ObjectName(“AgileSupport:type=ClasspathLocator”);
			if (mbs.isRegistered(objectName)){
				mbs.unregisterMBean(objectName);
			}
			ClasspathLocator cpLoc = new ClasspathLocator();
			mbs.registerMBean(cpLoc, objectName);
				
		}

	}

以上的代码中，创建了一个叫做AgileSupport的MBean目录，目录中的MBean命名为ClasspathLocator。

## 触发类

JVM必须调用这个ClasspathLocator的JMX服务。方法为：

	new AgileSupportJMXAgent().register();

## JConsole执行

运行JConsole可以远程地连接到服务器的JMX服务端口，可以看到MBean已经成功注册。执行方法列为：findLocation()

![](/assets//res/java_classlocatorjmx_jconsole.png)

比如输入“oracle.jdbc.driver.OracleDriver”，就能得到执行结果如下。

![](/assets//res/java_classlocatorjmx_jconsole_run.png)

