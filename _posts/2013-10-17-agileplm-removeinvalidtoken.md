---
title: PL/SQL to delete invalid data from token Strings
author: Jie Chen
date: 2013-10-17
categories: [AgilePLM]
tags: [plsql]
---

Previous article describes how to delete the duplicated values from token string in bulk mode. This one extends it and shows the way to delete invalid data.

## Scenario

Support we have page_two and manufacturers tables in database and the table DDL is:

	SQL> desc page_two;
	 Name                                      NULL? TYPE
	 ----------------------------------------- -------- ------------------------
	 MULTILIST04                                     VARCHAR2(765)

	SQL>

	SQL> desc manufacturers;
	 Name                                      NULL? TYPE
	 ----------------------------------------- -------- ------
	 ID                                     NOT NULL NUMBER
	 NAME                                            VARCHAR

In table page_two, column multilist04 stores a token string splitted with common. Each token represent a valid ID in manufacturers table. My expectation is to delete invalid token strings from page_two.multilist04, which have no mapping id in manufacturers.id.

For example in below SQL result: ,6295728,33,6295729,6295730,6295731,22, , value 33 and 22 are invalid data because there is no ID equals to 33 or 22 in manufacturers table. So I need to delete 33 and 22.

	SQL> col rowid format a20;
	SQL> col multilist04 format a50;
	SQL> select rowid, multilist04 from page_two;

	ROWID                MULTILIST04
	-------------------- --------------------------------------------------
	AAB+UrADfAAAAhUAAI   ,6295728,6295729,6295730,6295731,
	AAB+UrADfAAAAhUAAJ   ,1111,6295728,6295729,6295730,6295731,
	AAB+UrADfAAAAhUAAK   ,6295728,111,6295729,6295730,6295731,
	AAB+UrADfAAAAhUAAL   ,6295728,6295729,6295730,6295731,22,
	AAB+UrADfAAAAhUAAM   ,6295728,33,6295729,6295730,6295731,22,

.

	SQL> select id, encode_name from manufacturers where id in (1111,11,22,33);

	No rows selected

	SQL>

## Solution

As there is no existing SPLIT function or related in PL/SQL, I should program it by myself. I code Split intermediate function which is used to get the token value between current splitter and next splitter.

![](/assets/res/troubleshooting_agileplm-removeinvalidtoken-1.jpg)

Next program is main entry point, it get each column value from page_two.multilist04, process each row based on cursor. When it get each multilist04 value, it uses above Split function to get each token string stored to singValue variant, then check if it exists in manufacturers.id. If not found, set fixFlag to 1, pending to be deleted.

![](/assets/res/troubleshooting_agileplm-removeinvalidtoken-2.jpg)


