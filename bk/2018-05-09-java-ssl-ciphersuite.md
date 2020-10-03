---
title: Java加密套件强度限制引起的SSL handshake_failure
author: Jie Chen
date: 2018-05-09
categories: [Java]
tags: [ssl]
---

今天为客户解决了一个奇葩的SSL问题。通过Java代码使用HttpURLConnection去连接https系统时候总是报错handshake_failure。而使用浏览器访问一切正常。记录下诊断的过程。

HttpURLConnection的调用非常简单。
 
	HttpURLConnection connection =
			(HttpURLConnection)m_url.openConnection();

	connection.setRequestMethod("GET");
	connection.setAllowUserInteraction(false);
	connection.setDefaultUseCaches(false);
	connection.setDoInput(true);
	connection.setDoOutput(false);
	connection.setInstanceFollowRedirects(true);
	connection.setUseCaches(false);
	connection.connect(); <----- handshake error

错误也很抽象。
	
	javax.net.ssl.SSLHandshakeException: Received fatal alert: handshake_failure
		at sun.security.ssl.Alerts.getSSLException(Unknown Source)
		at sun.security.ssl.Alerts.getSSLException(Unknown Source)
		at sun.security.ssl.SSLSocketImpl.recvAlert(Unknown Source)
		at sun.security.ssl.SSLSocketImpl.readRecord(Unknown Source)
		at sun.security.ssl.SSLSocketImpl.performInitialHandshake(Unknown Source)
		at sun.security.ssl.SSLSocketImpl.startHandshake(Unknown Source)
		at sun.security.ssl.SSLSocketImpl.startHandshake(Unknown Source)
		at sun.net.www.protocol.https.HttpsClient.afterConnect(Unknown Source)
		at sun.net.www.protocol.https.AbstractDelegateHttpsURLConnection.connect(Unknown Source)
		at sun.net.www.protocol.https.HttpsURLConnectionImpl.connect(Unknown Source)
		at com.agile.common.HttpReader.getInputStream(HttpReader.java:76)
	

初步怀疑本地的JRE证书信任文件中没有包含对方服务器的根证书。

	keytool -list -v -keystore "C:\Java\jdk1.8.0_152\jre\lib\security\cacerts" >store.txt

检查发现，根证书和中间证书都存在，信任链没有问题。

	## 中间证书
	Alias name: comodorsaca [jdk]
	Creation date: 25 Aug, 2016
	Entry type: trustedCertEntry

	Owner: CN=COMODO RSA Certification Authority, O=COMODO CA Limited, L=Salford, ST=Greater Manchester, C=GB
	Issuer: CN=COMODO RSA Certification Authority, O=COMODO CA Limited, L=Salford, ST=Greater Manchester, C=GB
	Serial number: 4caaf9cadb636fe01ff74ed85b03869d
	Valid from: Tue Jan 19 05:30:00 IST 2010 until: Tue Jan 19 05:29:59 IST 2038
	Certificate fingerprints:
		 MD5:  1B:31:B0:71:40:36:CC:14:36:91:AD:C4:3E:FD:EC:18
		 SHA1: AF:E5:D2:44:A8:D1:19:42:30:FF:47:9F:E2:F8:97:BB:CD:7A:8C:B4
		 SHA256: 52:F0:E1:C4:E5:8E:C6:29:29:1B:60:31:7F:07:46:71:B8:5D:7E:A8:0D:5B:07:27:34:63:53:4B:32:B4:02:34
		 Signature algorithm name: SHA384withRSA
		 Version: 3

	## 根证书
	Alias name: addtrustqualifiedca [jdk]
	Creation date: 25 Aug, 2016
	Entry type: trustedCertEntry

	Owner: CN=AddTrust Qualified CA Root, OU=AddTrust TTP Network, O=AddTrust AB, C=SE
	Issuer: CN=AddTrust Qualified CA Root, OU=AddTrust TTP Network, O=AddTrust AB, C=SE
	Serial number: 1
	Valid from: Tue May 30 16:14:50 IST 2000 until: Sat May 30 16:14:50 IST 2020
	Certificate fingerprints:
		 MD5:  27:EC:39:47:CD:DA:5A:AF:E2:9A:01:65:21:A9:4C:BB
		 SHA1: 4D:23:78:EC:91:95:39:B5:00:7F:75:8F:03:3B:21:1E:C5:4D:8B:CF
		 SHA256: 80:95:21:08:05:DB:4B:BC:35:5E:44:28:D8:FD:6E:C2:CD:E3:AB:5F:B9:7A:99:42:98:8E:B8:F4:DC:D0:60:16
		 Signature algorithm name: SHA1withRSA
		 Version: 3

