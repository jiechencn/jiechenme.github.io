---
title: grep或sed快速定位大日志文件
author: Jie Chen
date: 2012-05-07
categories: [Linux]
tags: [Shell]
---

客户的系统日志常常几个G以上，全部拿过来再分析的话网络传输非常耗时间，而且里面大部分日志都是很早之前的。介绍有两个非常简单好用又快速的方法，只截取大文件中有用的部分数据。

## grep关键字的正则查询

我们总能知道一些错误日志的关键字。所以grep首先是最先想到。下面的过程就是怎么一步步优化grep命令，精确找到匹配项。

比如我们有个错误 “Node -1 does not exist in the cache”。找到它，很容易。
~~~
$ grep "^.\+Node -1 does not exist in the cache." stdout.log
~~~
双引号内为正则内容，^为开头字符。第一个和最后一个dot表示除了行尾句点之外的任意字符。

* ^ 以后边的字符内容作为行的开始
* .  除了行尾句点之外的任意字符
* \+ 前面的字符出现1次或者多次，需要转义

也就是说"Node -1 does not exist in the cache"是出现在一行的中间部分。

返回结果非常多。
~~~
com.agile.admin.client.value.AdminException: Node -1 does not exist in the cache. It may have been deleted already.
com.agile.admin.client.value.AdminException: Node -1 does not exist in the cache. It may have been deleted already.
com.agile.admin.client.value.AdminException: Node -1 does not exist in the cache. It may have been deleted already.
com.agile.admin.client.value.AdminException: Node -1 does not exist in the cache. It may have been deleted already.
...
~~~

对这个命令进行调整。我希望只检查最后一次出现。将grep结果通过管道传输给tail就可以。
~~~
$ grep "^.\+Node -1 does not exist in the cache." stdout.log | tail -n 1
~~~
找到关键行。
~~~
com.agile.admin.client.value.AdminException: Node -1 does not exist in the cache. It may have been deleted already.
~~~

最后一次出现的错误找到了。但是exception的栈中元素还没有显示。由于我不知道这个错误是处于Java exception栈顶还是异常链中的其中一节，所以我希望能找到错误前后各100行。使用grep的两个参数

* -A 100：模式被匹配到的前100行
* -B 100：模式被匹配到的后100行

~~~
$ grep -A 100 -B 100 "^.\+Node -1 does not exist in the cache." stdout.log | tail -n 201
~~~
~~~
        ...
        at weblogic.servlet.internal.WebAppServletContext.doSecuredExecute(WebAppServletContext.java:2163)
        at weblogic.servlet.internal.WebAppServletContext.securedExecute(WebAppServletContext.java:2089)
        at weblogic.servlet.internal.WebAppServletContext.execute(WebAppServletContext.java:2074)
        at weblogic.servlet.internal.ServletRequestImpl.run(ServletRequestImpl.java:1513)
        at weblogic.servlet.provider.ContainerSupportProviderImpl$WlsRequestExecutor.run(ContainerSupportProviderImpl.java:254)
        at weblogic.work.ExecuteThread.execute(ExecuteThread.java:256)
        at weblogic.work.ExecuteThread.run(ExecuteThread.java:221)
com.agile.admin.client.value.AdminException: Node -1 does not exist in the cache. It may have been deleted already.
        at com.agile.admin.server.ADictionary.getNodeByID(ADictionary.java:221)
        at com.agile.pc.cmserver.notification.CMNotifyService.getStatusType(CMNotifyService.java:246)
        at com.agile.pc.cmserver.notification.CMNotifyService.getTemplateId(CMNotifyService.java:255)
        at com.agile.pc.cmserver.notification.ChangeHistoryService.processChangeHistory(ChangeHistoryService.java:216)
        at com.agile.pc.cmserver.notification.ChangeHistoryService.processChangeHistories(ChangeHistoryService.java:119)
        at com.agile.pc.cmserver.notification.NotifyTask.processChangeHistory(NotifyTask.java:518)
        at com.agile.pc.cmserver.notification.NotifyTask.run(NotifyTask.java:214)
		...
~~~
		
## sed区间查找

如果不知道错误字符串，可以采用sed方式。基本上所有的日志文件都是带时间戳的。我可以截取问题发生的时间范围内的所有日志，缩小范围。
~~~
sed -n '/^<Mar 02, 2012 8:00/ , /^<Mar 02, 2012 8:02/p' stdout.log
~~~
* -n 一定要加上这个参数，否则整个文件会被显示。-n只会显示被模式匹配到的内容。
* p 打印到屏幕