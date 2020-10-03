---
title: How to integrate OpenLDAP with Agile PLM
author: Jie Chen
date: 2014-07-25
categories: [AgilePLM]
tags: []
---

Agile supports other kinds of LDAP serve as a Generic LDAP node in system with the customized groovy script to map LDAP Server‘s attributes to Agile‘s. The difficulty of integration for Agile Administrator is to understand LDAP specific attributes and write the correct groovy code. This article describes the principle of integration with OpenLDAP, an open-source LDAP server, with a sample of scripts.

After setting up the OpenLDAP Server, the administrator needs to import all corporation‘s employees data into LDAP. These data must follow the LDIF standard document RFC2849.

Suppose we have the Base DN of people.company.com, so in our scenario we define three LDAP users ldapjie1, ldapjie2 and ldapjie3 under the OU of people.company.com in a Import file. Attention that we do not define any Group in this sample because I do not know how to do as I am not LDAP Administrator.

	# all_users.ldif
	dn: cn=ldapjie1, ou=People, dc=company,dc=com
	telephoneNumber: 123-456-7890
	facsimileTelephoneNumber: 123-456-7980
	objectClass: top
	objectClass: person
	objectClass: organizationalPerson
	title: engineer
	sn: chen
	cn: ldapjie1

	dn: cn=ldapjie2, ou=People, dc=company,dc=com
	telephoneNumber: 123-456-78912
	facsimileTelephoneNumber: 123-456-79812
	objectClass: top
	objectClass: person
	objectClass: organizationalPerson
	title: engineer
	sn: chen
	cn: ldapjie2

	dn: cn=ldapjie3, ou=People, dc=company,dc=com
	telephoneNumber: 123-456-789123
	facsimileTelephoneNumber: 123-456-798123
	objectClass: top
	objectClass: person
	objectClass: organizationalPerson
	title: engineer
	sn: chen
	cn: ldapjie3

Then we use the 3rd party tool JXplorer to import this file into OpenLDAP server and we will see them imported successfully.

![](/assets//res/troubleshooting-agileplm-openldap-1.png)

Double click anyone user, then click Button "Properties", we will see the user‘s all available attributes.

![](/assets//res/troubleshooting-agileplm-openldap-2.png)

Next we need to set up JavaClient to input OpenLDAP specific data. You many have different values than mine. So you shall not copy them directly.

	Agent: GenericLDAP
	URL: ldap://ldapserver.company.com:389
	Domain: company.com
	Username: cn=ldapjie3,ou=People,dc=company,dc=com
	User Path: ou=People, dc=company,dc=com
	Search Scope: SUB_TREE
	Search Filter: (objectclass=person)

Customized Groovy script:

	import javax.naming.directory.*
	 
	def getSchemaInfo() {
	 [
	 classUser: "person",
	 entryDN: "entryDN",
	 entryCN: "cn",
	 entryGUID: "entryUUID",
	 userID: "cn",
	 #firstName: "givename",
	 lastName: "sn",
	 title: "title",
	 workPhone: "telephoneNumber",
	 fax: "facsimileTelephoneNumber",
	 createTimestamp: "createTimestamp",
	 modifyTimestamp: "modifyTimestamp", 
	 dateFormat: "yyyyMMddHHmmss‘Z‘",
	 sizeLimit: 1000
	 ]
	}
	 
	def isAccountDisabled(Attributes attributes) {
	   return false
	}
	 
	def getEntryDN(SearchResult entry) {
	 Attributes attrs = entry.getAttributes()
	 Attribute dnAttr = attrs.get("entryDN")
	 return dnAttr.get()
	}

In getSchemaInfo() function, the attributes in left are Agile‘s hard-coded attributes. The right attributes are from OpenLDAP which are shown in the second screenshot.

* classUser: mapping to OpenLDAP‘s objectClass "person"
* entryDN: it is DN, mapping to entryDN. If use other LDAP Browser, this attribute is invisible.
* entryCN: it is common name, mapping to cn
* entryGUID: it should be a GUID mapped value in agileuser table, but due to Agile bug, GUID will not populate if LDAP server is GenericLDAP.
* userID: common name
* firstname: there is no givename in this sample because LDIF standard does not define it.
* lastname: mapping to Surname "sn"
* createTimestamp: If use other LDAP Browser, this attribute is invisible.
* modifyTimestamp: If use other LDAP Browser, this attribute is invisible.

In isAccountDisabled() function, because there is no attribute similar to "account disabled", so we just directly return false, supposing all users are active.

In getEntryDN() function, we return "entryDN", the DN‘s value. In other LDAP server, this attribute may have different name.

After javaClient setup, Weblogic Console must have OpenLDAP Authenticator provider configured. All the setting is same as ActiveDirectory except the "User Name Attribute:cn". After restart Weblogic, you should be able to see users synced from OpenLDAP in console.

![](/assets//res/troubleshooting-agileplm-openldap-3.png)

Go back to JavaClient to do Preview and Sync. Agile will import all these users into agileuser table. Next all these users are able to logon Agile.

![](/assets//res/troubleshooting-agileplm-openldap-4.png)

Note: migrateUsersToDB tool does not support GenericLDAP 

