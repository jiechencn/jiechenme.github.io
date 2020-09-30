---
title: Checksum in Agile File Server
author: Jie Chen
date: 2013-05-10
categories: [AgilePLM]
tags: [checksum]
---

We will discuss the concept of checksum in Agile PLM and related technology. If enabled Checksum Calculation in Agile PLM, we may see below error when do CheckOut/CheckIn file attachment. This scenario means the original physical file is modified by external program beyond Agile.

![](/assets/res/troubleshooting-agileplm-fileserverchecksum-1.jpg)

There are quite a few ways to calculate the checksum against file, for example SHA1, MD5 and CRC32. We Agile PLM use CRC32 to verify file. More information of detailed algorithm and concept of CRC32 please read this document: 
http://en.wikipedia.org/wiki/Cyclic_redundancy_check


## File Checksum in Agile

Upon uploading file attachment each time, the new calculated checksum value will be saved to table file_info (column checksum_value) in database. Before Get/Checkout, Agile calculates the checksum value against phifical file in File Manager vault and compares it with the one in database.

Checksum calculation requires additional CPU time. For huge file Agile may delay few seconds (you may or may not notice the delay because of human's sense). Let's enable the DEBUG option for File Manager and monitor the delayed timestamps. 

When to upload 729,207,676 bytes file(about 720M) log shows checksum takes 2.078 seconds

	<2012-12-09 19:51:23,545>Entering updateChecksum => File ID:6020222 Vault Type :Standard Vault : Primary VaultRelativeFilePath :000/060/202/AGILE_16020222.zip
	<2012-12-09 19:51:23,559>Inside postCheckIn =>ServerContext:com.agile.webfs.components.security.ServerContext@d306dd File ID :6020222Checksum value :0 EIFS filepath :null IFS filepath :000/060/202/AGILE_16020222.zip HFS filepath :null Locations :http://localhost:8080/Filemgr/services/FileServer File type :zip
	<2012-12-09 19:51:23,559>Checksum Enabled:true
	<2012-12-09 19:51:25,637>Computed checksum value: 3309565842
	<2012-12-09 19:51:25,637>Action:Checksum::postCheckIn Time Taken:2.078 secs
	<2012-12-09 19:51:25,637>Adding file information fileID :6020222 EIFS filepath :null IFS filepath :000/060/202/AGILE_16020222.zip HFS filepath :null Locations :http://localhost:8080/Filemgr/services/FileServer File type :zipPersistence Level :1
	<2012-12-09 19:51:25,637>Action:EventDispatcher::postCheckIn Time Taken:2.078 secs
	<2012-12-09 19:51:25,637>Action:Vault:: updateChecksum Time Taken:2.092 secs
	<2012-12-09 19:51:25,637>Leaving updateCheckSum

Download the same file, checksum will be calculated again and it taks 2.559 seconds.

	<2012-12-09 20:18:49,719>Checksum Enabled:true
	<2012-12-09 20:18:52,278>Computed Checksum value: 3309565842
	<2012-12-09 20:18:52,278>Action:Checksum::preCheckOut Time Taken:2.559 secs

Above calculations are done in Tomcat's JVM, it is recommended to have powerful CPU on Tomcat machine as well.


## Checksum in other applications

We can use Checksum in many application developments to meet the software (or business) requirement. It is very convenient to reference the API java.util.zip.CRC32 . Below sample code shows how to use this API to get the Checksum value of a huge file with 1,444,792,736 bytes(around 1.2G). It takes 14342 milliseconds. We only need to pay attention that the value here is denary.

	package zigzag.research.checksum;

	import java.io.*;
	import java.util.zip.CRC32;

	public class ChecksumCalc {
		public static void main(String args[]) {
			final int BUFFER_SIZE = 1024;
			byte[] buffer = new byte[BUFFER_SIZE];
			CRC32 checksum = new CRC32();
			InputStream is = null;
			int length;
			long begin = System.currentTimeMillis();
			long end;
			try {
				is = new FileInputStream(new File("d:\\java_pid3256.hprof"));
				checksum.reset();
				while ((length = is.read(buffer, 0, BUFFER_SIZE)) != -1) {
					checksum.update(buffer, 0, length);
				}
				end = System.currentTimeMillis();
				System.out.println("checksum value=" + checksum.getValue() + ", time=" + (end-begin) + "ms");

			} catch (Exception e) {
				e.printStackTrace();
			}

		}
	}

Execution Result：

	D:\Program\Java\jdk1.5.0_07\bin\java zigzag.research.checksum.ChecksumCalc
	checksum value=2151428387, time=14342ms


## Checksum in Software Download

It is widely used in software download website to avoid the original software is corrupted by virus or cracker. Below screenshot shows the zip file with a valid SHA1 Checksum value.

![](/assets/res/troubleshooting-agileplm-fileserverchecksum-2.jpg)

Then we can use some free utility to calculate the downloaded file's checksum and compare with the valid one shown in website. A useful tool is HashCalc and you please download from this link, http://www.slavasoft.com/hashcalc/index.htm

If use this tool, we can get above file's Checksum value and get hexadecimal 803C3123 which equals to denary 2151428387.

![](/assets/res/troubleshooting-agileplm-fileserverchecksum-3.jpg)




