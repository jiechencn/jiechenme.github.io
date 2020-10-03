---
title: BASIC_LEXER and WORLD_LEXER in Agile Quick Search
author: Jie Chen
date: 2014-07-30
categories: [AgilePLM]
tags: []
---

We all know Agile Quick Search uses Oracle Text to do search based on the object name and description. For BASIC_LEXER, Agile defines several special characters in CONTENT LEXER, making them as normal characters not delimiter. While for WORLD_LEXER, Agile has no such definition. So recently we have one customer reporting that if to search a keyword, no expected result returns. While search the same keyword wrapped with parentheses, the object returns in result. The user case is:

* Create an Item, input its Description with content: "hello (validation)" .
* Do Quick Search against Item type with keyword "validation", no result.
* With keyword "(validation)", the Item shows in result.

Let's see how Agile Quick Search works with Oracle Text.

## Root Cause

We enable the DEBUG for SQL and get the Quick Search SQL which is mostly like below with CONTAINS syntax.

	SELECT A.ID,A.CLASS,A.SUBCLASS,  A.FLAGS, A.REV_FLAGS,NULL, NULL, A.ITEM_NUMBER, A.ITEM_NUMBER, A.DESCRIPTION,A.DESC_REV , 
	A.RELEASE_TYPE, A.REV_NUMBER,A.LATEST_RELEASED_ECO , A.SUBCLASS,A.RELEASE_DATE,A.MULTILIST03,A.SUBCLASS,A.CREATE_USER,
	A.MULTILIST01,A.RELEASE_DATE,A.RELEASE_TYPE, A.CREATE_USER , 0, 0 , 
	A.ITEM_NUMBER FROM ITEM_P2P3_QUERY A 
	WHERE (((NVL(A.DELETE_FLAG, 0) = 0) AND  CONTAINS(A.DESCRIPTION,'VALIDATION%',0) > 0 ) ) 

Definitely ITEM_P2P3_QUERY is a view. So we need to find out the DESCRIPTION column''s concrete table.

	--get ITEM_P2P3_QUERY definition
	SQL> select view_name, text from  user_views where view_name = 'ITEM_P2P3_QUERY';

We get the table name: ITEM. Next we get DESCRIPTION based indexes.

	SQL> column index_name format a15;
	SQL> column table_name format a15;
	SQL> column column_name format a15;
	SQL> select index_name , table_name , column_name from user_ind_columns where table_name='ITEM' and column_name='DESCRIPTION' ;

	INDEX_NAME	TABLE_NAME	COLUMN_NAME
	--------------- --------------- ---------------
	ITEM_CTX_IDX	ITEM		DESCRIPTION
	ITEM_DESC_IDX	ITEM		DESCRIPTION

Since the SQL uses CONTAINS syntax, obviously the ITYP_OWNER is CTXSYS. So we get the correct index name: ITEM_CTX_IDX

	column index_name format a15;
	column index_type format a15;
	column ityp_owner format a15;
	column ityp_name format a15;
	select index_name, index_type, ityp_owner, ityp_name from USER_INDEXES where index_name in ('ITEM_DESC_IDX', 'ITEM_CTX_IDX');

	INDEX_NAME	INDEX_TYPE	ITYP_OWNER	ITYP_NAME
	--------------- --------------- --------------- ---------------
	ITEM_CTX_IDX	DOMAIN		CTXSYS		CONTEXT
	ITEM_DESC_IDX	NORMAL

