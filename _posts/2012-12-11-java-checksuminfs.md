---
title: 使用Checksum对文件校验
author: Jie Chen
date: 2012-12-11
categories: [Java]
tags: [checksum]
---


企业应用中有时会使用到文档类文件的上传和下载。为了保证文档在传输和存储过程中没有被恶意篡改过，就可以使用Checksum对文件进行校验。比如在上传文件的同时，计算出文件的Checksum，保存到数据库中。当其他用户需要下载该文件时，对服务器上保存的该文件进行第二次的Checksum计算并和数据库中的值进行比较验证。

对于文件的checksum校验有非常多的方法，常见的有SHA1, MD5和CRC32。在Agile PLM中，文件的Checksum使用CRC32。对CRC算法感兴趣的可以查看此文：
<a href="http://en.wikipedia.org/wiki/Cyclic_redundancy_check" target="_blank">http://en.wikipedia.org/wiki/Cyclic_redundancy_check</a>

由于checksum的计算需要消耗一定的时间，对于大文件，可能出现秒级的延迟。实验过程中，当上传729,207,676字节（约720M）大小的文件时，打印日志表明checksum的计算耗时2.078秒。下载相同大小的文件，checksum被重新计算，耗时为2.559秒。

上述计算都在Tomcat服务器的JVM中完成，因此适当提高文件服务器的CPU也是可以考虑的范围。


## 代码例子

Checksum可以运用在我们自己的应用程序开发中。使用java.util.zip.CRC32非常方便高效。下述代码演示了使用CRC32类来快速计算一个1,444,792,736字节（约1.2G）大小的文件的checksum值，耗时14342毫秒。必须注意的是此处的checksum value是个十进制。

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


运行结果：

	D:\Program\Java\jdk1.5.0_07\bin\java zigzag.research.checksum.ChecksumCalc
	checksum value=2151428387, time=14342ms


## 网络下载应用

checksum的校验在国外的各类下载应用中十分普遍，目的就是为了防范文件被恶意篡改。比如下面的一个文件下载提供了SHA1的校验值供用户检查文件是否合法。

![](/assets/res/agileplm-checksuminfs-02.jpg)

我们可以使用免费的checksum计算工具HashCalc，下载地址为： http://www.slavasoft.com/hashcalc/index.htm

以上述Java代码计算的checksum来做例子运行HashCalc，得到十六进制的803C3123，就是上述十进制的2151428387。

![](/assets/res/java-checksuminfs-03.jpg)

