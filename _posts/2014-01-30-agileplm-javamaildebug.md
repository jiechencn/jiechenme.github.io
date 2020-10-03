---
title: mail.debug to collect JavaMail log from Notification
author: Jie Chen
date: 2014-01-30
categories: [AgilePLM]
tags: []
---

You may always have email problem from Agile that users are able to get Inbox mail on Agile UI, but fail to get it through email, it happens randomly, sometime users receive and sometime not. And you see error "Actual Exception : null" from server log like below.

  	Actual Exception : null
	at com.agile.common.server.notification.SendEmail.send(SendEmail.java:307)
	at com.agile.common.server.notification.SendEmailListener.onMessage(SendEmailListener.java:43)
	at weblogic.ejb.container.internal.MDListener.execute(MDListener.java:585)
	at weblogic.ejb.container.internal.MDListener.transactionalOnMessage(MDListener.java:488)
	at weblogic.ejb.container.internal.MDListener.onMessage(MDListener.java:385)
	at weblogic.jms.client.JMSSession.onMessage(JMSSession.java:4659)
	at weblogic.jms.client.JMSSession.execute(JMSSession.java:4345)
	at weblogic.jms.client.JMSSession.executeMessage(JMSSession.java:3821)

What does above email error mean? Nothing, absolutely nothing that makes sense. Then you may consult Oracle Agile Support to diagnose. You get instructions:

* Enable Email Notification Debug for com.agile.pc.cmserver.notification and com.agile.common.server.notification
* Use Telnet to verify the functionality of SMTP

It most of cases they are helpful and help you quickly find out the cause. But in complicated case that happens randomly, they will not.

## JavaMail

One year ago I wrote a Document article in My Oracle Support about how to make Agile email to SMTP at non-default port 25. Setup SMTP Server Port To A Value Other Than The Default 25, To Receive Email Notifications From Agile Server (Doc ID 1468816.1)

Then if you think more, you may ask what other options we can set to JVM parameters to extend JavaMail's function. Agile invokes the simplest function (Transport.send) of JavaMail to deliver the email entry to SMTP without Authentication (Relay is used), so definitely you can use these options listed in this link. https://javamail.java.net/nonav/docs/api/


## mail.debug

What I introduce here is to use mail.debug option to enable the debugging of email, including the handshake, authentication, delivery and disconnection.

	DEBUG: JavaMail version 1.4.1
	DEBUG: not loading file: /usr/java/jdk1.7.0_21/jre/lib/javamail.providers
	DEBUG: java.io.FileNotFoundException: /usr/java/jdk1.7.0_21/jre/lib/javamail.providers (No such file or directory)
	DEBUG: !anyLoaded
	DEBUG: not loading resource: /META-INF/javamail.providers
	DEBUG: successfully loaded resource: /META-INF/javamail.default.providers
	DEBUG: Tables of loaded providers
	DEBUG: Providers Listed By Class Name: {com.sun.mail.smtp.SMTPSSLTransport=javax.mail.Provider[TRANSPORT,smtps,com.sun.mail.smtp.SMTPSSLTransport,Sun Microsystems, Inc], com.sun.mail.smtp.SMTPTransport=javax.mail.Provider[TRANSPORT,smtp,com.sun.mail.smtp.SMTPTransport,Sun Microsystems, Inc], com.sun.mail.imap.IMAPSSLStore=javax.mail.Provider[STORE,imaps,com.sun.mail.imap.IMAPSSLStore,Sun Microsystems, Inc], com.sun.mail.pop3.POP3SSLStore=javax.mail.Provider[STORE,pop3s,com.sun.mail.pop3.POP3SSLStore,Sun Microsystems, Inc], com.sun.mail.imap.IMAPStore=javax.mail.Provider[STORE,imap,com.sun.mail.imap.IMAPStore,Sun Microsystems, Inc], com.sun.mail.pop3.POP3Store=javax.mail.Provider[STORE,pop3,com.sun.mail.pop3.POP3Store,Sun Microsystems, Inc]}
	DEBUG: Providers Listed By Protocol: {imaps=javax.mail.Provider[STORE,imaps,com.sun.mail.imap.IMAPSSLStore,Sun Microsystems, Inc], imap=javax.mail.Provider[STORE,imap,com.sun.mail.imap.IMAPStore,Sun Microsystems, Inc], smtps=javax.mail.Provider[TRANSPORT,smtps,com.sun.mail.smtp.SMTPSSLTransport,Sun Microsystems, Inc], pop3=javax.mail.Provider[STORE,pop3,com.sun.mail.pop3.POP3Store,Sun Microsystems, Inc], pop3s=javax.mail.Provider[STORE,pop3s,com.sun.mail.pop3.POP3SSLStore,Sun Microsystems, Inc], smtp=javax.mail.Provider[TRANSPORT,smtp,com.sun.mail.smtp.SMTPTransport,Sun Microsystems, Inc]}
	DEBUG: successfully loaded resource: /META-INF/javamail.default.address.map
	DEBUG: !anyLoaded
	DEBUG: not loading resource: /META-INF/javamail.address.map
	DEBUG: not loading file: /usr/java/jdk1.7.0_21/jre/lib/javamail.address.map
	DEBUG: java.io.FileNotFoundException: /usr/java/jdk1.7.0_21/jre/lib/javamail.address.map (No such file or directory)

