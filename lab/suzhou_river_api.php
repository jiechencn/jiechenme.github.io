<?php
include ("mysql_db.php");
$con = new DbConn();


$op = "";
if (isset($_GET["op"])){
	$op = $_GET["op"];
}


switch($op){
	case "insertrivers":{
		echo insertRivers();
		break;
	}
	default:{
		echo "-1";
	}
}


function insertRivers(){
	global $con;
	$amount = 0;
	try{
		$rivers = '';
		$t = '';
		$rivers = $_POST["rivers"];
		$t = $_POST["date"];
		//$t='2019-06-11';
		//$rivers = '[["2019-06-11", "\u4e94\u6cfe\u6d5c\u95f8", "1.88", "7.54", "26.5", "632", "16"], ["2019-06-11", "\u6821\u573a\u6865", "5.42", "7.99", "26.2", "602", "20"], ["2019-06-11", "\u5a04\u95e8\u6865", "5.71", "8.08", "26.3", "604", "31"], ["2019-06-11", "\u767d\u83b2\u6865", "4.00", "7.36", "26.2", "633", "20"], ["2019-06-11", "\u4e2d\u5e02\u6cb3", "5.42", "7.75", "26.5", "624", "8"], ["2019-06-11", "\u5e72\u5c06\u6cb3", "2.51", "7.51", "26.3", "617", "12"], ["2019-06-11", "\u918b\u574a\u6865", "4.54", "7.59", "26.1", "611", "17"], ["2019-06-11", "\u80e1\u53a2\u4f7f\u6865", "4.20", "7.27", "26.2", "676", "8"], ["2019-06-11", "\u88d5\u68e0\u6865", "2.99", "7.30", "26.5", "632", "10"], ["2019-06-11", "\u6842\u82b1\u516c\u56ed", "4.29", "7.70", "26.5", "672", "14"], ["2019-06-11", "\u6cf0\u8ba9\u6865", "5.48", "7.54", "26.6", "622", "23"]]';
		$riversArr = json_decode($rivers, true);

		$sql = 'select count(*) from suzhou_river where rptdate="' . $t . '"';
		
		$res = $con->getRs($sql);
		
		if ($res[0]<=0){ 
			// no existing data in database, so will insert 
			for($i=0;$i<count($riversArr);$i++){
				try{
					$sql = 'insert into suzhou_river(rptdate, river, o, p, c, e, d ) values ("' . $riversArr[$i][0] . '","' . $riversArr[$i][1] . '",' . $riversArr[$i][2] . ',' . $riversArr[$i][3] . ',' . $riversArr[$i][4] . ',' . $riversArr[$i][5] . ',' . $riversArr[$i][6] . ')';
					$con->Execute($sql);
					$amount++;
				}catch(Exception $sube){
					// do nothing
				}
			}
		}
		return $amount ;
	}catch(Exception $e){
		$con->Quit();
		return $amount ;
	}
	
}

$con->Quit();
?>