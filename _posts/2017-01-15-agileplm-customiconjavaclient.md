---
title: 自定义JavaClient中的subclass图标
author: Jie Chen
date: 2017-01-15
categories: [AgilePLM]
tags: [javaclient]
---

关于如何自定义JavaClient中subclass的图标，官方用户手册中讲到了要把图标文件添加到JavaClient的custom.jar中。文档是这么讲的：

![](/assets/res/troubleshooting_agileplm-customiconjavaclient-1.jpg)

实际上如果简单地按照它的做法，这是无法实现的。原因在于所有通过Java Web Start发布的jar文件都是经过了签名保护的。下面的内容我来演示如何通过自签名的方式实现它。

## 创建keystore

	d:\>keytool -genkeypair -dname "cn=Jie Chen, ou=Agile, o=Oracle, c=US" -alias javaclientKey -keypass oracle -keystore d:/temp/custom/javaclientKeystore -storepass oracle -validity 3600

## 签名

	d:\>jarsigner -keystore d:/temp/custom/javaclientKeystore -signedjar d:/temp/custom//custom.jar d:/temp/custom//custom.jar javaclientKey
	Enter Passphrase for keystore:
	jar signed.

	Warning:
	No -tsa or -tsacert is provided and this jar is not timestamped. Without a timestamp, users may not be able to validate this jar after the signer certificate's expiration date (2026-11-24) or after any future revocation date.


## 检查证书


	d:\>jarsigner -verify -verbose -certs d:/temp/custom/custom.jar

	s        367 Sun Jan 15 17:31:04 CST 2017 META-INF/MANIFEST.MF

		  X.509, CN=Jie Chen, OU=Agile, O=Oracle, C=US
		  [certificate is valid from 1/15/17 5:06 PM to 11/24/26 5:06 PM]
		  [CertPath not validated: Path does not chain with any of the trust anchors]

			 403 Sun Jan 15 17:31:04 CST 2017 META-INF/JAVACLIE.SF
			 950 Sun Jan 15 17:31:04 CST 2017 META-INF/JAVACLIE.DSA
			   0 Thu Dec 10 13:02:44 CST 2015 META-INF/
	sm      1215 Wed Apr 21 13:40:56 CST 2010 HowtoCustomizeIcons.txt

		  X.509, CN=Jie Chen, OU=Agile, O=Oracle, C=US
		  [certificate is valid from 1/15/17 5:06 PM to 11/24/26 5:06 PM]
		  [CertPath not validated: Path does not chain with any of the trust anchors]

	sm      1709 Sat Jan 14 20:30:46 CST 2017 qq.png

		  X.509, CN=Jie Chen, OU=Agile, O=Oracle, C=US
		  [certificate is valid from 1/15/17 5:06 PM to 11/24/26 5:06 PM]
		  [CertPath not validated: Path does not chain with any of the trust anchors]


	  s = signature was verified
	  m = entry is listed in manifest
	  k = at least one certificate was found in keystore
	  i = at least one certificate was found in identity scope

	jar verified.

	Warning:
	This jar contains entries whose certificate chain is not validated.
	This jar contains signatures that does not include a timestamp. Without a timestamp, users may not be able to validate this jar after the signer certificate's expiration date (2026-11-24) or after any future revocation date.






## 信任自签名

因为我使用了自签名的证书方式，如果不做特殊处理，Java会认为这是个非法危险的应用，所以必须将JavaClient的URL添加到Java控制面板的特殊列表中去认可它。

## 实际效果

![](/assets/res/troubleshooting_agileplm-customiconjavaclient-2.jpg)