Next, we need to get the detailed definition of index ITEM_CTX_IDX.

	SQL> select ctx_report.create_index_script('ITEM_CTX_IDX') from dual; 

	CTX_REPORT.CREATE_INDEX_SCRIPT('ITEM_CTX_IDX')
	--------------------------------------------------------------------------------
	begin
	  ctx_ddl.create_preference('"ITEM_CTX_IDX_DST"','MULTI_COLUMN_DATASTORE');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_DST"','COLUMNS','ITEM_NUMBER,DESCRIPTION');
	end;
	/

	begin
	  ctx_ddl.create_preference('"ITEM_CTX_IDX_FIL"','NULL_FILTER');
	end;
	/

	begin
	  ctx_ddl.create_section_group('"ITEM_CTX_IDX_SGP"','BASIC_SECTION_GROUP');
	end;
	/

	begin
	  ctx_ddl.create_preference('"ITEM_CTX_IDX_LEX"','BASIC_LEXER');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_LEX"','PRINTJOINS','_-~*'@#%^&.()+=:";');
	end;
	/

	begin
	  ctx_ddl.create_preference('"ITEM_CTX_IDX_WDL"','BASIC_WORDLIST');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_WDL"','STEMMER','ENGLISH');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_WDL"','FUZZY_MATCH','GENERIC');
	end;
	/

	begin
	  ctx_ddl.create_stoplist('"ITEM_CTX_IDX_SPL"','BASIC_STOPLIST');
	end;
	/

	begin
	  ctx_ddl.create_preference('"ITEM_CTX_IDX_STO"','BASIC_STORAGE');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_STO"','R_TABLE_CLAUSE','lob (data) store as (cache)');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_STO"','I_INDEX_CLAUSE','compress 2');
	end;
	/


	begin
	  ctx_output.start_log('ITEM_CTX_IDX_LOG');
	end;
	/

	create index "AG932_CUSTOMER1"."ITEM_CTX_IDX" on "AG932_CUSTOMER1"."ITEM"
		  ("DESCRIPTION")
	  indextype is ctxsys.context
	  parameters('
		datastore	    "ITEM_CTX_IDX_DST"
		filter	    "ITEM_CTX_IDX_FIL"
		section group   "ITEM_CTX_IDX_SGP"
		lexer	    "ITEM_CTX_IDX_LEX"
		wordlist	    "ITEM_CTX_IDX_WDL"
		stoplist	    "ITEM_CTX_IDX_SPL"
		storage	    "ITEM_CTX_IDX_STO"
	  ')
	/

	begin
	  ctx_output.end_log;
	end;
	/

From above result, we get the important information that the index uses BASIC_LEXER as default lexer for English and all other supported whitespace delimited languages.

	begin
	  ctx_ddl.create_preference('"ITEM_CTX_IDX_LEX"','BASIC_LEXER');
	  ctx_ddl.set_attribute('"ITEM_CTX_IDX_LEX"','PRINTJOINS','_-~*'@#%^&.()+=:";');
	end;
	/

Here Agile defines "()" as one of PRINTJOINS, which means it will be treated as a normal alphanumeric character and stored in TEXT index. So "(validation)" is an entire word, instead "validation" is not.

## How it is defined

When we install Agile database, Agile will first creates an OBJECT_LEXER as BASIC_LEXER, and includes predefined printjoins. It is in ctxsys owner.

	--agile9_fts_prefs_lexer_basic.sql
	begin
	ctx_ddl.create_preference('OBJECT_LEXER', 'BASIC_LEXER');
	ctx_ddl.set_attribute('OBJECT_LEXER', 'printjoins', '_-~*''@#%^&.()+=:";');
	end;
	/

Then schema user creates the TEXT index based on the CTXSYS.OBJECT_LEXER.

	--agile9_ctx_recreate.sql
	CREATE INDEX ITEM_CTX_IDX ON ITEM(DESCRIPTION) INDEXTYPE IS CTXSYS.CONTEXT
	   PARAMETERS('DATASTORE CTXSYS.ITEM_MULTI_PREF LEXER CTXSYS.OBJECT_LEXER SECTION GROUP CTXSYS.OBJECT_SECTION_GROUP STOPLIST CTXSYS.EMPTY_STOPLIST');

One more thing, ITEM_CTX_IDX indexes data from both ITEM_NUMBER and DESCRIPTION columns.

	--agile9_fts_prefs.sql
	begin
	ctx_ddl.create_preference('ITEM_MULTI_PREF', 'MULTI_COLUMN_DATASTORE');
	ctx_ddl.set_attribute('ITEM_MULTI_PREF', 'COLUMNS', 'ITEM_NUMBER,DESCRIPTION');
	end;
	/

## Solution

Two options.

* Use *validation* to search
* Recreate the TEXT index with WORLD_LEXER

		ctxsys@agile9_fts_prefs.sql
		ctxsys@agile9_fts_prefs_lexer_world.sql
		schemauser@agile9_ctx_recreate.sql


	 
	 
	 
	 
	 