那就去抓SSL包吧。

请求包：

	Secure Sockets Layer
		TLSv1.2 Record Layer: Handshake Protocol: Client Hello
			Content Type: Handshake (22)
			Version: TLS 1.2 (0x0303)
			Length: 229
			Handshake Protocol: Client Hello
				Handshake Type: Client Hello (1)
				Length: 225
				Version: TLS 1.2 (0x0303)
				Random: 5af01897734438e606e3342398727fe8a539522a2ef0dfa6...
					GMT Unix Time: May  7, 2018 17:12:55.000000000 中国标准时间
					Random Bytes: 734438e606e3342398727fe8a539522a2ef0dfa6b698a1eb...
				Session ID Length: 0
				Cipher Suites Length: 58
				Cipher Suites (29 suites)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA256 (0xc023)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA256 (0xc027)
					Cipher Suite: TLS_RSA_WITH_AES_128_CBC_SHA256 (0x003c)
					Cipher Suite: TLS_ECDH_ECDSA_WITH_AES_128_CBC_SHA256 (0xc025)
					Cipher Suite: TLS_ECDH_RSA_WITH_AES_128_CBC_SHA256 (0xc029)
					Cipher Suite: TLS_DHE_RSA_WITH_AES_128_CBC_SHA256 (0x0067)
					Cipher Suite: TLS_DHE_DSS_WITH_AES_128_CBC_SHA256 (0x0040)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA (0xc009)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA (0xc013)
					Cipher Suite: TLS_RSA_WITH_AES_128_CBC_SHA (0x002f)
					Cipher Suite: TLS_ECDH_ECDSA_WITH_AES_128_CBC_SHA (0xc004)
					Cipher Suite: TLS_ECDH_RSA_WITH_AES_128_CBC_SHA (0xc00e)
					Cipher Suite: TLS_DHE_RSA_WITH_AES_128_CBC_SHA (0x0033)
					Cipher Suite: TLS_DHE_DSS_WITH_AES_128_CBC_SHA (0x0032)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256 (0xc02b)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256 (0xc02f)
					Cipher Suite: TLS_RSA_WITH_AES_128_GCM_SHA256 (0x009c)
					Cipher Suite: TLS_ECDH_ECDSA_WITH_AES_128_GCM_SHA256 (0xc02d)
					Cipher Suite: TLS_ECDH_RSA_WITH_AES_128_GCM_SHA256 (0xc031)
					Cipher Suite: TLS_DHE_RSA_WITH_AES_128_GCM_SHA256 (0x009e)
					Cipher Suite: TLS_DHE_DSS_WITH_AES_128_GCM_SHA256 (0x00a2)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_3DES_EDE_CBC_SHA (0xc008)
					Cipher Suite: TLS_ECDHE_RSA_WITH_3DES_EDE_CBC_SHA (0xc012)
					Cipher Suite: TLS_RSA_WITH_3DES_EDE_CBC_SHA (0x000a)
					Cipher Suite: TLS_ECDH_ECDSA_WITH_3DES_EDE_CBC_SHA (0xc003)
					Cipher Suite: TLS_ECDH_RSA_WITH_3DES_EDE_CBC_SHA (0xc00d)
					Cipher Suite: TLS_DHE_RSA_WITH_3DES_EDE_CBC_SHA (0x0016)
					Cipher Suite: TLS_DHE_DSS_WITH_3DES_EDE_CBC_SHA (0x0013)
					Cipher Suite: TLS_EMPTY_RENEGOTIATION_INFO_SCSV (0x00ff)
				
响应包：

	Secure Sockets Layer
		TLSv1.2 Record Layer: Alert (Level: Fatal, Description: Handshake Failure)
			Content Type: Alert (21)
			Version: TLS 1.2 (0x0303)
			Length: 2
			Alert Message
				Level: Fatal (2)
				Description: Handshake Failure (40)


