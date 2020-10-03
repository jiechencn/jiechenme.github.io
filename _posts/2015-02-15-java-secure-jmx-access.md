---
title: 4 ways to secure JMX remote access
author: Jie Chen
date: 2015-02-15
categories: [Java]
tags: []
---

Many Java/JEE applications open the JMX interface/port to external for tuning or monitor purpose and most of them allow anonymous access. That is to say anyone knows the remote hostname and its JMX port can access/modify all exposed MBeans. I will show how to authenticate JMX access with following 4 options.

*  File-based Password 
* File-based PWD with SSL for Server Only
* File-based PWD with SSL for Both Client & Server
* Customized JMXAuthenticator

## 1. File-Based Password Authentication

Create a jmxremote.passowrd file in your specified directory and assign username/password. For example we set up a username "superadmin" with his password "agile9". 

	## jmxremote.password
	superadmin agile9

Create jmxremote.access file and assign JMX access privilege to superadmin user.

	## jmxremote.access
	monitorRole   readonly
	superadmin    readwrite

Anyone can read these two files and get superadmin password if no permission is set. Also Java will throw error and end application with following error if these two files are not restricted.

	Error: Password file read access must be restricted: 
		   /u01/agile/agile933/agileDomain/config/jmxremote.password

 So we can do permission setting to allow only current user have the read/write privilege to it. In Linux we can have below.

	[oracle@jielinux config]$ chmod 600 jmxremote.access
	[oracle@jielinux config]$ chmod 600 jmxremote.password

	[oracle@jielinux config]$ ls -l jmxremote.*
	-rw-------. 1 oracle dba 67 Feb 13 22:07 jmxremote.access
	-rw-------. 1 oracle dba 40 Feb 13 22:07 jmxremote.password

### System Property Set to Server JVM

In remote Java application, we enable JMX Agent to read the specified password and access file with following parameters.

	-Dcom.sun.management.jmxremote=true
	-Dcom.sun.management.jmxremote.port=9899 
	-Dcom.sun.management.jmxremote.authenticate=true 
	-Dcom.sun.management.jmxremote.password.file=/u01/agile/agile933/agileDomain/config/jmxremote.password 
	-Dcom.sun.management.jmxremote.access.file=/u01/agile/agile933/agileDomain/config/jmxremote.access
	-Dcom.sun.management.jmxremote.ssl=false

### JMC Client Access

Run JConsole or Java VisualVM and input the specified credential to access remote JMX agent. 

![](/assets/res/20150214_securejmx_logon.png)

<br/>

## 2. File-based PWD with SSL for Server Only

### System Property Set to Server JVM

All the configurations are same like above except System Property set to JVM

	-Dcom.sun.management.jmxremote=true
	-Dcom.sun.management.jmxremote.port=9899
	-Dcom.sun.management.jmxremote.authenticate=false
	-Dcom.sun.management.jmxremote.ssl=true
	-Dcom.sun.management.jmxremote.password.file=/u01/agile/agile933/agileDomain/config/jmxremote.password 
	-Dcom.sun.management.jmxremote.access.file=/u01/agile/agile933/agileDomain/config/jmxremote.access
	-Dcom.sun.management.jmxremote.ssl.need.client.auth=false <-------------------------------------------
	-Djavax.net.ssl.keyStore=/u01/agile/server.keystore
	-Djavax.net.ssl.keyStorePassword=serverkeystorepassword
	-Dcom.sun.management.jmxremote.registry.ssl=true

### Export Server Certificate

	keytool -export -alias agileserver -keystore /u01/agile/server.keystore -file /u01/agile/server.cer -storepass serverkeystorepassword

### Import CA into JConsole's truststore

	keytool -import -alias agileserver-truststore -file server.cer -keystore jconsole.truststore -storepass changeit

### Set JConsole Startup Parameter

	jconsole -J-Djavax.net.ssl.trustStore=jconsole.truststore -J-Djavax.net.ssl.trustStorePassword=changeit

