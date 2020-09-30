################ - READ ME - ##############################
# 1. Copy SQLView.py to Weblogic DOMAIN_HOME\bin\ folder
# 2. Run environment setting script of weblogic
#		Windows	:	setEnv.cmd
#		Linux	:	source ./setEnv.sh
# 3. Run command: 
#		java weblogic.WLST SQLView.py
# 4. Follow the instruction to proceed on 
###########################################################


###########################################################
# Supported features:
# - Connection:
#     JTS Connection
#     pooled Connection
# - Statement:
#     prepared tatement
#     simple tatement
#     prepareCall
#     direct SQL execument
#     bulk statement 
#
###########################################################

###########################################################
#	Weblogic	OS				APP				Pass
#	---------------------------------------------
#	12.2.1.3	Win2012R2(64)	AG9367(Cluster)	Y
#	12.2.1.3	Win2012R2(64)	AG9366(Cluster)	Y
#	12.2.1.1	Win2012R2(64)	AG936			Y
#	12.1.3		OEL6(64)		AG935(Cluster)	Y
#	12.1.3		Win2012R2(64)	AG935			Y
#	12.1.3		Win2012R2(64)	AG934			Y
#	12.1.1		Win2008R2(64)	AG933			Y
#	12.1.1		Win2008R2(64)	AG932			Y
#	10.3.6		Win2008R2(64)	AG931			Y
#	
###########################################################

import re
import shutil
import sys
import codecs
from java.util import Date
from java.text import SimpleDateFormat
from java.lang import System
from java.io import File

#import time as pytime
#####################################################################################################
__script_name = 'SQLView'
__script_version = '1.0'
__script_authtor = 'jie.chen@oracle.com'
__script_copyright = '(C) Oracle'

__k_conn = '_C'
__k_state = '_S'
__k_result = '_R'
__r_newrow = '_NR_'
__t_start = '_START'
__t_end = '_END'
#__dt_format = 'MMM d, yyyy, h:mm:ss,S a z'
__dt_format = 'MMM d, yyyy h:mm:ss,SSS a z'
__s_dummysql = '_DUMMY_'
__fileSize = 50000 # KB
__prompt_cur = '                           >>' # prompt cursor space
__sleep = 1  # frequent interval seconds to check flush
__flush_max_wait = 300 # seconds
__flush_time_buffer_gap = 20 # secons to fill in gap of weblogic flush log time
#####################################################################################################
wlsVersion = ''
pyVersion = ''
wlsInstance = 'Offline'
rptStart = ''
rptEnd = ''

dfLogTimestamp = SimpleDateFormat(__dt_format) # by default


sqlConns = {}
sqlStatementConnMap = {}	#{'C1000S1001':'sql1', 'C1000S1002':'sql2', 'C2000S2001':'sql3'}
sqlStatementTime = {}	#{'S1001':['20170102','20170103', 'S1002':['20170105','20170106'}
sqlStatementsParas = {} 	#{'S1001':['setInt(1,  6018285)', 'setString(2,  'hello')'], 'S1002':['setInt(1,  6018299)', 'setString(2,  'world')'], 'S2001':[]}
sqlStatementResultMap = {}	#{'S1001':'R001', 'S1002':'0', 'S2001':'1'}
sqlStatementResultReverseMap = {} # {'R1001':'S001', 'R1002':'S0001', 'R2001':'S0001'}   for bulk select results only
sqlStatementResults = {}	#{'R001':['_NR_', '704', '2000007951', '_NR_', '704', '2000007952']}
sqlResultIndexes = {}	#{'R001':[1,2,3,4,5]}
#####################################################################################################
def elapsed(t0, t1):
	try:
		unix0 = time2unix(t0)
		unix1 = time2unix(t1)
		return str(unix1-unix0)
	except:
		return 'N/A'

def time2unix(tt):
	return dfLogTimestamp.parse(tt).getTime()

		
