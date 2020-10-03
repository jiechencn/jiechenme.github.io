---
title: JDBC驱动的类加载-SPI新瓶装旧酒
author: Jie Chen
date: 2019-01-18
categories: [Java]
tags: [jdbc]
---


今天偶尔看JDBC驱动的问题，发现了一个有意思的事情，就是Class.forName()和SPI动态注册，原理居然是一样的，新瓶装旧酒。

## 老派的手工注册

JDBC很早以来一直采用驱动管理显式的注册方法，就是驱动类的使用者，显式地使用Class.forName()加载驱动类。

	Class.forName("oracle.jdbc.driver.OracleDriver");
	Connection con=DriverManager.getConnection(...);

Class.forName()的作用是初始化具体driver类的用static显式声明的静态初始化块。按照JDBC Driver定义的规范，每个Driver必须把自己注册到java.sql.DriverManager。比如oracle.jdbc.driver.OracleDriver源代码中的静态方法块声明类对象的一个实例，并把这个实例对象的引用提交到DriverManager.registerDriver()中。

	static{
		try{
			if (defaultDriver == null){
				defaultDriver = new oracle.jdbc.OracleDriver();
				DriverManager.registerDriver(defaultDriver);
			}


注册到DriverManager中的是具体的driver的对象引用，被保存在一个容器中。

接下来DriverManager.getConnection方法会循环试探容器的一个个的driver对象引用，调用该对象的的connect()方法连接。

	private static Connection getConnection(
		...
			for(DriverInfo aDriver : registeredDrivers) {
				if(isDriverAllowed(aDriver.driver, callerCL)) {
					try {
						Connection con = aDriver.driver.connect(url, info);
						if (con != null) {
							// Success!
							println("getConnection returning " + aDriver.driver.getClass().getName());
							return (con);
						}
				

## Service Provider Interface

JDBC 4.0规范引入了Service Provider，指定所有的JDBC Driver jar包必须在META-INF/Services/java.sql.driver 文件中写明各自的驱动名称。开发人员不再需要Class.forName()手动加载。只需要下面一行代码就是先数据库连接。

	Connection con=DriverManager.getConnection(...);


### SPI看起来很优雅

代码太简洁到不可思议，背后它用了不少的代码，目的只是帮程序员少输入一行而已。

首先，DriverManager.getConnection是个静态方法，调用时，DriverManager中的静态初始化块必须先被执行。

	static {
		loadInitialDrivers();
	}

再调用一个静态方法，ServiceLoader去加载所有的java.sql.Driver文件声明的驱动名。马上看到了一个熟悉的身影： Class.forName(...)。

	private static void loadInitialDrivers() {
		AccessController.doPrivileged(new PrivilegedAction<Void>() {
			public Void run() {
				ServiceLoader<Driver> loadedDrivers = ServiceLoader.load(Driver.class);    
				...
				for (String aDriver : driversList) {
				try {
					Class.forName(aDriver, true, ClassLoader.getSystemClassLoader());      

### 动态加载的好处

使用Class.forName()或者SPI的好处避免驱动类的显式import，实现了运行时的动态加载，并且隐藏了供应商的诸多细节，统一使用抽象层的JDBC API即可。
					
### 一个老问题

DriverManager.getConnection去调用具体driver对象引用的时候，是采用迭代的方式，循环地让注册列表中的每个driver试探着去连接。每次连接结果有三种：

* 如果url格式正确，driver能连接，就返回java.sql.Connection实例
* 如果url格式正确，但driver处理物理连接时错误，就向上抛出异常
* 如果url格式错误，直接返回null

如果在一个应用中有多个driver驱动类被引入，于是会出现一个久远的老问题：循环连接试探的时间浪费。那么，有应用需要多个不同种类驱动的可能吗？

当然有。

好在有连接池的存在，把我们的问题掩盖了。
