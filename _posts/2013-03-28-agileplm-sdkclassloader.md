---
title: Into Agile SDK Class Loader Logic
author: Jie Chen
date: 2013-03-28
categories: [AgilePLM]
tags: [classload]
---

When you use Agile API to develop SDK customization to extend your functionality, you may not care about how SDK architecture is designed by Agile smart engineers and you only focus attention on your own code. As the software engineer and Agile implementation developer, it's better to understand SDK internal, which could benefit you for your development/design ability on Java. Be realistic, you will understand how to diagnose SDK issues. This article will discuss how SDK dynamically load required classes from remote and reuse them, and how Agile loads the PX jars dynamically at server side.

## SDK Client Class Loading in Client Machine

First we look at this quite simple code which only creates Agile session.

	public class TestClient {
		public static void main(String args[]){
			String url = "http://agile.company.com/Agile";
			try {
				Map params = new HashMap();
				String stLoginUser = "admin";
				String stLoginPW = "agile";
				long start = System.currentTimeMillis();
				AgileSessionFactory factory = AgileSessionFactory.getInstance(url);
				params.put(AgileSessionFactory.USERNAME, stLoginUser);
				params.put(AgileSessionFactory.PASSWORD, stLoginPW);
				IAgileSession session = factory.createSession(params);
				System.out.print((System.currentTimeMillis()-start )+ " milliseconds");            
				
			} catch (APIException e) {
				e.printStackTrace();
			} 
		}
	}
	
We use System.currentTimeMillis() to record the elapsed time and we check two-time execution.

	//First time
	25422 milliseconds
	//Second time
	12765 milliseconds

You may feel interested that it must be something cached in SDK client local that the second execution benefits from. It is and how to identify. I will show two important JVM parameters Agile uses.

	-Dncl.printload=true -Dncl.printfind=true

Actually there is another parameter -Dncl.invalidate=true, which force Agile to load classes every time the SDK is executed and no cache is used. "ncl" means Network Class Loader. Never mind let us just go ahead to apply them to SDK client's JVM and run it again (You are required to delete java.io.tmpdir/AgileSDK.cache/ folder first, I will discuss later). Now you will see a mass of messages printed in SDK Client console

	Loading 'com.agile.api.pc.Session' ... loaded (total bytes: 41406)
	Loading 'com.agile.api.common.IResourceBundleHolder' ... loaded (total bytes: 41609)
	Loading 'com.agile.api.pc.APIObject' ... loaded (total bytes: 48867)
	Loading 'com.agile.api.common.IObjectType' ... loaded (total bytes: 50735)
	Loading 'com.agile.api.common.ISecuredObject' ... loaded (total bytes: 51138)
	... ...
	... ...

Run the client program for the second time and notice that

	Loading 'com.agile.api.pc.Session' ... loaded from cache
	Loading 'com.agile.api.common.IResourceBundleHolder' ... loaded from cache
	Loading 'com.agile.api.pc.APIObject' ... loaded from cache
	Loading 'com.agile.api.common.IObjectType' ... loaded from cache
	Loading 'com.agile.api.common.ISecuredObject' ... loaded from cache
	... ...
	... ...

We see difference that the first time it shows "loaded" and second time it shows "loaded from cache".

## What is loaded

Of course something is loaded, but what is that? Let's check HTTP access log. (Why check HTTP log, well, it is a secret).

	127.0.0.1 - - [27/Mar/2013:22:57:40 -0700] "GET /Agile/ServerAPIProperties HTTP/1.1" 200 792 
	127.0.0.1 - - [27/Mar/2013:22:57:40 -0700] "GET /Agile/LoaderServlet?op=loadClass&val=com%2Fagile%2Fapi%2Fpc%2FSession.class HTTP/1.1" 200 41494 
	127.0.0.1 - - [27/Mar/2013:22:57:40 -0700] "GET /Agile/LoaderServlet?op=loadClass&val=com%2Fagile%2Fapi%2Fcommon%2FIResourceBundleHolder.class HTTP/1.1" 200 211 
	127.0.0.1 - - [27/Mar/2013:22:57:40 -0700] "GET /Agile/LoaderServlet?op=loadClass&val=com%2Fagile%2Fapi%2Fpc%2FAPIObject.class HTTP/1.1" 200 7274 
	127.0.0.1 - - [27/Mar/2013:22:57:40 -0700] "GET /Agile/LoaderServlet?op=loadClass&val=com%2Fagile%2Fapi%2Fcommon%2FIObjectType.class HTTP/1.1" 200 1876 
	... ...
	... ...

