---
title: Allow agile user to login Weblogic Admin Console
author: Jie Chen
date: 2014-05-30
categories: [AgilePLM]
tags: []
---


By default, Agile uses the default user superadmin as the administrator user to start Weblogic and logon Admin Console. We also can enable any type of users to start Weblogic or manage it, for example the DB user and LDAP user.

* DB User to start WLS and logon WLS console
* LDAP user to logon WLS console

Note: As we talked in previous article Agile superadmin authentication during Weblogic startup, user authentication during WLS startup is based on the DB connection, so LDAP user is not feasible to start WLS.

## DB User to start WLS and logon WLS console

To allow DB user to start and login Weblogic, we only modify agile.properties and boot.properties file. Note if enable wls.admin.console.users in agile.properties file will override the user account in boot.properties. That is to say, if superadmin user to start WLS but also other user to logon WLS console, add both superadmin and other users to wls.admin.console.users parameters like below.

	## agile.properties
	wls.admin.console.users =superadmin;admin;jiechen

If non-superadmin user to start and logon WLS console, need to modify both files to remove superadmin as below.

	## agile.properties
	wls.admin.console.users =admin;jiechen

.
	
	## boot.properties
	username=admin
	password=agile9


## LDAP user to logon WLS console

Each user in wls.admin.console.users will be added to WLS subject as separate principle and must be validated through WLSLoginModule.class which only works as DB authentication. So if add one ldap user to wls.admin.console.users and expect this user to logon WLS console will get 403--Forbidden error.

	Error 403--Forbidden
	From RFC 2068 Hypertext Transfer Protocol -- HTTP/1.1:
	10.4.4 403 Forbidden
	The server understood the request, but is refusing to fulfill it. 
	Authorization will not help and the request SHOULD NOT be repeated.
	 ... 
	 This status code is commonly used when the server does not wish to reveal exactly why the request has been refused,
	 or when no other response is applicable.

And in WLS server log the detailed error is:

	java.lang.NullPointerException
			at com.agile.util.Scrambler.getHashAlgorithm(Scrambler.java:107)
			at com.agile.admin.security.userregistry.DBUserAdapter.checkPassword(DBUserAdapter.java:89)
			at com.agile.admin.security.userregistry.DBUserAdapter.validateCredentials(DBUserAdapter.java:858)
			at com.agile.admin.security.weblogic.WLSLoginModule.validate(WLSLoginModule.java:477)
			at com.agile.admin.security.weblogic.WLSLoginModule.login(WLSLoginModule.java:199)
			at com.bea.common.security.internal.service.LoginModuleWrapper$1.run(LoginModuleWrapper.java:110)
			at java.security.AccessController.doPrivileged(Native Method)
			at com.bea.common.security.internal.service.LoginModuleWrapper.login(LoginModuleWrapper.java:106)

To make it feasible we have to setup on WLS console manually after you successfully set up LDAP Authentication provider in WLS. First logon WLS console as the DB user, go to Security Realms >AgileRealm >Realm Roles, then expand Global Roles || Roles || Admin, click View Role Conditions link.

![](/assets/res/troubleshooting-agileplm-agileuserlogonwls-1.png)

Add a new OR condition "User ldapuser1" with Administrators group. Enable the modification, then user ldapuser1 is able to logon WLS console.

![](/assets/res/troubleshooting-agileplm-agileuserlogonwls-2.png)

![](/assets/res/troubleshooting-agileplm-agileuserlogonwls-3.png)

Additionally, if add a new OR condition "Group LDAP Group 1", the all the ldap users in the Group of "LDAP Group 1" are allowed to logon as well. 

![](/assets/res/troubleshooting-agileplm-agileuserlogonwls-4.png)


