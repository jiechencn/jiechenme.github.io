---
title: superadmin authentication during Weblogic startup
author: Jie Chen
date: 2014-04-30
categories: [AgilePLM]
tags: []
---


In Agile PLM, we all know superadmin account is used to start Weblogic server, this account is authenticated against Agile database. In previous version before 9.3.2, we define the username and its plain password in startAgile script. In 9.3.2 and 9.3.3, we move the user account information to a separate file which is specified by -Dweblogic.system.BootIdentityFile parameter, of course the username and password are encrypted. Since it is a database authentication, by default we shall use SQLAuthenticator to do user validation in Weblogic Security, but Agile uses its own security provider, that is AgileAuthenticator, a customized extension. Let's see how it works during Weblogic start up.

AgileAuthenticator

From config.xml, we see the default authenticator is agile-authenticatorType. The xsi type is ext, not wls, and xsd implements weblogic/security/extension, not weblogic/security.

![](/assets/res/troubleshooting-agileplm-superadminauthentication-1.png)

It is a "unnamed" authentication provider (with no sec:name definition), so Weblogic will lookup a provider named "AgileAuthenticator". The definition of AgileAuthenticator could be found in agileSecurityProviders.jar file which locates in WLS_HOME/server/lib/mbeantypes/. If extract this file, we will find this provider's XML Schema Definition like element, namespace, and type. Also we see AgileAuthenticator.xml has below java implementation definition.

![](/assets/res/troubleshooting-agileplm-superadminauthentication-2.png)

The definition clearly show us the implementation is AgileAuthenticationProviderImpl.class, so if we look at the class file and check what type of Login class wrapped in AppConfigurationEntry to be sent to Java Authentication and Authorization Service (JAAS), we get the concrete login class, that is "WLSLoginModule"

  
	private AppConfigurationEntry getConfiguration(HashMap paramHashMap)
	{
	  paramHashMap.put("database", this.database);
	  return new AppConfigurationEntry("com.agile.admin.security.weblogic.WLSLoginModule", this.controlFlag, paramHashMap);
	}

JAAS then transfers the authentication to WLSLoginModule.class to manage. The module then check the superadmin user account (need to decrypt the username and password first if Agile is 9.3.2 and 9.3.3) against the database.

DB Connection

Many people are confused why Agile defines db connection parameters in two places. One is in agile.properties and the other one is in which is defined in CP-AgileContentPool-jdbc.xml as a Connection Pool.

![](/assets/res/troubleshooting-agileplm-superadminauthentication-3.png)

agile.properties definition

![](/assets/res/troubleshooting-agileplm-superadminauthentication-4.png)

CP-AgileContentPool-jdbc.xml definition

This is a correct design, not a redundance. During superadmin authentication, many Weblogic components are not initialized that connection pool is not ready. So WLSLoginModule cannot get a connection from the AgileContentPool. In this case, WLSLoginModule set up a direct jdbc connection to remote database with parameters from agile.properties, we call it a LocalConnection, which uses the traditional register function listed below.

	Class.forName("oracle.jdbc.driver.OracleDriver");