# get connection ID(s)
def getConns(oneline):
	try:
		
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes
		
		#1. weblogic.jdbc.wrapper.JTSConnection_oracle_jdbc_driver_T4CConnection@bf0f] prepareStatement(selec * from ...
		#2. weblogic.jdbc.wrapper.JTSConnection_oracle_jdbc_driver_T4CConnection@bf0c] CreateStatement()> 
		#3. weblogic.jdbc.wrapper.JTSConnection_oracle_jdbc_driver_T4CConnection@bf11] prepareCall(select * from ...
		#4. weblogic.jdbc.wrapper.PoolConnection_oracle_jdbc_driver_T4CConnection@bee9] prepareStatement(SELECT t1.JPS_ATTRS_ID, t1.ATTRNAME,
		
		patternPrepare = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] prepareStatement\((.*)\)>'
		patternCall = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] prepareCall\((.*)\)>'
		patternSimpleSt = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] CreateStatement\(\)>'
		
		patternMatch = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] (prepareStatement|CreateStatement|prepareCall)\((.*)\)>'
		
		matchObj = re.match(patternMatch, oneline, re.M|re.I)
		if matchObj:
			connID = matchObj.group(3)
			sqlBody = matchObj.group(5)
			if sqlBody=='':
				sqlBody = __s_dummysql
			sqlConns[__k_conn + connID] = sqlBody
			return True
		else:
			return False
	except Exception:
		raise



# get mappings between connection ID(s) and statements
def getStatementConnMap(oneline):
	try:
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes
		#x. simplestatment: yes: specially process sql 
		
		#1. weblogic.jdbc.wrapper.JTSConnection_oracle_jdbc_driver_T4CConnection@bf0f] prepareStatement returns weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@bf10> 
		#2. weblogic.jdbc.wrapper.JTSConnection_oracle_jdbc_driver_T4CConnection@bf0c] CreateStatement returns weblogic.jdbc.wrapper.Statement_oracle_jdbc_driver_OracleStatementWrapper@bf0d> 
		#3. weblogic.jdbc.wrapper.JTSConnection_oracle_jdbc_driver_T4CConnection@bf11] prepareCall returns weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@bf12> 
		#4. weblogic.jdbc.wrapper.PoolConnection_oracle_jdbc_driver_T4CConnection@bee9] prepareStatement returns weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@beeb> 
		#x. weblogic.jdbc.wrapper.Statement_oracle_jdbc_driver_OracleStatementWrapper@bf0d] executeQuery(selec * from agileuser)> 
		
		patternStatement = r'(.*)<(.*)> <Debug> <JDBCSQL> (.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] (p|c)re(.*) returns weblogic\.jdbc\.wrapper\.(.*)Statement_oracle_jdbc_driver_Oracl(.*)StatementWrapper(.*)>'
	
		patternPrepare = r'(.*)<(.*)> <Debug> <JDBCSQL> (.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] prepare(.*)returns weblogic\.jdbc\.wrapper\.(.*)Statement_oracle_jdbc_driver_Oracle(.*)StatementWrapper(.*)>'
		patternSimpleSt = r'(.*)<(.*)> <Debug> <JDBCSQL> (.*)weblogic\.jdbc\.wrapper\.(.*)Connection_oracle_jdbc_driver_T4CConnection(.*)\] CreateStatement returns weblogic\.jdbc\.wrapper\.Statement_oracle_jdbc_driver_OracleStatementWrapper(.*)>'
		
		patternSimpleStSQL = r'(.*)<(.*)> <Debug> <JDBCSQL> (.*)weblogic\.jdbc\.wrapper\.Statement_oracle_jdbc_driver_OracleStatementWrapper(.*)\] executeQuery\((.*)\)>'
		
		matchObj = re.match(patternStatement , oneline, re.M|re.I)
		if matchObj:
			startTime = matchObj.group(2)
			connID = matchObj.group(5)
			stateID = matchObj.group(10)
			tmpSQL = sqlConns.get(__k_conn + connID, __s_dummysql)
			sqlConns[__k_conn + connID + __k_state + stateID ] = tmpSQL
			sqlConns[__k_conn + connID] = __s_dummysql;
			del sqlConns[__k_conn + connID]
				
			sqlStatementConnMap[__k_state + stateID] =  __k_conn + connID
			sTime = []
			sTime.append(startTime)
			sqlStatementTime[__k_state + stateID] = sTime 
			return True
		else:# get simplestatement's SQL
			matchObj = re.match(patternSimpleStSQL , oneline, re.M|re.I)
			if matchObj:
				stateID = matchObj.group(4)
				curSQL = matchObj.group(5)
				cconnID = sqlStatementConnMap.get(__k_state + stateID, '')
				sqlConns[cconnID + __k_state + stateID ] = curSQL
				sqlConns[cconnID] = __s_dummysql;
				del sqlConns[cconnID]
				return True
			else:
				return False
	except Exception:
		#print(sqlConns)
		raise

