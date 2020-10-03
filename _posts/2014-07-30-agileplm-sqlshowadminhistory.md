---
title: SQL to show better Admin History than JavaClient
author: Jie Chen
date: 2014-08-14
categories: [AgilePLM]
tags: []
---

In JavaClient, Admin History records all kinds of administration actions to system for audit. These actions could be Modify Class, Add List, Remove Workflow and more. But in most cases, Admin History does not display these actions clearly. For example below records confuse us.

![](/assets//res/troubleshooting-agileplm-sqlshowadminhistory-1.png)

What object are they? Nobody can answer this question unless you analyze the database data internally. This article demonstrates how to customize a powerful SQL to list all kinds of actions in a self-explanatory way with these columns.

    loginid -- user's loginid
    action -- user's action like "Create", "Modify"...
    object_type -- object type name, for example: attribute, class, list, personal criteria and etc.
    object.name -- object name
    ahd.details -- detailed action message in English language
    ah.created -- created time

All the Admin history data are saved in admin_history and admin_history_details tables. admin_history_details table save detailed action message in different language. While admin_history is a mapping table to join agileuser tables with USERID, to admin nodes tables with OWNERID and to admin_history_details table with DETAILID.

	SQL> desc admin_history
	Name      Null     Type   
	--------- -------- ------ 
	ID                 NUMBER 
	USERID             NUMBER 
	TIMESTAMP          DATE   
	ACTIONID           NUMBER 
	TYPE               NUMBER 
	OWNERID            NUMBER 
	DETAILID           NUMBER 
	CREATED   NOT NULL DATE   
	LAST_UPD  NOT NULL DATE    

.

	SQL> desc admin_history_details
	Name     Null     Type               
	-------- -------- ------------------ 
	ID                NUMBER             
	LANGID            NUMBER             
	OBJECT            VARCHAR2(600 CHAR) 
	DETAILS           CLOB               
	CREATED  NOT NULL DATE               
	LAST_UPD NOT NULL DATE    

ACTIONID in admin_history table has 12 types of actions. They are:

	 1 : 'Create'
	 2 : 'Modify'
	 3 : 'Delete'
	 4 : 'SaveAs'
	 5 : 'CopyFrom'
	 6 : 'Undelete'
	 7 : 'Login'
	 8 : 'Reorder'
	 9 : 'Export'
	 10 : 'Purge'
	 11 : 'Import'
	 12 : 'Push'
 

TYPE in admin_history has 3 kinds of nodes. They are:

	0: all nodes in nodetable table
	1: list data in listname table
	2: personal criteria data in pers_criteria_node table
 

We connect these 3 TYPE (3 tables) in a single table with UNION ALL in a sql, making it a single table: object .

	(
		select n.id, n.name, e.entryvalue object_type, 0 type_id 
			from nodetable n, listentry e where n.objtype=e.entryid and e.parentid=101
		union all
		select id, name, 'List' object_type, 1 type_id from listname
		union all
		select id, name, 'PersonalCriteria' object_type, 2 type_id from pers_criteria_node
	) object

Finally, we join object table with admin_history, admin_history_details and agileuser tables. We have below.

	select 
		u.loginid, 
		case ah.actionid
			when 1 then 'Create'
			when 2 then 'Modify'
			when 3 then 'Delete'
			when 4 then 'SaveAs'
			when 5 then 'CopyFrom'
			when 6 then 'Undelete'
			when 7 then 'Login'
			when 8 then 'Reorder'
			when 9 then 'Export'
			when 10 then 'Purge'
			when 11 then 'Import'
			when 12 then 'Push'
		end action,
		object.object_type, 
		object.name, 
		ahd.details, 
		ah.created 

	from 
		admin_history ah, 
		admin_history_details ahd,
		(
			select n.id, n.name, e.entryvalue object_type, 0 type_id 
				from nodetable n, listentry e where n.objtype=e.entryid and e.parentid=101
			union all
			select id, name, 'List' object_type, 1 type_id from listname
			union all
			select id, name, 'PersonalCriteria' object_type, 2 type_id from pers_criteria_node
		) object, 
		agileuser u 
	where 
		ah.detailid=ahd.id 
		and ah.type=object.type_id 
		and langid=0 
		and ah.ownerid=object.id 
		and u.id=ah.userid 
	order by ah.created desc;

Run it and we have a detailed Admin History result from SQL, absolutely better than JavaClient Admin History page. You will like it. 

![](/assets//res/troubleshooting-agileplm-sqlshowadminhistory-2.png)