There are many HTTP access record in log file and actually, we can access them in browser and browser will download these files for us. 
http://agile.company.com/Agile/LoaderServlet?op=loadClass&val=com%2Fagile%2Fapi%2Fpc%2FSession.class 
Save above to Session.class, and the filesize is 41406 
http://agile.company.com//Agile/LoaderServlet?op=loadClass&val=com%2Fagile%2Fapi%2Fcommon%2FIResourceBundleHolder.class 
Save above to IResourceBundleHolder.class and the filesize is 203

## Where is loaded

Ok, we get it that these classes are loaded and saved to local. But where are they? Agile saves all loaded classes to SDK client machine at this location:

	System.getProperty("java.io.tmpdir") + "/AgileSDK.cache/"

If you open this location you will find there are two files:

	#host_port#_Agile_.cache : It contains all the downloaded classes and combined to a binary file. 
	#host_port#_Agile_.properties : It contains the indexing data for the classes(File size and file header position locator)

Let's open_Agile_.properties, and check below:

	current-impl-version=Agile PLM 9.3.1.1 (2011-04-20.15-14-39.966)
	current-server-version=9.3.1.1 (Build 43)
	SIZ-com/agile/api/pc/Session.class=41406
	IDX-com/agile/api/pc/Session.class=0
	SIZ-com/agile/api/common/IResourceBundleHolder.class=203
	IDX-com/agile/api/common/IResourceBundleHolder.class=41406
	SIZ-com/agile/api/pc/APIObject.class=7258
	IDX-com/agile/api/pc/APIObject.class=41609

SIZ means the class filesize. IDX is the class position locator in_Agile_.cache, that is to tell Agile where the current class begins from.

	#host_port#_Agile_.cache : It contains all the downloaded classes and combined to a binary file. 
	#host_port#_Agile_.properties : It contains the indexing data for the classes(File size and file header position locator)

## When is loaded?

Agile will cache all the loaded classes into one single *.cache file for the first time. And next executions will load from local directly. But in client code, which class invocation will make Agile to load(from local or remote)? Exactly during this execution:

	IAgileSession session = factory.createSession(params);

## How is loaded?

Agile use a Network Class Loader which implements Java ClassLoader to load com/agile/api/pc/Session.class first, then get all other classes from the same ClassLoader which initializes Session.class's in server's JVM, not client's.

## PX Class Loading in Server

You are always asked by Oracle Support to check this url: http://agile.company.com/Agile/ServerAPIProperties . Below is sample output from the link:

	#
	# java.io.tmpdir=C:\Windows\TEMP\
	# java.io.tmpdir.readable=true
	# java.io.tmpdir.writable=true
	# sdk.extensions=C:/Agile/Agile931/integration/sdk/extensions
	# sdk.extensions.readable=true
	# sdk.extensions.writable=true
	# cookie.domain=.sl.agilesoft.com
	#
	minimum-api-version=9.22
	current-server-version=9.3.1.1 (Build 43)
	current-impl-version=Agile PLM 9.3.1.1 (2011-04-20.15-14-39.966)
	session-class=com.agile.api.pc.Session
	authenticator-class=com.agile.api.common.WebLogicAuthenticator
	transaction-manager=com.agile.api.common.WebLogicTransactionManager
	app.server.type=weblogic
	env-name.0=java.naming.factory.initial
	env-name.1=java.naming.provider.url
	env-value.1=t3://agile.company.com:80
	env-set.1=false
	env-value.0=weblogic.jndi.WLInitialContextFactory
	env-set.0=false


There are two important items you need to care about: java.io.tmpdir and sdk.extensions. All the PX jars are deployed to sdk.extensions folder, but why we need to care about java.io.tmpdir? Check the folder and you would see a folder name "sdk.extensions.libs", and there are all the PX jars here. Why these jars are copied from sdk.extensions to java.io.tmpdir\sdk.extensions.libs\ ? Agile first copies them to java.io.tmpdir\sdk.extensions.libs\ folder, then uses URL Class Loader to dynamically load all the classes/jars in this temporary folder upon below event:

* When PX is invoked for the first time and no such jar in sdk.extensions.libs folder
* When the timestamp of same Jar in sdk.extensions is newer than the one in sdk.extensions.libs and PX executes again
* When PX is setup in JavaClient for the first time

## Error of Local Class Incompatible: Stream Classdesc SerialVersionUID

You may see "Error of Local Class Incompatible: Stream Classdesc SerialVersionUID" very often especially after you upgrade Agile with main release or patch/hotfix. It means the version of loaded class in Client is different from version in server side. You should check current-impl-version and current-server-version in below two items then.

* #host_port#_Agile_.properties in client's "java.io.tmpdir/AgileSDK.cache/" folder
* http://agile.company.com/Agile/ServerAPIProperties