---
title: Agile JavaClient and Java Web Start
author: Jie Chen
date: 2013-06-08
categories: [AgilePLM]
tags: [javaclient]
---

JavaClient uses Java Web Start technology to launch all required jar files and resources into local cache to deploy with online and offline mode. We will discuss how JavaClient is loaded from remote Application Server.

## Detect Java Web Start

Usually we access JavaClient from the entrance http://host:port/JavaClient/start.html . This start.html will detect if Java Web Start is installed on local client machine with below script language in browser's engine. If the browser is not IE, we use navigator element and its function to detect application/x-java-jnlp-file. Else it will use VBScript in IE's engine to detect the object of JavaWebStart. The detailed code is like below. You also can refer to Oracle document in this link. http://docs.oracle.com/javase/7/docs/technotes/guides/javaws/developersguide/launch.html

## pcclient.jnlp

pcclient.jnlp is the entrance point of JavaClient. We save the file to local and open in notepad. We will notice there is codebase defined as below:

	codebase="http://agile.mycompany.com:7001/JavaClient"

That means all the jar and resource must be loaded from the base URL /JavaClient/.

Below entry define the required data for JavaClient. It requires 1.6+ JRE, and defines the expected max heap size. The required jar and other expected extension resources in other referenced jnlp files.

![](/assets/res/troubleshooting_agileplm-javaclientwebstart-1.png)

After all the resource is loaded into local cache, the main class com.agile.ui.pcclient.PCClient runs and asks for login. If we click the Option in login window, we see URL is pre-defined. It is actually read from the pcclient.jnlp.

![](/assets/res/troubleshooting_agileplm-javaclientwebstart-2.png)

	serverURL=t3://agile.mycompany.com:7001
	
Additionally information we can see directly from JavaClient is the webserverName, appserverVersion and UpdateVersions, all of them are defined here as well.

![](/assets/res/troubleshooting_agileplm-javaclientwebstart-3.png)

![](/assets/res/troubleshooting_agileplm-javaclientwebstart-4.png)


## Local Cache

Previously we say all jar and resource are loaded from remote into local machine, if we go to java's cache directory we will see all of them.

![](/assets/res/troubleshooting_agileplm-javaclientwebstart-5.png)