# get statements' end time
def getStatementEndTime(oneline):
	try:
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes
		
		#1. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@bf10] close returns> 
		#2. weblogic.jdbc.wrapper.Statement_oracle_jdbc_driver_OracleStatementWrapper@bf0d] close returns> 
		#3. weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@bf12] close returns> 
		#4. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@beeb] close returns>
		patternState = r'(.*)<(.*)> <Debug> <JDBCSQL> (.*)weblogic\.jdbc\.wrapper(.*)Statement_oracle_jdbc_driver(.*)StatementWrapper(.*)\] close returns>'
		matchObj = re.match(patternState , oneline, re.M|re.I)
		if matchObj:
			endTime = matchObj.group(2)
			stateID = matchObj.group(6)	
			sTime = sqlStatementTime.get(__k_state + stateID, [])
			sTime.append(endTime)			
			sqlStatementTime[__k_state + stateID] = sTime
			return True
		else:
			return False
	except Exception:
		raise
		

# get statement's parameter
def getStatementsParas(oneline): 
	try:
		#1. prepared: yes
		#2. simplestatment: no
		#3. callable: yes
		#4. metadata: yes
		
		#1. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@bf08] setInt(2, 2)> 
		#3. weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@bf12] setTimeStamp(1, 2018-01-31 08:31:30.596)> 
		#3b.weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@31bbf] registerOutParameter(1, 4)> 
		#4. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@beeb] setString(1, cn=globalpolicy)> 
		
		patternMatch = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Statement_oracle_jdbc_driver_Oracle(.*)StatementWrapper(.*)] (.*)\(([0-9]+),(.*)\)>'
		
		patternStatePara = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Statement_oracle_jdbc_driver_Oracle(.*)StatementWrapper(.*)] set(.*)\((.*),(.*)\)>'
		patternCallRegisterPara = r'(.*)weblogic\.jdbc\.wrapper\.(.*)Statement_oracle_jdbc_driver_Oracle(.*)StatementWrapper(.*)] registerOutParameter\((.*)>'
		matchObj = re.match(patternMatch, oneline, re.M|re.I)
		if matchObj:
			stateID = matchObj.group(4)
			ptype = matchObj.group(5)
			pOrder = matchObj.group(6)
			pvalue = matchObj.group(7)
			#sqlStatements[__k_state + stateID] =  __k_conn + connID
			#print connID
			paras = []
			paras = sqlStatementsParas.get(__k_state + stateID, [])
			paras.append(ptype + '(' + pOrder + ', ' + pvalue + ')')
			sqlStatementsParas[__k_state + stateID] = paras
			return True
		else:
			return False
	except Exception:
		raise


		
#get resultsets
def getStatementResultMap(oneline):
	try:
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes

		#1a. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@1ad7a] executeQuery returns weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_OracleResultSetImpl@1ad7b> 
		#1b. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@beeb] executeQuery returns weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@beec> 
		#2.  weblogic.jdbc.wrapper.Statement_oracle_jdbc_driver_OracleStatementWrapper@bf0d] executeQuery returns weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@bf0e>
		#3.  weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@bf12] executeQuery returns weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@bf13> 
		#1/4.  weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@beee] executeQuery returns weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@beef> 
		
		# for bulk select, one statement has multiple resultID, so only record the first recordID

		
		patternSelectResultMap = r'(.*)weblogic\.jdbc\.wrapper(.*)Statement_oracle_jdbc_driver_Ora(.*)StatementWrapper(.*)\] executeQuery returns weblogic\.jdbc\.wrapper\.ResultSet_oracle_jdbc_driver(.*)ResultS(.*)@(.*)>'		
		
		matchObj = re.match(patternSelectResultMap , oneline, re.M|re.I)
		if matchObj:
			stateID = matchObj.group(4)
			resultID = '@' + matchObj.group(7)
			if sqlStatementResultMap.get(__k_state + stateID, '') == '':
				sqlStatementResultMap[__k_state + stateID] = __k_result + resultID
			#print sqlStatementResultMap
			sqlStatementResultReverseMap[__k_result + resultID] = __k_state + stateID
			return True
		else:
			return False
	except Exception:
		raise
		
		

