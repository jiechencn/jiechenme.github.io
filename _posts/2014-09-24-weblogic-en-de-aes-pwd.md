---
title: Decrypt & encrypt the Weblogic password with AES
author: Jie Chen
date: 2014-09-24
categories: [Weblogic]
tags: []
---

Since Agile PLM 9.3.2, Agile uses the same algorithms as Weblogic to encrypt some credential with AES. In some cases that we need to reset these credentials or verify if we input the correct password, we need a method to retrieve the decrypted password. Here is a way. 

All the password encrypted starts with {AES}. We use WLST script to retrieve the decrypted password. 
 
## decryptWLSPwd.py

	import os
	import weblogic.security.internal.SerializedSystemIni
	import weblogic.security.internal.encryption.ClearOrEncryptedService

	def decrypt(agileDomain, encryptedPassword):
		agileDomainPath = os.path.abspath(agileDomain)
		encryptSrv = weblogic.security.internal.SerializedSystemIni.getEncryptionService(agileDomainPath)
		ces = weblogic.security.internal.encryption.ClearOrEncryptedService(encryptSrv)
		password = ces.decrypt(encryptedPassword)
		
		print "Plaintext password is:" + password

	try:
		if len(sys.argv) == 3:
			decrypt(sys.argv[1], sys.argv[2])
		else:
			print "Please input arguments as below"
			print "		Usage 1: java weblogic.WLST decryptWLSPwd.py  "
			print "		Usage 2: decryptWLSPwd.cmd "
			print "Example:"
			print "		java weblogic.WLST decryptWLSPwd.py C:\Agile\Agile933\agileDomain {AES}JhaKwt4vUoZ0Pz2gWTvMBx1laJXcYfFlMtlBIiOVmAs="
			print "		decryptWLSPwd.cmd {AES}JhaKwt4vUoZ0Pz2gWTvMBx1laJXcYfFlMtlBIiOVmAs="
	except:
		print "Exception: ", sys.exc_info()[0]
		dumpStack()
		raise

To simplify the execution of WLST, we code a command file for both Windows and Linux. 

## decryptWLSPwd.cmd for Windows

	@echo off
	SETLOCAL

	call setEnv.cmd

	set CURRENT_DIR=%cd%
	cd %~dp0\..
	set PARENT_DIR=%cd%
	cd %CURRENT_DIR%

	"%JAVA_HOME%\bin\java" weblogic.WLST decryptWLSPwd.py %PARENT_DIR% %*
	echo:

	:finish

	ENDLOCAL

## decryptWLSPwd.sh for Linux

	# Set all env variables
	. ./setEnv.sh

	CURRENT_DIR=`pwd`
	PARENT_DIR=`dirname $CURRENT_DIR`

	CLASSPATH=$CLASSPATH
	export CLASSPATH

	"$JAVA_HOME/bin/java" -ms64m -mx64m -classpath $CLASSPATH weblogic.WLST decryptWLSPwd.py $PARENT_DIR $*

We put these three files to the AGILE_HOME/agileDomain/bin/ directory, then run decryptWLSPwd.cmd/sh file with the encrypted password as the only one parameter.

	# Decrypt the password which is encrypted by AES
	C:\Agile\Agile933\agileDomain\bin>decryptWLSPwd.cmd {AES}JhaKwt4vUoZ0Pz2gWTvMBx1laJXcYfFlMtlBIiOVmAs=
	Your environment has been set.
	Initializing WebLogic Scripting Tool (WLST) ...
	Welcome to WebLogic Server Administration Scripting Shell
	Type help() for help on available commands
	Plaintext password is:tartan

You may also wonder how AES could be generated manually. There is an existing tool, encryptDBSchemaPwd.cmd/sh in the same directory.

	# Encrypt the password with AES
	C:\Agile\Agile933\agileDomain\bin>encryptDBSchemaPwd.cmd agile9
	Your environment has been set.
	Encrypted DB password agile9 is:
	{AES}c5R9FqJTt2ciMtKYLvVe9VWmO1Jeevs7f/SwFiuaBck=

The Encryption and Decryption apply for below cases. 

**db.password in agile.properties**

	db.password={AES}ZSeNBJwMMXvY1N/soDVCVAZf70+OO92wvJJ8n2h5yhg=

**username/password in boot.properties**

	username={AES}7GIUy7cPfuHU+qvI6+Dciozo1i5Ubo0kk9W4UMBd3pI\=
	password={AES}5qvSybU1fUUkhT18XZh9Pj90pBPzBIiZQG1LWQtKgC8\=

**All the encrypted password starts with {AES} in config.xml**

	## Domain Credential
	<credential-encrypted>{AES}Qo5cO4UsLe3bAwTksOTtjBjDCuTgcLlp1tdgUkfca54=</credential-encrypted>

	## Node Manager Credential
	<node-manager-password-encrypted>{AES}hKM+zh5stLC3gK7aCeIaG85wPvTpd7OYvE2aW3yEN60=</node-manager-password-encrypted>

	## Embedded LDAP Credential
	<embedded-ldap>
	<credential-encrypted>{AES}Qo5cO4UsLe3bAwTksOTtjBjDCuTgcLlp1tdgUkfca54=</credential-encrypted>
	</embedded-ldap>

	## External Authentication Provider Credential (like LDAP):
	<wls:credential-encrypted>{AES}8pEqG5E/mvm0GMFRaL7l5Aji5eXXzBRTuLicssk/J8Y=</wls:credential-encrypted>

**Database User Password in CP-AgileContentPool-jdbc.xml**

	<password-encrypted>{AES}v8paGGDePn0Uw92M1i0VxDN3pRqcNtDQ3kBSOd3l2W4=</password-encrypted>

## Note

On Windows the encrypted password which contains "\" cannot be decrypted, so we need to remove "\" before decryption. For example:

Change 

	{AES}UmcRmf70ZzObd1k+HLHUBnH2Y5jv7gyd5QOT992qahY\=
	
to 

	{AES}UmcRmf70ZzObd1k+HLHUBnH2Y5jv7gyd5QOT992qahY=












 