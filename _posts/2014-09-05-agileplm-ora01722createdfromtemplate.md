---
title: ORA-01722 if reference PPM ‘Created From Template’ in IQuery Where clause
author: Jie Chen
date: 2014-09-05
categories: [AgilePLM]
tags: []
---

There is one issue in Agile SDK API that if "General Info.Created From Template" of PPM is referenced in IQuery‘s WHERE clause, SDK client program will show "ORA-01722: invalid number" error. A BUG 19064118 is filed this week. This article describes why it happens and how to work around it.

## Scenario

This is a customized SDK client program which uses IQuery to search some kinds of PPM objects with matched criteria.

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-1.png)

In WHERE clause, it has criteria:

	[General Info.Created From Template] in (‘PPM_template‘, ‘Project_Amy Template‘)

This code is absolutely correct but when runs it immediately throws exception "ORA-01722" from execution of TableIterator.hasNext().

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-2.png)

## Root Cause

This is an Admin module design bug that "Created From Template" is wrongly defined to TEXT type in JavaClient Admin. Ideally it must be a Dynamica List pointing to Activity objects.

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-3.png)

However when to query the table ACTIVITY in database, it shows CREATED_FROM_TEMPLATE column is NUMBER type and stores only integer number.

	SQL> desc activity
	Name                       Null   Type           
	----------------------------- -------- -------------- 
	ID                     NOT NULL NUMBER 
	...
	CREATED_FROM_TEMPLATE           NUMBER   
	...

When the client program runs, a SQL runs in Server with below similar syntax should get ORA-01722 as Oracle detects.

	select * from ACTIVITY where CREATED_FROM_TEMPLATE in (‘PPM_template‘, ‘Project_Amy Template‘);

Since this is a Dynamica list and it points to Object lists, we should use nested criteria for List search. Then we revise code again.

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-4.png)

However we get "Unsupported operand datatype" error because Agile still thinks it is not a List type. A real chaos.

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-5.png)

## Workaround

No BUG FIX available this moment. So only workaround is possible. Since CREATED_FROM_TEMPLATE column only stores integer number, we can input the template ID to the WHERE clause. If we revise the source code with mapped template id like below demonstrates, it works perfectly.

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-6.png)

So we need to find out the expected templates‘ IDs. There are two ways to achieve. The stupid way is to use SQL.

	SQL> select id, name from activity where 
	decode(root_id, null,0, root_id)=0 and decode(parent_id, null, 0, parent_id)=0 
	and template=1 and name in (‘PPM_template‘, ‘Project_Amy Template‘)；

	ID    Name
	----------------------------
	15982 PPM_template
	15926 Project_Amy Template

Another smart way is to dynamically find the expected templates‘ ID in another IQuery which will search templates with matched criteria, then pass the templates‘ IDs to WHERE clause of PPM Query. The Template‘s criteria should be:

	Project Template = ‘Template‘
	Root Parent is NULL
	Name in (‘PPM_template‘, ‘Project_Amy Template‘)

Finally we have a workable solution to work around the BUG. 

![](/assets//res/troubleshooting-agileplm-ora01722createdfromtemplate-7.png)