#get executeUpdate result (insert/update/delete)
def getAddDelUpdResultMap(oneline):
	try:
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes
		
		#1/4. weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@bf03] executeUpdate returns 1>
		#2/4. weblogic.jdbc.wrapper.Statement_oracle_jdbc_driver_OracleStatementWrapper@bf03] executeUpdate returns 1>
		#2b.  weblogic.jdbc.wrapper.PreparedStatement_oracle_jdbc_driver_OraclePreparedStatementWrapper@31bbb] executeBatch returns [I@623312d5> 
		#3. weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@bf03] executeUpdate returns 1>
		
		patternAddDelUpdResult = r'(.*)weblogic\.jdbc\.wrapper(.*)Statement_oracle_jdbc_driver_Ora(.*)StatementWrapper(.*)\] execute(.*) returns (.*)>'
		matchObj = re.match(patternAddDelUpdResult, oneline, re.M|re.I)
		if matchObj:
			stateID = matchObj.group(4)
			result = matchObj.group(6)
			sqlStatementResultMap[__k_state + stateID] = result
			return True
		else:
			return False
	except Exception:
		raise
		
	
def getFirstResultIDforBulkSelect(curResultID):
	try:
		sid = sqlStatementResultReverseMap.get(curResultID, '')
		rid = sqlStatementResultMap.get(sid)
		return rid
	except Exception:
		raise
# get next token of new row from resultset
def getResultNextToken(oneline):
	try:
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes
		
		#1/2/3/4. weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_OracleResultSetImpl@1ad7b] next returns true>
		#3		. weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@31bbd] next returns true>
		#1/2/3/4. weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@beec] next returns true>
		
		patternResultNextToken = r'(.*)weblogic\.jdbc\.wrapper\.(.*)@(.*)\] next returns true>'
		matchObj = re.match(patternResultNextToken, oneline, re.M|re.I)
		if matchObj:
			resultID = '@' + matchObj.group(3)
			results = []
			firstRID = getFirstResultIDforBulkSelect(__k_result + resultID)
			results = sqlStatementResults.get(firstRID, [])
			results.append(__r_newrow)
			sqlResultIndexes[firstRID] = [] #new row begins, clean old column indexes
			sqlStatementResults[firstRID] = results
			return True
		else:
			return False
	except Exception:
		raise	

#get resultset column is column is duplicated
def isResultColumnDuplicated(oneline):
	try:
		# weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_OracleResultSetImpl@beec] getString(1)>
		# weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_OracleResultSetImpl@beec] getString(1)>
		# weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_OracleResultSetImpl@beec] getInt(2)>
		
		# return:
		#   1: duplicated
		#   2: not duplicated
		#   3: not matched
		patternResultIndex = r'(.*)weblogic\.jdbc\.wrapper\.(.*)@(.*)\] get(.*)\((\d+)\)>' 
				
		matchObj = re.match(patternResultIndex, oneline, re.M|re.I)
		if matchObj:
			resultID = '@' + matchObj.group(3)
			index = matchObj.group(5) 
			firstRID = getFirstResultIDforBulkSelect(__k_result + resultID)
			indexes = sqlResultIndexes.get(firstRID, [])
			
			if index in indexes: # need to remove duplicated column, 1 if duplicated
				return 1
			indexes.append(index)
			sqlResultIndexes[firstRID] = indexes
			
			return 2
		else:
			return 3
	except Exception:
		raise	
		
		
		
