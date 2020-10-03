---
title: Agile SDK的Numeric字段精度问题
author: Jie Chen
date: 2017-01-23
categories: [AgilePLM]
tags: [sdk]
---

Web Client显示Numeric字段时（Double类型），根据在Java Client中scale设定的小数位数自动计算精度。比如下图中所示的Java Client和Web Client所展示的那样。

![](/assets/res/troubleshooting-agileplm-sdknumeric-1.png)

![](/assets/res/troubleshooting-agileplm-sdknumeric-2.png)

但是如果使用SDK来获取Numeric字段时，不能简单地通过下面的错误代码来获取Double类型返回值，因为它这个代码只是获取Double的实际值，并没有获取精度设定。

	IItem item = (IItem)session.getObject(IItem.OBJECT_TYPE, "DIE-00001");
	Object num = item.getValue(new Integer(12472));
	 
同样地，如果使用上面的代码来处理下面的数值，会发现和Web Client相差问题很大。

![](/assets/res/troubleshooting-agileplm-sdknumeric-3.png)

解决方法很简单， 就是获得Double值后，再去获取Admin数据中的精度设定。

	IAdmin admin = session.getAdminInstance();
	IAttribute attr = admin.getAgileClass(ItemConstants.CLASS_PART).getAttribute(new Integer(12472));
	int scale = ((Double)attr.getProperty(PropertyConstants.PROP_SCALE).getValue()).intValue();
	 
根据精度设定，使用DecimalFormat对Double数值格式化。特别注意当小数位精度设为0时，格式化串必须是“0”，而不是“0.”
	 
	// parse numeric to be formatted
	String f = "0.";
	if (scale==0)
	  f = "0";
	else
	  for (int i = 0; i<scale; i++) f += "0";
	DecimalFormat df = new DecimalFormat(f);
	String data = df.format(num);
	// print expected numeric data as a String
	System.out.println(data); 
	 
	 
