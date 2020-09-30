---
title: Agile File Server impacted by TCP Compression Solution
author: Jie Chen
date: 2013-07-15
categories: [AgilePLM]
tags: [tcp]
---

Usually, Agile JavaClient is only used internally. Here "internally" I especially means the LAN in same physical location. I do not suggest it to be "externally" used across WAN or VPN. That is because JavaClient is a fat client, each EJB invockation involves too much redundant data in TCP packages. And you will see delay in JavaClient if you intend to use externally, the delay includes slow login, slow response of tab click. However there is still some customers using it externally and their feedback is JavaClient works very fast in WAN like in LAN, with some special network technology. Customers are happy with that, but issue comes...

There is one typical case that Agile PLM and main File Server is deployed in Taiwan, the distributed File Server is deployed in Shanghai. All the users in Taiwan, Shanghai and Beijing (these three locations are physically far away) are using JavaClient, they do not like WebClient. Everything is OK except many Beijing users have issue to download files from Taiwan main File Server. Administrator sees below error in Tomcat log.

	AxisFault
	 faultCode: {http://xml.apache.org/axis/}HTTP
	 faultSubcode: 
	 faultString: (0)null
	 faultActor: 
	 faultNode: 
	 faultDetail: 
		{}:return code:  0
		{http://xml.apache.org/axis/}HttpErrorCode:0
	(0)null
		at org.apache.axis.transport.http.HTTPSender.readFromSocket(HTTPSender.java:712)
		at org.apache.axis.transport.http.HTTPSender.invoke(HTTPSender.java:172)
		... ...
		at com.agile.webfs.components.fileserver.client.FileServerSoapBindingStub.ping(FileServerSoapBindingStub.java:449)
		at com.agile.webfs.client.IFSLocator.getRemoteFileServer(IFSLocator.java:127)
		at com.agile.webfs.client.IFSLocator.getConnection(IFSLocator.java:95)
	
It happens randomly, and succeeds if Beijing users try more. We checked everything on Agile Server and File Server setting. When issue comes to me, the performance of JavaClient catches my attention. How could be JavaClient fast to connect remote Agile Server far away? After taking TCPDUMP we get some strange TCP data.

First let's analyze the correct TCP data for a Success case on a File Server (not on client machine).

![](/assets/res/troubleshooting-agileplm-tcpcompress-1.jpg)
 
Package 29860 shows the client 10.241.64.99 sends a POST to File Server 10.241.64.234, the File Server does not send back immediate response. Instead, it sends GET to Agile Server 10.241.64.60 and tries to get user authenticated in SSOAuthServlet. Agile Server sends back HTTP 200 and asks the File Server to re-authenticate in redirected j_security_check. After getting HTTP 200 and authentication at package 30000, File Server finally send back HTTP 200 to the original request from client 10.241.64.99 at package 30015, then at package 37900, client sends CheckOutServlet request and begins to download files at package 37936. If we look at the Stream Content between TCP Flow of package 29860, we see the Request and Response clearly. The Red words are Request from client and Blue words are response from File Server at package 30015.

![](/assets/res/troubleshooting-agileplm-tcpcompress-2.jpg)

If we hide all other sessions between File Server and Agile Server, the client and File Server communication is quite simple like in below two screenshots.

![](/assets/res/troubleshooting-agileplm-tcpcompress-3.jpg)
![](/assets/res/troubleshooting-agileplm-tcpcompress-4.jpg)
 
Now we go back to the Failure case and check the TCP on client machine (not on File Server machine). Below screenshot shows Beijing user client (10.17.11.93) sends a POST request to remote File Server 172.20.168.48 at package 8627. Then there is no feedback forever. Check the TCP Steam Content, no response in blue color.

![](/assets/res/troubleshooting-agileplm-tcpcompress-5.jpg)
![](/assets/res/troubleshooting-agileplm-tcpcompress-6.jpg)
 
When we check the TCP on File Server 172.20.168.48, we see abnormal data. Package 93848 sends Reset and combines Acknowledge to client machine in order to close the TCP session. It is not a correct behavior. Then client sends Finish and Acknowledge as well. The Session is closed with Reset at wrong package 93850.

![](/assets/res/troubleshooting-agileplm-tcpcompress-7.jpg)
![](/assets/res/troubleshooting-agileplm-tcpcompress-8.jpg)
![](/assets/res/troubleshooting-agileplm-tcpcompress-9.jpg)
 
I am confused by that until I read the TCP Stream Content of package 93847. It contains below:

![](/assets/res/troubleshooting-agileplm-tcpcompress-10.jpg)

It gives me a supposition. The original package 93847 is inserted a wrong HTTP head Keep-Alive, which will make the http session alive in a given time period of Keep-Alive, and also it is not required for such a HTTP session as we see in a Success case, that is why the remote File Server sends back Reset to request to close the session. But where is "Keep-Alive" from?

	Connection: Keep-Alive
	X-RBT-Optimized-By: CQ-Riverbed (RiOS 7.0.6) IK

Riverbed is a TCP Compression solution, especially for WAN to pack huge TCP package, compress and optimize it during transmission across faraway remote network. No wonder that customer has best performance for remote JavaClient like in local computing. In some cases it has a command "IK" in the HTTP head, it means "Insert-Keep-Alive" and automatically insert Keep-Alive field into HTTP head.

![](/assets/res/troubleshooting-agileplm-tcpcompress-11.jpg)
 
Check [Riverbed user guide](https://support.riverbed.com/download.htm?filename=public/doc/steelhead/7.0.3/cli.pdf "" "_blank")

Now we find the root cause and if we exclude the File Server IP from Riverbed, all users in remote Beijing are able to download files without any error.