#get each data from resultset
def getResultNextValue(oneline):
	try:
		#1. prepared: yes
		#2. simplestatment: yes
		#3. callable: yes
		#4. metadata: yes
		
		#1/2/3/4. weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@beec] getString returns hello>
		#1/2/3/4. weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_OracleResultSetImpl@beec] getString returns hello>
		#3.       weblogic.jdbc.wrapper.CallableStatement_oracle_jdbc_driver_OracleCallableStatementWrapper@31bbd] getInt returns 6014965>
		#4.       weblogic.jdbc.wrapper.ResultSetMetaData_oracle_jdbc_driver_OracleResultSetMetaData@beed] getColumnCount returns 4
		#patternResultValue = r'(.*)weblogic\.jdbc\.wrapper\.ResultSet(.*)oracle_jdbc_driver_(.*)ResultS(.*)@(.*)\] get(.*) returns (.*)>'
		
		#Note: filter out:  weblogic.jdbc.wrapper.ResultSet_oracle_jdbc_driver_ForwardOnlyResultSet@beec] getMetaData returns weblogic.jdbc.wrapper.ResultSetMetaData_oracle_jdbc_driver_OracleResultSetMetaData@beed> 
		
		patternResultValue = r'(.*)weblogic\.jdbc\.wrapper\.(.*)@(.*)\] get(.*) returns(.*)>' 
		
		matchObj = re.match(patternResultValue, oneline, re.M|re.I)
		if matchObj:
			if (matchObj.group(4)=='MetaData'):
				return False
			resultID = '@' + matchObj.group(3)
			value = matchObj.group(5)
			if value[:1]==' ':
				value = value[0-len(value)+1:]
			results = []
			firstRID = getFirstResultIDforBulkSelect(__k_result + resultID)
			results = sqlStatementResults.get(firstRID, [])
			results.append(value)
			sqlStatementResults[firstRID] = results
			#print value
			return True
		else:
			return False
	except Exception:
		raise	

def info(action, result):
	System.out.println(__prompt_cur + ' ' + str(action) + " " + str(result))

def showProgress(s):
	System.out.print(s)

def flushed(userStopUnixTime, logModifiedUnixTime):
	diff = logModifiedUnixTime - userStopUnixTime
	if (diff >= __flush_time_buffer_gap * 1000):
		return True
	else:
		curUnix = Date().getTime()
		if (curUnix >= userStopUnixTime + __flush_max_wait*1000):
			return True
	return False
		

def collectJDBCSQL():
	global wlsInstance
	global wlsVersion
	global rptStart
	global rptEnd
	try:
		wlsVersion = version
		info('Weblogic Version:', wlsVersion)

		info('Note:', 'You must run this script on the Weblogic Managed Server which you connect.')
		info('', '')

		connect()
		edit()
		startEdit()
		serverNames=cmo.getServers()
		allServers = []
		for name in serverNames:
			curServerName = name.getName()
			allServers.append(curServerName)
		#allServers.append('agile-server2')
		#allServers.append('agile-server3')
		#allServers.append('agile-server4')
		info('Find following Weblogic instance(s):', len(allServers))
		info('', '')
		for i in range(len(allServers)):
			srv = allServers[i]
			info('    ' + str(i) + ':', srv)
		info('', '')	
		info('Type the number to select the correct Weblogic instance to connect, or type x to exit.', '')
		user_sel = ''
		while user_sel == '':
			user_sel = raw_input(__prompt_cur + ' Your choice: ')
		if user_sel.lower()=='x':
			save()
			activate()
			disconnect()
			info('User quits.', 'Bye')
			exit()
		
		wlsInstance = allServers[int(user_sel)]
		cd('/Servers/'+ wlsInstance + '/Log/' + wlsInstance)
		#ls()
		sqlLogFile = get('FileName')	
		info('Get log file:', sqlLogFile)
		sqlLogOrigSize = get('FileMinSize')
		info('Get log size:', str(sqlLogOrigSize))
		logDTFormatStr = get('DateFormatPattern')
		info('Get log date format:', str(logDTFormatStr))
		
		set('FileMinSize', __fileSize)
		info('Set log size:', str(__fileSize))
		set('DateFormatPattern', __dt_format)
		info('Set log date format:', __dt_format)
		cd('/Servers/' + wlsInstance + '/ServerDebug/' + wlsInstance)
		set('DebugJDBCSQL','true')
		info('Set DebugJDBCSQL:', 'true')
		save()
		activate()

		sqlLogFilePath = os.getcwd() + '/../servers/' + wlsInstance + '/' + sqlLogFile
		
		rptStart = dfLogTimestamp.format(Date())
		info('It is collecting SQL data. Press Enter after collected.', '')
		raw_input(__prompt_cur + ' ')
		dtRpt = Date()
		rptEnd = dfLogTimestamp.format(dtRpt)
		
		##
		info(__script_name + ' is waiting for Weblogic to flush log, please hold on...', '')
		#pytime.sleep(__sleep)
		jfile = File(sqlLogFilePath)
		
		showProgress(__prompt_cur + ' ')
		while True:
			jfmodifiedUnix = jfile.lastModified()
			rpt_endtime_unix = dtRpt.getTime()
			dtCurrent = Date()
			if (flushed(rpt_endtime_unix, jfmodifiedUnix)):
				break
			showProgress('.')
			Thread.sleep(__sleep * 1000)
			
		showProgress('\n')
		sqlLogFilePathCopy = sqlLogFilePath + '.' + __script_name
		shutil.copyfile(sqlLogFilePath, sqlLogFilePathCopy) # copy jdbc log file
		info('Copy ' + sqlLogFile + ' to', sqlLogFilePathCopy)
		
		##
		## revert back to original setting
		edit()
		startEdit()
		info('Get server:', wlsInstance)
		cd('/Servers/'+ wlsInstance + '/Log/' + wlsInstance)
		set('FileMinSize', sqlLogOrigSize)
		info('Reset log size:', str(sqlLogOrigSize))
		set('DateFormatPattern', logDTFormatStr)
		info('Reset log date format:', str(logDTFormatStr))
		cd('/Servers/' + wlsInstance + '/ServerDebug/' + wlsInstance)
		set('DebugJDBCSQL','false')
		info('Reset DebugJDBCSQL:', 'false')
		save()
		activate()
		disconnect()
		return sqlLogFilePathCopy
		#rpt_endtime = pytime.strftime(__dt_format, pytime.localtime()) 
	except Exception:
		save()
		activate()
		disconnect()
		raise