<br/>

## 3. File-based PWD with SSL for Both Client & Server

### System Property Set to Server JVM

	-Dcom.sun.management.jmxremote=true
	-Dcom.sun.management.jmxremote.port=9899
	-Dcom.sun.management.jmxremote.authenticate=false
	-Dcom.sun.management.jmxremote.ssl=true
	-Dcom.sun.management.jmxremote.password.file=/u01/agile/agile933/agileDomain/config/jmxremote.password 
	-Dcom.sun.management.jmxremote.access.file=/u01/agile/agile933/agileDomain/config/jmxremote.access
	-Dcom.sun.management.jmxremote.ssl.need.client.auth=true    <-------------------------------------------
	-Djavax.net.ssl.keyStore=/u01/agile/server.keystore
	-Djavax.net.ssl.keyStorePassword=serverkeystorepassword
	-Dcom.sun.management.jmxremote.registry.ssl=true

### Export Server Certificate

Same as above

### Import Server CA into JConsole's truststore

Same as above

### Export JConsole Certificate

Similar to above 

### Import JConsole CA into Server's truststore

Similar to above 

### Set JConsole startup parameter

Same as above

<br/>

## 4. Customized JMXAuthenticator

File-Based Password Authentication is simple, but SSL is expensive unless it is a self-signed. An enhanced authentication is to customize JMXAuthenticator. Following is a practical case that I develop a new LoginModule of javax.security.auth.spi.LoginModule to read Weblogic Server's boot.properties, then authenticate JMX Client's credential. 

### JAAS Configuration

Create a JAAS configuration file jmxauth.cfg and name a customized configuration entry JMXLogonConfig.

	## jmxauth.cfg 
	JMXLogonConfig{
	com.agile.support.jmx.JMXLoginModule REQUIRED debug=true;
	};

### Customized LoginModule

First an authenticated Principal.

	## JMXPrincipal.java
	package com.agile.support.jmx;
	import java.security.Principal;
	public class JMXPrincipal implements Principal {
		private String name;
		public JMXPrincipal(String name) {
			this.name = name;
		}
		public boolean equals(Object o) {
			return (o instanceof JMXPrincipal)this.name.equalsIgnoreCase(((JMXPrincipal) o).name);
		}
		public String getName(){
				return name;
		}
		public int hashCode(){
				return name.toUpperCase().hashCode();
		}
	}

