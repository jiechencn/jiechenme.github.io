<?php
include ("mysql_db.php");
$con = new DbConn();
function getDataByPage($page, $rows){
	global $con;
	$sql = "select smsid, smstime, /*concat(left(smsteacher, 1), '**')*/ '***', replace(replace(smsbody, '陈梓澜', '***'),'方洲小学','**学校') from suzhou_schoolsms order by smstime desc";
	$smses = $con->getGopageRs($sql, $rows); 
	return json_encode($smses);
}

$page = $_GET["page"];
$rows = $_GET["rows"];
echo getDataByPage($page, $rows);
$con->Quit();
?>