def createHTMLReport(jdbcLogFile):
	global wlsInstance
	global wlsVersion
	global rptStart
	global rptEnd
	info('Generating HTML Report...', '')

	f = codecs.open(jdbcLogFile, 'r', encoding='utf-8')
	try:
		lines = f.read().splitlines()
	except:
		f = open(jdbcLogFile, 'r')
		lines = f.read().splitlines()
	f.close()
	
	curDupCol = 3
	preDupCol = 3
	for line in lines:
		#print(line)
		line.strip()
		if line=='':
			continue
		if (getConns(line)==False):
			if (getStatementEndTime(line)==False):
				if (getStatementConnMap(line)==False):
					if (getStatementsParas(line)==False):
						if (getStatementResultMap(line)==False):
							if (getAddDelUpdResultMap(line)==False):
								if (getResultNextToken(line)==False):
									curDupCol = isResultColumnDuplicated(line);
									if (curDupCol==1 or curDupCol==2):
										preDupCol = curDupCol
										continue;
									if (curDupCol==3 and preDupCol==2):
										getResultNextValue(line)

	
	
	htmlText = []
	htmlText.append('<html lang="en-US">')
	htmlText.append('<head>')
	htmlText.append('<meta charset="utf-8">')
	htmlText.append('<title>' + __script_name + ' Report for '+ wlsInstance +'</title>')
	htmlText.append('<style type="text/css">')
	htmlText.append('body.awr {font:bold 10pt Arial,Helvetica,Geneva,sans-serif;color:black; background:White;}')
	htmlText.append('h1.awr {font:bold 20pt Arial,Helvetica,Geneva,sans-serif;color:#336699;background-color:White;border-bottom:1px solid #cccc99;margin-top:0pt; margin-bottom:0pt;padding:0px 0px 0px 0px;}')
	htmlText.append('th.awrnobg {font:bold 8pt Arial,Helvetica,Geneva,sans-serif; color:green; background:white;padding-left:4px; padding-right:4px;padding-bottom:2px}')
	htmlText.append('th.awrbg {font:bold 8pt Arial,Helvetica,Geneva,sans-serif; color:White; background:#0066CC;padding-left:4px; padding-right:4px;padding-bottom:2px}')
	htmlText.append('td.awrnc {font:8pt Arial,Helvetica,Geneva,sans-serif;color:black;background:White;vertical-align:top;}')
	htmlText.append('td.awrnc2 {font:8pt Arial,Helvetica,Geneva,sans-serif;color:black;background:#dedede;vertical-align:top;}')
	htmlText.append('table.tdiff {border_collapse: collapse; }')
	htmlText.append('table.xTBResult {border-collapse: collapse;}')
	htmlText.append('td.xTDResult {border:#ccc solid 1px;font:8pt Arial,Helvetica,Geneva,sans-serif;color:black;background:White;vertical-align:top;}')
	htmlText.append('</style>')
	htmlText.append('</head>')
	htmlText.append('<body class="awr">')
	htmlText.append('<h1 class="awr">')
	htmlText.append(__script_name + ' Report for '+ wlsInstance )
	htmlText.append('</h1>')
	htmlText.append('<p/>')
	htmlText.append('<table border="0" width="100%" class="tdiff">')
	htmlText.append('<tr><th class="awrbg">' + __script_name + ' Version</th><th class="awrbg">Jython Version</th><th class="awrbg">Weblogic Instance</th><th class="awrbg">Weblogic Version</th><th class="awrbg">Report Start</th><th class="awrbg">Report End</th></tr>')
	htmlText.append('<tr><td class="awrnc">' + __script_version + '</td><td class="awrnc">'+ pyVersion +'</td><td class="awrnc">'+ wlsInstance +'</td><td class="awrnc">'+ wlsVersion +'</td><td class="awrnc">'+ rptStart +'</td><td class="awrnc">'+ rptEnd +'</td></tr>')
	htmlText.append('</table>')
	htmlText.append('<p/>')
	htmlText.append('<p/>')

	htmlText.append('<h3 class="awr"><a class="awr"></a>Report Summary</h3>')
	htmlText.append('<table border="0" width="100%" class="tdiff">')
	htmlText.append('<tr><th class="awrbg">Connection</th><th class="awrbg">Statement</th><th class="awrbg">Resultset</th><th class="awrbg">Start Time</th><th class="awrbg">End Time</th><th class="awrbg">Elapsed(ms)</th></tr>')

	sqlDetails = []

	#print(sqlConns)
	#print(sqlStatementConnMap)
	#print(sqlStatementsParas)
	#print(sqlStatementResults)
	#print(sqlStatementTime)

	cssTd2 = 'awrnc'

	keyOfStatement = sqlStatementConnMap.keys()
	keyOfStatement.sort()
	for sid in keyOfStatement:	
	#for sid in sqlStatementConnMap.keys():
		cid = sqlStatementConnMap.get(sid, '')
		sql = sqlConns.get(cid + sid, '')
		rid = sqlStatementResultMap.get(sid, sid) 
		seTime = sqlStatementTime.get(sid, ['0','0'])
		paras = sqlStatementsParas.get(sid, '')
		resultAmout = 0
		rows = '0'
		if rid.startswith(__k_state): # for callablestatement, once case that resultset is not used. So sql result is saved in statement directly
			rid = rid.replace(__k_state, __k_result)
		if rid.startswith(__k_result):
			rows = sqlStatementResults.get(rid, '0') #if select returns no result, there is no matched record. So use default 0
			if isinstance(rows, str): # is '0' or '1' or '2' ....
				resultAmout = rows
			else:
				#resultAmout = len(rows)
				pass
		else:
			resultAmout = rid
			
		if len(seTime)==0:
			seTime = ['','']
		else:
			if len(seTime)==1:
				seTime.append('')

		sqlResult = []
		trFlag = 0
		callableResRow = 0
		if isinstance(rows, list): # if it is R@xxxx
			for r in range(len(rows)):
				rvalue = rows[r]
				#print(rvalue)
				if rvalue==__r_newrow: # new row
					if ((trFlag!=0) and (trFlag%2==1)):
						sqlResult.append('</tr>')
					sqlResult.append('<tr>')
					trFlag += 1
				else:
					sqlResult.append('<td class="xTDResult">' + rvalue + '</td>')
					callableResRow = 1
			resultAmout = trFlag
		else:
			sqlResult.append('<tr>')
			sqlResult.append('<td class="xTDResult">Rows: ' + resultAmout + '</td>')
		
		if (callableResRow == 1 and resultAmout==0):
			resultAmout = callableResRow
		#cssTd2 = 'awrnc'
		htmlText.append('<tr><td class="'+ cssTd2 +'">' + cid + '</td><td class="'+ cssTd2 +'"><a href="#' + sid + '">' + sid + '</a></td><td class="'+ cssTd2 +'">' + str(resultAmout) + '</td><td class="'+ cssTd2 +'">'+ seTime[0] + '</td><td class="'+ cssTd2 +'">'+ seTime[1] + '</td><td class="'+ cssTd2 +'">' + elapsed(seTime[0], seTime[1]) + '</td></tr>')
		if cssTd2=='awrnc':
			cssTd2 = 'awrnc2'
		else:
			cssTd2 = 'awrnc'
			
		sqlDetails.append('<p></p>')
		sqlDetails.append('<table width="100%" class="tdiff">')
		sqlDetails.append('<tr><th class="awrnobg" align="left"><a name="' + sid + '">' + sid + '</a></th><th class="awrbg">Value</th></tr>')
		sqlDetails.append('<tr><td scope="row" width="80" class="awrnc">SQL Body</td><td class="awrnc">' + sql + '</td></tr>')
		sqlDetails.append('<tr><td scope="row" width="80" class="awrnc">Parameter</td><td class="awrnc">' + '<br> '.join(paras) + '</td></tr>')
		sqlDetails.append('<tr><td scope="row" width="80" class="awrnc">Start Time</td><td class="awrnc">' + seTime[0] + '</td></tr>')
		sqlDetails.append('<tr><td scope="row" width="80" class="awrnc">End Time</td><td class="awrnc">' + seTime[1] + '</td></tr>')
		sqlDetails.append('<tr><td scope="row" width="80" class="awrnc">Elapsed(ms)</td><td class="awrnc">' + elapsed(seTime[0], seTime[1]) + '</td></tr>')
		sqlDetails.append('<tr><td scope="row" width="80" class="awrnc">Result('+ str(resultAmout) +')</td><td class="awrnc">')
		sqlDetails.append('<table width="100%" class="xTBResult xTDResult">')
		sqlDetails += sqlResult
		sqlDetails.append('</tr>')
		sqlDetails.append('</table>')
		sqlDetails.append('</td>')
		sqlDetails.append('</tr>')
		sqlDetails.append('</table>')

	htmlText.append('</table>')
	htmlText.append('<p/>')
	htmlText.append('<p/>')
	htmlText.append('<h3 class="awr"><a class="awr"></a>SQL Detail</h3>')

	htmlText += sqlDetails

	htmlText.append('<p/>')
	htmlText.append('<p/>')
	htmlText.append('<a class="awr" href="#top">Back to Top</a>')
	htmlText.append('<p/>End of Report')
	htmlText.append('</body>')
	htmlText.append('</html>')

	reportFilePath = os.getcwd() + "/" + __script_name + "-" + wlsInstance + ".html"
	
	
	f = codecs.open(reportFilePath, 'w', encoding='utf-8')
	try:
		f.writelines(htmlText)
	except:
		f = open(reportFilePath, 'w')
		f.writelines(htmlText)
	f.close()
	
	return reportFilePath

	
		
#####################################################################################################

info('Welcome to ' + __script_name, __script_version)
pyVersion = sys.version
info('Jython Version:', pyVersion)

info('Type the number to select mode', "")
info('','')
info('    0:', " Online mode")
info('    1:', " Offline mode (for Oracle Support only)")
info('','')
user_sel = raw_input(__prompt_cur + ' Your choice: ')
if user_sel.lower()=='0':
	sqlLogFilePath = collectJDBCSQL()
else:
	info('Please copy the JDBC log file to directory: ', os.getcwd())
	user_input_filename = ''
	while user_input_filename=='':
		info('','')
		user_input_filename = raw_input(__prompt_cur + ' JDBC log file name: ')
	sqlLogFilePath = os.getcwd() + "/" + user_input_filename
	
reportFilePath = createHTMLReport(sqlLogFilePath)



info('','')
info('HTML Report file:', reportFilePath)
info('JDBC Log file   :', sqlLogFilePath)
info('','')
info('Script quits.', 'Bye')
#####################################################################################################
exit()