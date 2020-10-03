---
title: Java证书安装及多个Java应用间的根证书交叉信任
author: Jie Chen
date: 2018-03-11
categories: [Java]
tags: [ssl]
---


在一套Java产品环境中，常常会存在不同的Java应用，相互之间会通过HttpClient模拟HTTP访问对方，这时就涉及到浏览器所不会用到的特殊的过程：根证书的交叉信任。最后面会讲为什么浏览器和Java应用服务器的通信不需要交叉导入根证书（公钥）。

## 制作密钥库文件

假设有2个Java应用, app1和app2，用户访问的地址为https://app1.xwiz.cn 和 https://app2.xwiz.cn。首先分别为这两个主机应用创建密钥库JKS和证书请求CSR

	keytool -genkey -alias app01 -keyalg RSA -keysize 2048 -keystore /u01/app1_xwiz_cn_keystore.jks -dname "CN=app1.xwiz.cn,OU=cn, O=xwiz, L=Suzhou, ST=Jiangsu, C=CN" 
	
这个过程中，必须提供密钥库JKS文件的密码和私钥密码。

## 发送证书请求

	keytool -certreq -alias app01 -file /u01/app1_xwiz_cn.csr -keystore /u01/app1_xwiz_cn_keystore.jks

发送请求给CA，并下载证书文件。


## 导入证书文件

CA机构办法的证书文件格式不尽相同，具体需要查询CA的帮助说明。

以PFX格式为例，假设CA发来的是PKCS#12格式PFX文件。将该证书文件导入到前面创建CSR文件的keystore文件中。

	keytool -importkeystore -srckeystore /u01/app1_xwiz_cn.pfx -srcstoretype pkcs12 -srcstorepass mykeystorepassword -destkeystore /u01/app1_xwiz_cn_keystore.jks

它会把整个证书链(按照服务器证书-中间证书-根证书的顺序)一起导入到keystore中。

使用 -list 可以清楚地看到证书链，最上面为服务器证书、其次为中间CA，最后为根CA。

	keytool -list -v -keystore /u01/app1_xwiz_cn_keystore.jks >app1_keystore.txt

导出为app1_keystore.txt文件

	Alias name: app01
	Creation date: Feb 8, 2018
	Entry type: PrivateKeyEntry
	Certificate chain length: 3
	Certificate[1]:
	Owner: CN=app1.xwiz.cn,OU=cn, O=xwiz, L=Suzhou, ST=Jiangsu, C=CN"
	Issuer: CN=Entrust Certification Authority - L1K, OU="(c) 2012 Entrust, Inc. - for authorized use only", OU=See www.entrust.net/legal-terms, O="Entrust, Inc.", C=US
	Serial number: xxxxxx
	Valid from: Fri Feb 02 04:56:16 CST 2018 until: Sat Feb 01 05:26:15 CST 2020
	Certificate fingerprints:
		 //下面为私钥

	Certificate[2]:
	Owner: CN=Entrust Certification Authority - L1K, OU="(c) 2012 Entrust, Inc. - for authorized use only", OU=See www.entrust.net/legal-terms, O="Entrust, Inc.", C=US
	Issuer: CN=Entrust Root Certification Authority - G2, OU="(c) 2009 Entrust, Inc. - for authorized use only", OU=See www.entrust.net/legal-terms, O="Entrust, Inc.", C=US
	Serial number: yyyyyy
	Valid from: Wed Oct 22 12:05:14 CDT 2014 until: Wed Oct 23 02:33:22 CDT 2024
	Certificate fingerprints:
		 //下面为中间CA的公钥

	Certificate[3]:
	Owner: CN=Entrust Root Certification Authority - G2, OU="(c) 2009 Entrust, Inc. - for authorized use only", OU=See www.entrust.net/legal-terms, O="Entrust, Inc.", C=US
	Issuer: CN=Entrust Root Certification Authority - G2, OU="(c) 2009 Entrust, Inc. - for authorized use only", OU=See www.entrust.net/legal-terms, O="Entrust, Inc.", C=US
	Serial number: zzzzzz
	Valid from: Tue Jul 07 12:25:54 CDT 2009 until: Sat Dec 07 11:55:54 CST 2030
	Certificate fingerprints:
		//下面为根CA的公钥


