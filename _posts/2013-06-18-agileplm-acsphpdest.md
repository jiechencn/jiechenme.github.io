---
title: Develop PHP HTTP Destination for Agile PLM ACS
author: Jie Chen
date: 2013-06-18
categories: [AgilePLM]
tags: [sdk]
---

I seldom see customer use HTTP Destination for Agile ACS because of its code difficulty. But someone ever asked this question about how to code PHP program as the HTTP destination to process ACS data from Agile PLM. In our Agile PLM ACS User Guide, there is only one sample HTTP head data for reference like below.

![](/assets/res/troubleshooting-agileplm-acsdest-1.jpg)

It is very abstract to understand and to develop the code. I will discuss this with details. After you read this article, you will also know how to write different HTTP Destination code with other languages like JSP and Asp.Net.

## Analyze HTTP Head

First we setup a HTTP Destination in Agile JavaClient like below. There are something important I need to emphasize.

![](/assets/res/troubleshooting-agileplm-acsdest-2.png)

### URL

I use axis tcpmonitor as the HTTP Destination first because I need to collect all the HTTP head from Agile to see how it looks like. axis tcpmonitor is a open source tool and you can download from internet. Also attention there is a question mark in the end of URL. That is because Agile has a very small defect on this.

### Response Expected

Usually you can set it to No. If set to Yes, then you will be in a big trouble to send the expected Response data back to Agile. The Response is not HTTP Return Code. It is Agile specific.

### Request File Field

This field is used to simulate an HTML form element of below: 

![](/assets/res/troubleshooting-agileplm-acsdest-3.png)

So "acsFile" element is the key of File object in Post data. 

### Additional Parameters

You can use $_POST["para"] to get the posted data in httpreader.php

Now let me trigger an ATO and transfer to HTTP Destination (It actually goes to axis tcpmonitor). After ATO is sent, we get the Head data now.

	==== Request ====
	POST /httpreader.php HTTP/1.1
	User-Agent: Jakarta Commons-HttpClient/3.0
	Host: xxxxx.xxx.com:80
	Content-Length: 2319
	Content-Type: multipart/form-data; boundary=----------------314159265358979323846

	------------------314159265358979323846
	Content-Disposition: form-data; name="acsFile"; filename="TO0000557926.AXML"
	Content-Type: application/octet-stream; charset=ISO-8859-1
	Content-Transfer-Encoding: binary

	xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
	xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

	------------------314159265358979323846
	Content-Disposition: form-data; name="para2"
	Content-Type: text/plain; charset=US-ASCII
	Content-Transfer-Encoding: 8bit

	myvalue2
	------------------314159265358979323846
	Content-Disposition: form-data; name="para1"
	Content-Type: text/plain; charset=US-ASCII
	Content-Transfer-Encoding: 8bit

	myvalue1
	------------------314159265358979323846
	Content-Disposition: form-data; name="AgileRecordLocator"
	Content-Type: text/plain; charset=US-ASCII
	Content-Transfer-Encoding: 8bit
	AAAt8QBj0vcAAC6TACW+uAAgRTJDRUVCNDc0MjAyNDlBOEI1MkYwRkUwQjJGOUFEQUU=

	------------------314159265358979323846--


Above Head data is real data that is sent from Agile system. We need to pay attention to these Post elements:

1. POST

	First, it is POST, not GET

2. Content-Disposition: form-data; name="acsFile"; filename="TO0000557926.AXML" 

	The File object is acsFile and filename is present in data. Then there is a very long binary characters of "xxxxxxxxxxxxxxxxx". It stands for the File stream.

3. Content-Disposition: form-data; name="para1" Content-Disposition: form-data; name="para2" 

	para1 and para2 are customized by myself in Agile JavaClient and you can see the value are also present here: myvalue1, myvalue2

4. Content-Disposition: form-data; name="AgileRecordLocator"

	AgileRecordLocator is the unique identifier pointing to the specific ATO object. Each ATO object has unique AgileRecordLocator. In this example it is "AAAt8QBj0vcAAC6TACW+uAAgRTJDRUVCNDc0MjAyNDlBOEI1MkYwRkUwQjJGOUFEQUU="

## httpreader.php to process ATO data

If you understand how to write PHP code then you know below my code is to write the ATO data to a TO0000557926.AXML to "upload" directory.

![](/assets/res/troubleshooting-agileplm-acsdest-4.png)

## Response Expected

This is very complicated. The Agile's expected response is not HTTP return code. Attention again, not HTTP return code like HTTP 200, HTTP 401 or HTTP 500. The correct expected response is an xml package from web service. First let's look at this Agile Web Service about ResponseService from below link:

http://agile-server:port/Agile/integration/services/ResponseService?wsdl

![](/assets/res/troubleshooting-agileplm-acsdest-5.png)

From above screenshot, we will know the operation of WebServce is acceptResponse and the input parameter is acceptResponseRequest which is a map of "recordLocator" (String), "accept" (boolean) and "msg"(String). So we need to combine such map into PHP array.

	$params = array(
		'recordLocator'=>"AAAt8QBj0vcAAC6TACW+uAAgRTJDRUVCNDc0MjAyNDlBOEI1MkYwRkUwQjJGOUFEQUU=",
		'accept'=>true,
		'msg'=>"OK"
	);

To send a webservice request from PHP, I use NuSOAP open source.

Before call the operation of "acceptResponse", the credential data must be included to authenticate current request.

	$client->setCredentials($username,$password);

And now we put the code together and we have below PHP file response.php .

![](/assets/res/troubleshooting-agileplm-acsdest-6.png)

Attention, this PHP code must be one single file like response.php. That is to say, when Agile sends ACS data to httpreader.php and the ATO package file (PDX or AXML) is created on PHP web server, you need to run response.php manually. You may have to try to figure out how to trigger response.php automatically to send back Response (Accept or Reject) to Agile with the unique AgileRecourdLocator from ATO. There would be many ways, and you please think about it.

Pay attention with two issues:

1. Do not put response.php code into httpreader.php, otherwise you will get below error, that is because to update Response status back to Agile cannot work in synchronization mode.

		com.agile.admin.client.value.AdminException: Please cancel this operation and refresh since a newer version of this object is available.
			at com.agile.admin.server.ADDAO.checkNodeVersion(ADDAO.java:247)
			at com.agile.admin.server.ADDAO.updateProperty(ADDAO.java:3059)
			at com.agile.admin.server.ADPropSSList.updateProperty(ADPropSSList.java:129)
			at com.agile.acs.PCExtractTask.setStatusSuccess(PCExtractTask.java:1835)
			at com.agile.acs.PCExtractTask.transmitPayload(PCExtractTask.java:1733)
			at com.agile.acs.PCExtractTask.tmpProcessWS(PCExtractTask.java:1530)
			at com.agile.acs.PCExtractTask.tmpExtractionProcess(PCExtractTask.java:758)
			at com.agile.acs.PCExtractTask.run(PCExtractTask.java:442)
			at java.util.TimerThread.mainLoop(Timer.java:512)
			at java.util.TimerThread.run(Timer.java:462)
	
2. If "Response Expected" is set to Yes in JavaClient, but you forget to run response.php, the ATO will always be in "Release" status and the "Transmission Status" will be "Pending" forever.

