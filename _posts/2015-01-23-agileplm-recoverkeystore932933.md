---
title: Recover Keystore for Agile 9.3.2 and 9.3.3
author: Jie Chen
date: 2015-01-23
categories: [AgilePLM]
tags: []
---

agileks.jks is the Keystore used by Agile based on Java JCEKS and AES algorithm. So all the AES related password in Agile are associated with agileks.jks.

During Agile 9.3.2/9.3.3 installation, a random Keystore password is created automatically, then agileks.jks is created as well based on the random Keystore password. After that Agile will use this Keystore password and Keystore file to encrypt the random Keystore password itself and save to Agile database propertytable table with format of "{AES}xxxx" like "{AES}sX+GBU67vmFlF9z7GcVBa/+qCyrfBL0YF61qOf1iUak=". It is displayed in JavaClient's Preference as "Keystore Password".

In some cases the Keystore will be corrupted. For example, manually modify the Keystore Password in JavaClient or clone Agile database to destination Agile without updating Keystore file. Agile throws Keystore error during startup.

	AgileAuthenticationProviderImpl.initialize
	log4j:WARN No appenders could be found for logger (com.agile.util.sql.OracleConnectionImpl).
	log4j:WARN Please initialize the log4j system properly.
	java.io.IOException: Keystore was tampered with, or password was incorrect
		   at com.sun.crypto.provider.JceKeystore.engineLoad(JceKeystore.java:867)
		   at java.security.Keystore.load(Keystore.java:1214)
		   at com.agile.util.crypto.ContainerCryptoUtil.loadKeystore(ContainerCryptoUtil.java:139)
		   at com.agile.util.crypto.ContainerCryptoUtil.(ContainerCryptoUtil.java:77)
		   at com.agile.admin.security.weblogic.WLSLoginModule.login(WLSLoginModule.java:193)
		   at com.bea.common.security.internal.service.LoginModuleWrapper$1.run(LoginModuleWrapper.java:110)
		   at java.security.AccessController.doPrivileged(Native Method)
		   at com.bea.common.security.internal.service.LoginModuleWrapper.login(LoginModuleWrapper.java:106)
		...
		   at weblogic.security.service.PrincipalAuthenticator.authenticate(PrincipalAuthenticator.java:338)
		   at weblogic.security.service.CommonSecurityServiceManagerDelegateImpl.doBootAuthorization(CommonSecurityServiceManagerDelegateImpl.java:930)
		   at weblogic.security.service.CommonSecurityServiceManagerDelegateImpl.initialize(CommonSecurityServiceManagerDelegateImpl.java:1054)
		   at weblogic.security.service.SecurityServiceManager.initialize(SecurityServiceManager.java:873)
		   at weblogic.security.SecurityService.start(SecurityService.java:148)
		   at weblogic.t3.srvr.SubsystemRequest.run(SubsystemRequest.java:64)
		   at weblogic.work.ExecuteThread.execute(ExecuteThread.java:256)
		   at weblogic.work.ExecuteThread.run(ExecuteThread.java:221)
	*** Can not initialize key store from agileks.jks. Encryption service will fail.
	Error: Wrong Keystore password

To recover Agile Keystore and everything related to AES password, follow below.

## 1. Get a new Keystore password

You can give a new Keystore password at will by yourself. Be sure the password consists of alphabit and number and length is 8. For example "abcd1234".

## 2. Create a new Keystore file

	[oracle@jiechen-linux bin]$ pwd
	/u01/agile/agile932/agileDomain/bin
	[oracle@jiechen-linux bin]$ ./encryptPwdUtil.sh -genkeystore -storepass abcd1234
	Keystore is generated successfully in current directory with arguments:
			Keystore size: 200
			Algorithm: AES
			Key size: 128

A new agileks.jks file will be created in AGILE_HOME/agileDomain/bin/ directory. Use keytool command to validate it.

	[oracle@jiechen-linux config]$ keytool -list -Keystore agileks.jks -storepass abcd1234 -storetype JCEKS
	Keystore type: JCEKS
	Keystore provider: SunJCE
	Your Keystore contains 200 entries
	{aes:128}fd06, Jan 21, 2015, SecretKeyEntry,
	{aes:128}a649, Jan 21, 2015, SecretKeyEntry,
	{aes:128}9e95, Jan 21, 2015, SecretKeyEntry,
	...
	...
	...
	...

If see below error message, it means the Keystore file agileks.jks is invalid.

	[oracle@jiechen-linux config]$ keytool -list -Keystore agileks.jks -storepass abcd1234 -storetype JCEKS
	keytool error: java.io.IOException: Keystore was tampered with, or password was incorrect

Then you need to copy it to AGILE_HOME/agileDomain/config/ folder manually to overwrite the old one.

## 3. Encrypt the Keystore password and save to database

	[oracle@jiechen-linux bin]$ ./encryptDBSchemaPwd.sh abcd1234
	Encrypted DB password abcd1234 is:
	{AES}efw6EBEJWhFIQpIC1KSu7fMMb2T98Sjizk6LgfQM6oU=

SQL to save to database

	update propertytable set value = '{AES}efw6EBEJWhFIQpIC1KSu7fMMb2T98Sjizk6LgfQM6oU=' where parentid=5004 and propertyid=1008;
	commit;

## 4. Re-encrypt below password

* db.password in agile.properties
* ifsuser password in server.conf
* superadmin password in boot.properties if required

This topic applies to 9.3.2 and 9.3.3 only.

