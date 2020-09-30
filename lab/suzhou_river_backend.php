<?php
include ("mysql_db.php");
global $con;
$con = new DbConn();

function getDataByDay($con){
	$myday = $_GET["day"];
	
	$sqlDay = "select rptdate, river, o, p, c, e, d from suzhou_river ";
	if ($myday == "9999-99-99"){
		$sqlDay = $sqlDay . " where rptdate = (select distinct rptdate from suzhou_river order by rptdate desc limit 1) order by river";
	} else {
		$sqlDay = $sqlDay . " where rptdate = '" . $myday . "' order by river";
	}
	
	$dayRivers = $con->getRsArray($sqlDay, 9999999999999); 
	return json_encode($dayRivers);
}

function getDayList($con){
	$sql = "select distinct rptdate from suzhou_river order by rptdate desc limit 365";
	$days = $con->getRsArray($sql, 9999999999999); 
	return json_encode($days);
}
function getMonthList($con){
	$sql = "select distinct left(rptdate, 7) as mon from suzhou_river order by mon desc";
	$months = $con->getRsArray($sql, 9999999999999); 
	return json_encode($months);
}

function getMonthData($con){
	$mymonth = $_GET["month"];
	
	$sql = "SELECT rptdate, river, o, p, c, e, d FROM suzhou_river where rptdate between date_sub(now(),interval " . $mymonth . " month) and now() order by river, rptdate";

	$monthRivers = $con->getRsArray($sql, 9999999999999); 
	if ($con->num_rows<=0)
		return "error";
	else
		return json_encode($monthRivers);
}


/*
op: showdaydata,
	showmonthdata,
	showyeardata,
	showdaylist,
	showmonthlist,
	showmonthdata,
	

*/

if (isset($_GET["op"])){
	$by = $_GET["op"];
}

switch($by){
	case "showdaylist":{
		echo getDayList($con);
		break;
	}
	case "showdaydata":{
		echo getDataByDay($con);
		break;
	}
	case "showmonthlist":{
		echo getMonthList($con);
		break;
	}
	case "showmonthdata":{
		echo getMonthData($con);
		break;
	}
	default:{
		echo "error";
	}
}

$con->Quit();
?>