客户端发送了Hello，服务器翻了个白眼直接拒绝了。这个响应包，没有任何线索。但是能明确一点就是客户端和服务器都通过TLS 1.2协议协商。不用再怀疑协议版本问题了。

联想到浏览器访问对方https系统是成功的，再次抓取浏览器请求和响应包。发现了一点有用的线索。

浏览器的请求包同样采用TLS 1.2协议，但是加密套件 (Cipher Suites)和前面的差异很大，提供了17个可选加密套件。前面的包提供了29个可选列表。


	Secure Sockets Layer
		TLSv1.2 Record Layer: Handshake Protocol: Client Hello
			Content Type: Handshake (22)
			Version: TLS 1.0 (0x0301)
			Length: 512
			Handshake Protocol: Client Hello
				Handshake Type: Client Hello (1)
				Length: 508
				Version: TLS 1.2 (0x0303)
				Random: 7179caea804a5281b411adca67c11883f616e13e13756d0d...
					GMT Unix Time: May  1, 2030 03:20:10.000000000 中国标准时间
					Random Bytes: 804a5281b411adca67c11883f616e13e13756d0d5764d936...
				Session ID Length: 32
				Session ID: 324885cac7ecf4dd09e38acdd3e45ceb2e0c5e248b31d267...
				Cipher Suites Length: 34
				Cipher Suites (17 suites)
					Cipher Suite: Reserved (GREASE) (0xcaca)
					Cipher Suite: TLS_AES_128_GCM_SHA256 (0x1301)
					Cipher Suite: TLS_AES_256_GCM_SHA384 (0x1302)
					Cipher Suite: TLS_CHACHA20_POLY1305_SHA256 (0x1303)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256 (0xc02b)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256 (0xc02f)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_AES_256_GCM_SHA384 (0xc02c)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384 (0xc030)
					Cipher Suite: TLS_ECDHE_ECDSA_WITH_CHACHA20_POLY1305_SHA256 (0xcca9)
					Cipher Suite: TLS_ECDHE_RSA_WITH_CHACHA20_POLY1305_SHA256 (0xcca8)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA (0xc013)
					Cipher Suite: TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA (0xc014)
					Cipher Suite: TLS_RSA_WITH_AES_128_GCM_SHA256 (0x009c)
					Cipher Suite: TLS_RSA_WITH_AES_256_GCM_SHA384 (0x009d)
					Cipher Suite: TLS_RSA_WITH_AES_128_CBC_SHA (0x002f)
					Cipher Suite: TLS_RSA_WITH_AES_256_CBC_SHA (0x0035)
					Cipher Suite: TLS_RSA_WITH_3DES_EDE_CBC_SHA (0x000a)
				
接着看浏览器接收到的tcp包Server Hello。服务器通过TLS 1.2协议协商，告诉浏览器它要使用 TLS_RSA_WITH_AES_256_GCM_SHA384 加密套件作为后续数据的加密算法。

	Secure Sockets Layer
		TLSv1.2 Record Layer: Handshake Protocol: Server Hello
			Content Type: Handshake (22)
			Version: TLS 1.2 (0x0303)
			Length: 81
			Handshake Protocol: Server Hello
				Handshake Type: Server Hello (2)
				Length: 77
				Version: TLS 1.2 (0x0303)
				Random: f1649150aeb3366381e54392bfdb8f49ae8ead9f47dbcb1f...
					GMT Unix Time: May  3, 2098 04:10:56.000000000 中国标准时间
					Random Bytes: aeb3366381e54392bfdb8f49ae8ead9f47dbcb1fc13f95a4...
				Session ID Length: 32
				Session ID: 264c4906bbd0fd2b8f94f66ea2992433b82690fb7fb844d7...
				Cipher Suite: TLS_RSA_WITH_AES_256_GCM_SHA384 (0x009d)
				Compression Method: null (0)
				Extensions Length: 5
				Extension: renegotiation_info (len=1)
					Type: renegotiation_info (65281)
					Length: 1
					Renegotiation Info extension
						Renegotiation info extension length: 0

马上注意到在前面的请求包中，29个加密套件里都没有TLS_RSA_WITH_AES_256_GCM_SHA384。似乎问题就在这里，就是通过java代码访问https服务器时，候选的加密套件中没有服务器希望的TLS_RSA_WITH_AES_256_GCM_SHA384。