JMXLoginModule.java first reads JMX Client's input (username and password), then encrypt the boot.properties's password and compare these two. If they match, return the authenticated Principal.

	## JMXLoginModule.java
	package com.agile.support.jmx;
	import java.util.Map;
	import java.util.Properties;
	import java.io.BufferedInputStream;
	import java.io.File;
	import java.io.FileInputStream;
	import javax.security.auth.Subject;
	import javax.security.auth.callback.CallbackHandler;
	import javax.security.auth.login.LoginException;
	import javax.security.auth.spi.LoginModule;
	import javax.security.auth.callback.Callback;
	import javax.security.auth.callback.NameCallback;
	import javax.security.auth.callback.PasswordCallback;
	import weblogic.security.internal.SerializedSystemIni;
	import weblogic.security.internal.encryption.ClearOrEncryptedService;

	public class JMXLoginModule implements LoginModule {
		private static final String passwordFile = System.getProperty("weblogic.system.BootIdentityFile");
		private boolean isAuthenticated = false;
		private CallbackHandler callbackHandler;
		private Subject subject;
		private JMXPrincipal principal;
		private Properties userCredentials;

		public void initialize(Subject subject, CallbackHandler callbackHandler,
				Map sharedState, Map options) {
			this.subject = subject;
			this.callbackHandler = callbackHandler;
			System.out.println("bigin");
		}

		public boolean login() throws LoginException {
			try {
				NameCallback nameCallback = new NameCallback("username");
				PasswordCallback passwordCallback = new PasswordCallback(
						"password", false);
				final Callback[] calls = new Callback[] { nameCallback,
						passwordCallback };
				callbackHandler.handle(calls);

				String username = nameCallback.getName();
				String password = String.valueOf(passwordCallback.getPassword());
				System.out.println("Got username/password from JMX Client");
				if (username != null && password != null) {
					username = username.trim();
					password = password.trim();
					
					loadPasswordFile();
					System.out.println("Got username/password from boot.properties");
					String filePwd = userCredentials.getProperty("password");
					String fileUserName = userCredentials.getProperty("username");
					
					System.out.println("Comparing with boot.properties");
					
					int l=passwordFile.lastIndexOf(File.separator);
					String securityPath = passwordFile.substring(0, l) + File.separator + "../security";
					
					ClearOrEncryptedService ces = new ClearOrEncryptedService(
						SerializedSystemIni.getEncryptionService(securityPath)
					);
					fileUserName = fileUserName.replace("\\", "");
					fileUserName =ces.decrypt(fileUserName);
					filePwd = filePwd.replace("\\", "");
					filePwd =ces.decrypt(filePwd);
					
					if (password.equals(filePwd) && username.equals(fileUserName)) {
						principal = new JMXPrincipal(username);
						isAuthenticated = true;
						System.out.println("JMX Client authenticated");
					} else {
						throw new LoginException("JMX Client not authenticated, user or password is wrong");
					}
				}

			} catch (Exception e) {
				e.printStackTrace();
				throw new LoginException(
						"Failed to authenticate: no such user/no password file");
			}
			return isAuthenticated;
		}

		private void loadPasswordFile() throws Exception {
			FileInputStream fis;
			try {
				fis = new FileInputStream(passwordFile);
				BufferedInputStream bis = new BufferedInputStream(fis);
				userCredentials = new Properties();
				userCredentials.load(bis);
				bis.close();
			} catch (Exception e) {

				throw e;
			}
		}

		public boolean commit() throws LoginException {
			if (isAuthenticated) {
				subject.getPrincipals().add(principal);
			} else {
				throw new LoginException("Authentication failure");
			}
			return isAuthenticated;
		}

		public boolean abort() throws LoginException {
			return false;
		}

		public boolean logout() throws LoginException {
			subject.getPrincipals().remove(principal);
			principal = null;
			return true;
		}

	}


The important code of above is how to decrypt boot.properties. securityPath points to the directory of SerializedSystem.dat file of Weblogic.
	
	ClearOrEncryptedService ces = new ClearOrEncryptedService(SerializedSystemIni.getEncryptionService(securityPath));
	fileUserName = fileUserName.replace("\\", "");
	fileUserName =ces.decrypt(fileUserName);
	filePwd = filePwd.replace("\\", "");
	filePwd =ces.decrypt(filePwd);

## Configuration to JMX Agent

Be sure to pack these two classes into a jar and include to classpath of Weblogic.

	## setEnv.sh
	$LIB_HOME/jmxlogin.jar

Then add JMX properties to JVM, inform the agent to read the specified JAAS entry: com.agile.support.jmx.JMXLoginModule

	## startWeblogic.sh
	-Dcom.sun.management.jmxremote 
	-Dcom.sun.management.jmxremote.port=9899 
	-Dcom.sun.management.jmxremote.authenticate=true 
	-Djava.security.auth.login.config=/u01/agile/agile933/agileDomain/config/jmxauth.cfg 
	-Dcom.sun.management.jmxremote.login.config=JMXLogonConfig 
	-Dcom.sun.management.jmxremote.access.file=/u01/agile/agile933/agileDomain/config/jmxremote.access 
	-Dcom.sun.management.jmxremote.ssl=false


## JMX Client Access

Following message will be printed to Weblogic standard server output upon JMX Client's correct credential.

	Got username/password from JMX Client
	Got username/password from boot.properties
	Comparing with boot.properties
	JMX Client authenticated

 