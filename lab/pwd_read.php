<?php
include ("mysql_db.php");
global $con;
$con = new DbConn();

$pKey = rmHtmlTag($_GET['key']);

$whereClause = "";
if ($pKey!="")
	$whereClause = " where entry like '%". $pKey . "%'";

$sqlPwd = "select id, entry, entryvalue from pwd_library" . $whereClause;

$allPwds = $con->getRsArray($sqlPwd, 9999999999999); 

if ($con->num_rows==0)
	echo "result_0"; // no data found
else
	echo json_encode($allPwds);

$con->Quit();
?>