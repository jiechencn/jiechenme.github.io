<?php
include ("mysql_db.php");
global $con;
$con = new DbConn();

$pKey = rmHtmlTag($_POST['key']);
$pKeys = rmHtmlTag($_POST['keys']);
$pValue = rmHtmlTag($_POST['value']);
$pAction = rmHtmlTag($_POST['action']);


if ($pAction=='delete'){
	$sqlDel = "delete from pwd_library where id in (". $pKeys .")";
	$con->Execute($sqlDel);
}else{// modify
	if ($pKey==""){
		echo "Error: null key";
		die();
	}else{
		$sqlDel = "delete from pwd_library where entry = '". $pKey ."'";
		$con->Execute($sqlDel);
		$sqlUpd = "insert into pwd_library(entry, entryvalue) values ('". $pKey ."', '". $pValue ."')";
		$con->Execute($sqlUpd);
	}
}


echo "success";

$con->Quit();

?>