找到Root Cer很容易，因为根证书的拥有者和颁发者都是自己，就是自己证明自己的权威，好比让派出所自己证明自己就是派出所。

到这一步，app1的证书安装就完成了，通过该Java应用服务器的其他配置（比如证书类型的设置，keystore路径指定等），就可以访问https://app1.xwiz.cn了。

## 交叉信任根证书

由于app1的keystore文件含有app1的私钥，所以导出根证书必须在app1上完成，或者通过浏览器访问导出(这是最常见最简单的方法)。

	keytool -export -alias root -file app1_root.crt -keystore app1_xwiz_cn_keystore.jks

再将app1_root.crt导入对方app2的JRE标准信任库，或者建立一个新的信任库。同理对于app2也做同样的操作。

	keytool -import -trustcacerts -keystore /u01/app2_truststore.jks -storepass changeit -noprompt -alias app01 -file /u01/app/cert/app1_root.cer

如果使用额外的信任库，必须在Java服务器中指定。具体参考该服务器的使用说明。


### 为何需要交叉信任

这里有一个问题，为什么证书导入到服务器之后，不需要将根证书（公钥）导入到浏览器，浏览器就能自动信任该证书？而Java服务器之间互相访问必须要交叉导入对方的根证书？

每一个浏览器都通过自身的机制定义了一个CTL（Certificate Trust List）证书信任列表，Public CA机构如果制定了新的根证书、修改根证书或者作废根证书，都会向每个浏览器厂商发送更新请求，厂商接收请求后定期地推送新的CTL列表到浏览器。

![](/assets/res/20180311-java-ssl-truststore-01.png)

* IE浏览器
>IE浏览器使用的CTL来自于Windows操作系统本身的<a href="https://social.technet.microsoft.com/wiki/contents/assets/31633.microsoft-trusted-root-program-requirements.aspx" class="bodyA" target="_blank">Microsoft Root Certificate Program</a>，它会通过操作系统的自动更新功能，定期地更新信任列表。


* Firefox
>Firefox通过<a href="https://www.mozilla.org/en-US/about/governance/policies/security-group/certs/policy/" class="bodyA" target="_blank">Mozilla Root Store Policy</a>，自动维护CTL。


* Chrome
>Chrome在Windows上，会使用Windows自身CTL，在Linux上则使用Mozilla的CTL。

* Safari
>Safari使用Apple自己的<a href="http://www.apple.com/certificateauthority/ca_program.html" class="bodyA" target="_blank">Public Key Infrastructure</a>更新计划



但是对于Java而言，Java版本发布时，会把当时所有公共的Root Cer存储在<kbd>JDK/jre/lib/security/cacerts</kbd>文件（JRE标准信任库）中，但是这个Java没有自动更新信任库的机制，尤其对于老版本的Java，很多最新的根证书根本就无法信任。这种情况下，Java服务器之间相互访问时，Server一方必须提交自身的证书证明自己，而Client一方的CTL列表中没有该证书的Root CA Certification，所以也就无法信任对方。 此时常常抛出的错为：


	javax.net.ssl.SSLHandshakeException: sun.security.validator.ValidatorException: PKIX path building failed: sun.security.provider.certpath.SunCertPathBuilderException: unable to find valid certification path to requested target
	
同时在TCP 包中返回错误：

	TLSv1.2 Record Layer: Alert (Level: Fatal, Description: Certificate Unknown)
		Content Type: Alert (21)
		Version: TLS 1.2 (0x0303)
		Length: 2
		Alert Message
			Level: Fatal (2)
			Description: Certificate Unknown (46)