When JavaMail is first time referenced, it will initialize all the default configuration from JRE, like you see from above log. It has nothing to do with Agile so let's leave it alone.

Then JavaMail will try to connect to remote SMTP at specified port 25. Default authentication is false.

	DEBUG: getProvider() returning javax.mail.Provider[TRANSPORT,smtp,com.sun.mail.smtp.SMTPTransport,Sun Microsystems, Inc]
	DEBUG SMTP: useEhlo true, useAuth false
	DEBUG SMTP: trying to connect to host "dummy-smtp-server.oracle.com", port 25, isSSL false
	220-dummy-smtp-server-proxy.oracle.com ESMTP Oracle Corporation - Unauthorized Use Prohibited 
	220 Ready at Thu, 30 Jan 2014 03:42:32 GMT
	DEBUG SMTP: connected to host "dummy-smtp-server.oracle.com", port: 25

Next, JavaMail simulates the TELNET method to handshake with SMTP server. It verifies the sender and recipient if they are allowed to relay (internal user sends to internal user, internal user sends to external user, or external user sends to internal user).

	EHLO agileServerhost
	250-dummy-smtp-server-proxy.oracle.com Hello agileServerhost.oracle.com [1xx.1xx.1xx.1xx], pleased to meet you
	250-ENHANCEDSTATUSCODES
	250-PIPELINING
	250-8BITMIME
	250-SIZE 14680064
	250-STARTTLS
	250-DELIVERBY
	250 HELP
	DEBUG SMTP: Found extension "ENHANCEDSTATUSCODES", arg ""
	DEBUG SMTP: Found extension "PIPELINING", arg ""
	DEBUG SMTP: Found extension "8BITMIME", arg ""
	DEBUG SMTP: Found extension "SIZE", arg "14680064"
	DEBUG SMTP: Found extension "STARTTLS", arg ""
	DEBUG SMTP: Found extension "DELIVERBY", arg ""
	DEBUG SMTP: Found extension "HELP", arg ""
	DEBUG SMTP: use8bit false
	MAIL FROM:
	250 2.1.0 ... Sender ok
	RCPT TO:
	250 2.1.5 ... Recipient ok
	DEBUG SMTP: Verified Addresses
	DEBUG SMTP:   "jie, chen (chenjie)" 

After that, it deliver the email message entry.

	DATA
	354 Enter mail, end with "." on a line by itself
	Date: Thu, 30 Jan 2014 03:41:53 +0000 (GMT)
	From: test@oracle.com
	Reply-To: test@oracle.com
	To: "jie, chen (chenjie)" 
	Message-ID: <395344347.0.1391053313086.JavaMail.oracle@slag9320w5c>
	Subject: Administrator (admin) has sent admin to you
	MIME-Version: 1.0
	Content-Type: text/plain ; charset="UTF-8"
	Content-Transfer-Encoding: 7bit

	admin has been sent to you by Administrator (admin).

	Comments from Administrator (admin):
	test 123

	Access the following URL to see admin:
	http://agileServerhost.oracle.com:80/Agile/PLMServlet?action=OpenEmailObject&classid=11605&objid=704

	Sent by: Administrator (admin)
			
	.
	250 2.0.0 s0U3gWNJ015603 Message accepted for delivery

And finally quit.

	QUIT
	221 2.0.0 dummy-smtp-server-proxy.oracle.com closing connection


## Scenario

Below is two sample error detected by JavaMail DEBUG.

* 

		javax.mail.SendFailedException: Invalid Addresses;
		nested exception is:
		class javax.mail.SendFailedException: 550 5.7.1 Unable to relay

* 

		454 4.7.0 Temporary authorization failure
		221 2.0.0 Service closing transmission channel
		DEBUG SMTP: QUIT failed with 454


