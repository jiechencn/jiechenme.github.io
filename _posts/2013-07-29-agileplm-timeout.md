---
title: Transaction Timeout in Agile + Weblogic
author: Jie Chen
date: 2013-07-29
categories: [AgilePLM]
tags: [weblogic,transaction]
---


We may see different transaction timeout error in Agile (It also happens to other applications). There are two places where control the Timeout setting, JTA, EJB tier and Loadbalancer/Proxy. I will demonstrate three cases to show how to identify them. Though most of the timeout issues are caused by applications and we MUST find out the REAL root cause to fix them definitely, increase transaction-timeout still can resolve most of these cases as a temporary solution/workaround.

## JTA Timeout

JTA Timeout is the system-wide setting for the weblogic domain, in Agile it is agileDomain. That is to say, all the transaction are controlled by JTA globally in the same domain. In Agile, all of database access timeout are detected by JTA setting. For example:

	java.sql.SQLException: Transaction BEA1-03D472459EE07C52BE9E not active anymore. tx status = Marked rollback. [Reason=weblogic.transaction.internal.AppSetRollbackOnlyException]
		at weblogic.jdbc.jts.Driver.getTransaction(Driver.java:550)
		at weblogic.jdbc.jts.Driver.connect(Driver.java:112)
		at weblogic.jdbc.common.internal.RmiDataSource.getConnection(RmiDataSource.java:355)
		at com.agile.util.sql.DefaultConnectionFactory.getConnFromDS(DefaultConnectionFactory.java:84)
		at com.agile.util.sql.DefaultConnectionFactory.getJDBCConnection(DefaultConnectionFactory.java:48)
		at com.agile.util.sql.ConnectionFactory.getConnection(ConnectionFactory.java:37)
		at com.agile.util.sql.DebugConnectionFactory.getJDBCConnection(DebugConnectionFactory.java:59)
		at com.agile.util.sql.ConnectionFactory.getConnection(ConnectionFactory.java:37)
	
Or

	java.sql.SQLException: Transaction BEA1-14D07D02B16929FA01BC not active anymore. tx status = Rolled back. [Reason=weblogic.transaction.internal.TimedOutException: Transaction timed out after 7199 seconds BEA1-14D07D02B16929FA01BC]
		at weblogic.jdbc.jts.Driver.getTransaction(Driver.java:552)
		at weblogic.jdbc.jts.Driver.connect(Driver.java:112)
		at weblogic.jdbc.common.internal.RmiDataSource.getConnectionInternal(RmiDataSource.java:533)
		at weblogic.jdbc.common.internal.RmiDataSource.getConnection(RmiDataSource.java:498)
		at weblogic.jdbc.common.internal.RmiDataSource.getConnection(RmiDataSource.java:491)
		at com.agile.util.sql.DefaultConnectionFactory.getConnFromDS(DefaultConnectionFactory.java:84)
		at com.agile.util.sql.DefaultConnectionFactory.getJDBCConnection(DefaultConnectionFactory.java:48)
		at com.agile.util.sql.ConnectionFactory.getConnection(ConnectionFactory.java:37)
	
You can set it in agileDomain - Services - JTA category.

## EJB Timeout

In Agile, many EJB session beans MAY have their own transaction timeout settings which override the JTA system-wide timeout. They are defined in the weblogic-ejb-jar.xml. If you find the timeout value in error log does not match the one in JTA, then you may have to check the error trace carefully. Because it may be set elsewhere. For example below error shows the timeout error happens from "AdminSessionBean".
java.rmi.RemoteException: Transaction Rolledback.; nested exception is: 

	weblogic.transaction.internal.TimedOutException: Transaction timed out after 300 seconds BEA1-50CB4EE5D0A2611D0A36
		at weblogic.ejb.container.internal.EJBRuntimeUtils.throwRemoteException(EJBRuntimeUtils.java:103)
		at weblogic.ejb.container.internal.BaseRemoteObject.postInvoke1(BaseRemoteObject.java:591)
		at weblogic.ejb.container.internal.StatelessRemoteObject.postInvoke1(StatelessRemoteObject.java:60)
		at weblogic.ejb.container.internal.BaseRemoteObject.postInvokeTxRetry(BaseRemoteObject.java:441)
		at com.agile.admin.server.AdminSessionBean_yg79hz_EOImpl.invokeAction(AdminSessionBean_yg79hz_EOImpl.java:10294)
		at com.agile.ipa.pc.CMHelper.invokeCustomProcessActions(CMHelper.java:386)
		at com.agile.ui.pcm.common.ObjectViewHandler.invokeCustomProcessActions(ObjectViewHandler.java:8150)
		at sun.reflect.GeneratedMethodAccessor333.invoke(Unknown Source)
		at sun.reflect.DelegatingMethodAccessorImpl.invoke(DelegatingMethodAccessorImpl.java:25)
		at java.lang.reflect.Method.invoke(Method.java:597)
		at com.agile.ui.web.action.ActionServlet.invokeMethod(ActionServlet.java:1067)
	
We find AdminSession transaction is defined in applications.ear - admin.jar - META-INF directory 

![](/assets/res/troubleshooting-agileplm-timeout-1.jpg)

Please consult Oracle Agile Support for similar EJB timeout errors before you modify them.

## WebTier Timeout

Different Web tier has different timeout setting, for example below is from Apache HTTP timeout error.

	[Mon Jul 29 00:07:32 2013] [error] [client xxx.xxx.xxx.xxx] (OS 10060)A connection attempt failed because the connected party did not properly respond after a period of time, or established connection failed because connected host has failed to respond.  : proxy: error reading status line from remote server xxxplm.company.com:80, referer: http://proxy.company.com/Agile/PLMServlet?module=LoginHandler&opcode=forwardToMainMenu
	[Mon Jul 29 00:07:32 2013] [error] [client xxx.xxx.xxx.xxx] proxy: Error reading from remote server returned by /Agile/PCMServlet, referer: http://proxy.company.com/Agile/PLMServlet?module=LoginHandler&opcode=forwardToMainMenu

For this WebTier timeout error, you may refer to its vendor/document.

