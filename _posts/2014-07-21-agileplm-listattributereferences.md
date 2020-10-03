---
title: Solution to detect list attribute references
author: Jie Chen
date: 2014-07-21
categories: [AgilePLM]
tags: []
---

Agile shows alert and does not allow to modify List attribute to switch to another List if this attribute is already referenced by below criteria.

* Admin Criteria
* Report Criteria
* Search Criteria

To enable the attribute modification, we have to go to these Criteria and remove the references one by one. But usually in the alert window we cannot identify the criteria is of Admin, Report or Search. Also if it is used by Search, the alert does not show us who owns the Search. Below screenshot will confuse us.

![](/assets//res/troubleshooting-agileplm-listattributereferences-1.png)

## Admin Criteria

Say we have "Change Orders.Page Two" list attribute named "JieListAttribute" and links to a List "test000001". Now we create a Criteria named "JieCriteria", add this list attribute to criteria mask like below.

![](/assets//res/troubleshooting-agileplm-listattributereferences-2.png)

Note: Do not select "IS NULL" or "IS NOT NULL" because these two do not impact attribute's modification.

Go back to attribute "JieListAttribute", try to select any list for it and click Save, you will get alert.

To detect Admin Criteria references, we can use below SQL to find out. When it prompts for ATTRIBUTE_ID and CLASS_NAME, input its BASE ID and class name (case sensitive).

	SET LIN 200
	COLUMN class_id format a15
	COLUMN criteria_id format a20
	COLUMN criteria_name format a30
	COLUMN class_name format a30

	SELECT criteria_id || '' criteria_id,
		   criteria_name,
		   class_id,
		   class_name
	FROM   (SELECT a.id          criteria_id,
				   a.description criteria_name,
				   b.value       class_id
			FROM   nodetable a,
				   propertytable b
			WHERE  a.parentid = 3642
				   AND a.id = b.parentid
				   AND b.propertyid = 53
				   AND a.id IN (SELECT DISTINCT parentid
								FROM   admincriteria
								WHERE  attid = &ATTRIBUTE_ID
									   AND relop NOT IN ( 9, 10 ))) criterias,
		   (SELECT n.id          cid,
				   n.description class_name
			FROM   nodetable n,
				   (SELECT id
					FROM   nodetable
					WHERE  description = '&CLASS_NAME'
						   AND objtype IN ( 5, 13 )) nc
			WHERE  n.id = nc.id
					OR n.parentid IN (SELECT id
									  FROM   nodetable
									  WHERE  objtype = 14
											 AND parentid = nc.id)) classes
	WHERE  classes.cid = criterias.class_id;


	SQL>/
	Enter value for attribute_id: 1271
	old  15:                             WHERE  attid = &ATTRIBUTE_ID
	new  15:                             WHERE  attid = 1271
	Enter value for class_name: Change Orders
	old  22:                 WHERE  description = '&CLASS_NAME'
	new  22:                 WHERE  description = 'Change Orders'

	CRITERIA_ID          CRITERIA_NAME                  CLASS_ID        CLASS_NAME
	-------------------- ------------------------------ --------------- ------------------------------
	2474421              JieCriteria                    6000            Change Orders


## Report Criteria

Let's create a Custom Report as any user on WebClient, define its Query Definition with "Change Orders.Page Two.JieListAttribute In (xxxxx)". Then try to modify the attribute's list to others on JavaClient, we will get same alert.

![](/assets//res/troubleshooting-agileplm-listattributereferences-3.png)

Solution

	SET LIN 200
	COLUMN report_id format a10
	COLUMN report_name format a40
	COLUMN query_id format a10

	SELECT report_id || '' report_id,
		   report_name,
		   query_id || '' query_id
	FROM   (SELECT a.id   report_id,
				   A.NAME report_name,
				   B.TYPE report_class_id,
				   b.id   query_id
			FROM   REPORT A,
				   QUERY B
			WHERE  A.CRITERIA_ID = B.ID
				   AND B.ID IN (SELECT DISTINCT QUERY_ID
								FROM   CRITERIA
								WHERE  ATTR_ID = &ATTRIBUTE_ID
									   AND RELATIONAL_OP NOT IN ( 9, 10 ))) reports,
		   (SELECT n.id          cid,
				   n.description class_name
			FROM   nodetable n,
				   (SELECT id
					FROM   nodetable
					WHERE  description = '&CLASS_NAME'
						   AND objtype IN ( 5, 13 )) nc
			WHERE  n.id = nc.id
					OR n.parentid IN (SELECT id
									  FROM   nodetable
									  WHERE  objtype = 14
											 AND parentid = nc.id)) classes
	WHERE  reports.report_class_id = classes.cid;

	SQL>/
	Enter value for attribute_id: 1271
	old  13:                             WHERE  ATTR_ID = &ATTRIBUTE_ID
	new  13:                             WHERE  ATTR_ID = 1271
	Enter value for class_name: Change Orders
	old  20:                 WHERE  description = '&CLASS_NAME'
	new  20:                 WHERE  description = 'Change Orders'

	REPORT_ID  REPORT_NAME                              QUERY_ID
	---------- ---------------------------------------- ----------
	14325816   Can you guess who I am? (d)              14325817
	14325809   Can you guess who I am? (c)              14325811

Then you can open the Report object and modify the Query Definition to remove the reference, if you have the privilege.


## Search Criteria

On WebClient, create an Advanced Search, set criteria to use the same attribute. We will get same error if we go to JavaClient to modify the attribute.

![](/assets//res/troubleshooting-agileplm-listattributereferences-4.png)

Solution

	SET LIN 200
	COLUMN loginid format a15
	COLUMN query_name format a40
	COLUMN query_id format a10

	SELECT u.loginid,
		   query_id || '' query_id,
		   query_name
	FROM   (SELECT A.ID    query_id,
				   A.TYPE  query_class_id,
				   A.NAME  query_name,
				   A.owner ownerid
			FROM   QUERY A
			WHERE  A.ID IN (SELECT DISTINCT QUERY_ID
							FROM   CRITERIA
							WHERE  ATTR_ID = &ATTRIBUTE_ID
								   AND RELATIONAL_OP NOT IN ( 9, 10 ))) queries,
		   (SELECT n.id          cid,
				   n.description class_name
			FROM   nodetable n,
				   (SELECT id
					FROM   nodetable
					WHERE  description = '&CLASS_NAME'
						   AND objtype IN ( 5, 13 )) nc
			WHERE  n.id = nc.id
					OR n.parentid IN (SELECT id
									  FROM   nodetable
									  WHERE  objtype = 14
											 AND parentid = nc.id)) classes,
		   agileuser u
	WHERE  queries.query_class_id = classes.cid
		   AND queries.ownerid = u.id
		   AND (query_name is null OR query_id || '' <> query_name);

		   
		   
	sql>/  
	Enter value for attribute_id: 1271
	old  11:                         WHERE  ATTR_ID = &ATTRIBUTE_ID
	new  11:                         WHERE  ATTR_ID = 1271
	Enter value for class_name: Change Orders
	old  18:                 WHERE  description = '&CLASS_NAME'
	new  18:                 WHERE  description = 'Change Orders'

	LOGINID         QUERY_ID   QUERY_NAME
	--------------- ---------- ----------------------------------------
	admin           14321593
	admin           14325249
	admin           14325817   admin1401373750229
	admin           14325848   Can you guess who I am? (a)
	admin           14325896   Can you guess who I am? (b)   

You can ask the user "admin" to modify his Search criteria.

Note: if query_name is null or the format is like loginid plus a number like "admin1401373750229", it means they are temporary Advanced Search. They must be deleted manually via SQL:

	delete query where id=&query_id;
	delete criteria where query_id=&query_id;
	delete select_list where query_id=&query_id;
	commit;

