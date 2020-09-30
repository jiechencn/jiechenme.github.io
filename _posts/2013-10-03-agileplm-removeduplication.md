---
title: Remove duplicated values programmatically
author: Jie Chen
date: 2013-10-03
categories: [AgilePLM]
tags: [plsql]
---

We may always see multiple duplicated values for one attribute on Agile WebClient, which happens sometimes, very often. It is OK to correct them from UI if less objects have such issue. But it is boring if too many exist there. We will have a very simple way to quickly identify the column in database table and fix them effectively by a PL/SQL Procedure.

Suppose we have one Part object TESTPART001 which has multiple values for "CM Access" attribute like below.

![](/assets/res/troubleshooting_agileplm-removeduplication-1.jpg)

We need to figure out this attribute's table and column name in database. Since this "CM Access" attribute is from Page Two page, so we go to Part's subclass Page Two tab in JavaClient. We get "PAGE_TWO.MULTILIST02". That is to say, the attribute value is saved in Page_two table, multilist02 column.

![](/assets/res/troubleshooting_agileplm-removeduplication-2.jpg)

To get which row in page_two table, next we shall get this TESTPART001's ID first. There is a very quick way: put your mouse pointer on any tab of that part, you will get a hint of Javascript in browser's status bar. In this example, this TESTPART001's ID is 967250145.

![](/assets/res/troubleshooting_agileplm-removeduplication-3.jpg)

To confirm this, we verify it against ITEM table, and it is.

![](/assets/res/troubleshooting_agileplm-removeduplication-4.jpg)

Then we can query page_two table with the ID to get multilist02 column's value. You will find multiple duplicated values reside.

![](/assets/res/troubleshooting_agileplm-removeduplication-5.jpg)

Then I coded a SQL procedure to fix it programmatically. You can revise it to put into your own scenario.

To remove the duplicated value:

	update page_two set multilist02 = remove_dup_vals(multilist02) where id=967250145;
	commit;