真的是这样吗？为了弄清服务器和本地JRE是否存在加密套件不匹配，还得使用有足够说服力的诊断方法来验证。

首先搬上OPENSSL来调试。

	openssl s_client -connect server.mycompany.com:443

诊断发现，服务器确实需要AES256-GCM-SHA384，这是一个很长长度的强加密，一般128位长度加密很牛逼了。

	New, TLSv1.2, Cipher is AES256-GCM-SHA384
	Server public key is 2048 bit
	Secure Renegotiation IS supported
	Compression: NONE
	Expansion: NONE
	No ALPN negotiated
	SSL-Session:
		Protocol  : TLSv1.2
		Cipher    : AES256-GCM-SHA384
		Session-ID: 5BFE44E0BE1248156266BE6947FA113C1035DDDC3A3BD1888940EF8257CAA18C
	
	
对Java代码也来一次调试，确认它所支持的所有加密套件。

	-Dssl.debug=true -Djavax.net.debug=all

返回结果确实如此，根本就不存在TLS_RSA_WITH_AES_256_GCM_SHA384，也不存在基于AES256-GCM-SHA384的加密方法。

	Cipher Suites: [
		TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA256, 
		TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA256, 
		TLS_RSA_WITH_AES_128_CBC_SHA256, 
		TLS_ECDH_ECDSA_WITH_AES_128_CBC_SHA256, 
		TLS_ECDH_RSA_WITH_AES_128_CBC_SHA256, 
		TLS_DHE_RSA_WITH_AES_128_CBC_SHA256, 
		TLS_DHE_DSS_WITH_AES_128_CBC_SHA256, 
		TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA, 
		TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA, 
		TLS_RSA_WITH_AES_128_CBC_SHA, 
		TLS_ECDH_ECDSA_WITH_AES_128_CBC_SHA, 
		TLS_ECDH_RSA_WITH_AES_128_CBC_SHA, 
		TLS_DHE_RSA_WITH_AES_128_CBC_SHA, 
		TLS_DHE_DSS_WITH_AES_128_CBC_SHA, 
		TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256, 
		TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256, 
		TLS_RSA_WITH_AES_128_GCM_SHA256, 
		TLS_ECDH_ECDSA_WITH_AES_128_GCM_SHA256, 
		TLS_ECDH_RSA_WITH_AES_128_GCM_SHA256, 
		TLS_DHE_RSA_WITH_AES_128_GCM_SHA256, 
		TLS_DHE_DSS_WITH_AES_128_GCM_SHA256, 
		TLS_ECDHE_ECDSA_WITH_3DES_EDE_CBC_SHA, 
		TLS_ECDHE_RSA_WITH_3DES_EDE_CBC_SHA, 
		SSL_RSA_WITH_3DES_EDE_CBC_SHA, 
		TLS_ECDH_ECDSA_WITH_3DES_EDE_CBC_SHA, 
		TLS_ECDH_RSA_WITH_3DES_EDE_CBC_SHA, 
		SSL_DHE_RSA_WITH_3DES_EDE_CBC_SHA, 
		SSL_DHE_DSS_WITH_3DES_EDE_CBC_SHA, 
		TLS_EMPTY_RENEGOTIATION_INFO_SCSV
		]
	
问题找到了。解决方法看来现在只能回到JRE本身的加密授权问题上来了。

Java支持所有的加密套件，但是对于发行的JDK版本，它默认做了很多加密长度限制的裁剪，就是只出口强度低的加密，这是美国政府对于安全软件的强制性规定。但Oracle允许下载强加密的未限制版本，其实就是几个授权属性文件，因为源代码都在发行的JDK中。

当前问题发生在JDK1.8中，所以可以去官网下载一个压缩包叫做 “Java Cryptography Extension (JCE) Unlimited Strength Jurisdiction Policy Files 8”。

对于JDK1.8版本但是低于1.8.0_151版本的JDK，将下载的包里的两个文件直接覆盖到本地 Java\jre\lib\security\

	local_policy.jar
	US_export_policy.jar
  
1.8.0_151和以后的版本，无需下载任何文件，只要修改Java\jre\lib\security\java.security文件，修改这一行注释并启用就可以了。

	crypto.policy=unlimited
	
