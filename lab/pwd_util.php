<script src="/themes/textpress/assets/js/jquery.js"></script>
<script type="text/javascript" src="/themes/textpress/assets/js/bootstrap.min.js"></script>
<link href="/themes/textpress/assets/css/pwd_bootstrap.min.css" rel="stylesheet">
<link href="/themes/textpress/assets/css/font-awesome.min.css" rel="stylesheet">

<style>

.thBig{
	width: 160px !important;
}

.thSmall{
	width: 100px !important;
}

.inputSizel{
  width: 150px !important;
  height: 34px !important;
}
.inputSize2{
  width: 250px !important;
  height: 34px !important;
}
.inputSize3{
  width: 350px !important;
}
.inputSize4{
  width: 450px !important;
}
.inputSize5{
  width: 550px !important;
}


.panelForm{
  align:center !important;
}

.tdNo{
  width:20px;
}
.tdKey{
  //width:100px;
}
.tdValue{
  //width:100px;
}
.tdCopy{
  width:30px;
  aligh:right;
}
</style>


<script language="javascript">
var $xinfo=jQuery.noConflict();

$xinfo("document").ready(function(){
	
	var mCount = 0;
	init();
	


	
	$xinfo("#idBtnSearch").click(function(){
		var key = $xinfo('#idInputKey').val();
		$xinfo('#idInputValue').val("");
		
		$xinfo.ajax({
			type:"get",
			url:"/lab/pwd_read.php",
			data:{"key":key, "random":Math.random},
			dataType:"json",
			beforeSend:function(){
				showMessageBox(1, '正在查询……');
			},
			complete: function(){
				hideMessageBox();
			},
			success: function(dd){
				
				$xinfo('#idTableResult').remove();
				createResultTableHeader(true);
				kvs = eval(dd);
				mCount = kvs.length;
				for (var i=0; i<mCount; i++){
				// ignore smsDataset[0] which is smsid
					createResultTableContent(kvs[i][0], kvs[i][1], kvs[i][2]);
				}

				createResultTableHeader(false);
				
			},
			error:function(msg){
				showMessageBar(3, "No record found");
				$xinfo('#idTableResult').remove();
			}
		});
		
	});
	
	$xinfo("#idBtnModify").click(function(){
		var key = $xinfo('#idInputKey').val();
		var value = $xinfo('#idInputValue').val();
		key = key.trim();
		if (key==""){
			alert("请输入Key");
			$xinfo('#idInputKey').val("")
			return;
		}
		
		$xinfo.ajax({
			type:"post",
			url:"/lab/pwd_modify.php",
			data:{"key":key, "value":value, "action":"modify"},
			dataType:"text",
			success:function(dd){
				if (dd=="success"){
					showMessageBar(0, "Added/Modified successfully");
				}else{
					showMessageBar(3, dd);
				}
			},
			error:function(msg){
				showMessageBar(3, msg.status + "-" + msg.statusText);
			}
		});
	});
	
	$xinfo("#idBtnDelete").click(function(){
		//var dropKeys = new Array();
		var dropKeys = "-1";
		var $check_boxes = $xinfo("input[type='checkbox']:checked");
		if($check_boxes.length<=0){ 
			alert('先选择要删除的行，再点删除。');
			return;
		}  
		$check_boxes.each(function(){  
			//dropKeys.push($xinfo(this).val());  
			dropKeys += "," + $xinfo(this).val(); 
		});  
		//alert(dropKeys);
	
		$xinfo.ajax({
			type:"post",
			url:"/lab/pwd_modify.php",
			data:{"keys":dropKeys, "action":"delete"},
			dataType:"text",
			success:function(dd){
				if (dd=="success"){
					showMessageBar(0, "Deleted successfully");
					$xinfo('#idInputKey').val("");		
					$xinfo('#idInputValue').val("");		
				}else{
					showMessageBar(3, dd);
				}
			},
			error:function(msg){
				showMessageBar(3, msg.status + "-" + msg.statusText);
			}
		});
	});
	
	function createResultTableHeader(isHead){
		if (isHead){
			var ele = "<table class='table table-hover' id='idTableResult'>";
			ele = ele + "<thead>";
			ele = ele + "<tr>";
			ele = ele + "<th class='tdNo'>Sel</th>";
			ele = ele + "<th class='tdKey'>Key</th>";
			ele = ele + "<th class='tdValue'>Value</th>";
			ele = ele + "<th class='tdCopy'>Copy</th>";
			ele = ele + "</tr>";
			ele = ele + "</thead>";
			ele = ele + "<tbody id='idTbodyContent'>";
			$xinfo('#idDivResult').append(ele);
		}else{
			var ele = "<tr>";
			ele = ele + "<td colspan='2'>共 <kbd>" + mCount + "</kbd> 条</td>";
			ele = ele + "<td class='text-right' colspan='2'><kbd>支持特殊字符</kbd></td>";
			ele = ele + "</tr>";
			$xinfo('#idTbodyContent').append(ele);	
			ele = "</tbody></table>";
			$xinfo('#idDivResult').append(ele);			
		}
	}
	
	function createResultTableContent(idx, key, value){
		var ele = "<tr id='idOneTR" + idx + "'>";
		ele = ele + "<td><input type='checkbox' name='nCheckboxKV' id='idCheckboxKV" + idx + "' value='" + idx + "'></td>";
		ele = ele + "<td>" + key + "</td>";
		ele = ele + "<td id='idValue" + idx + "'>********" + "<input style='width:1px;height:1px;opacity: 0;position: absolute;' readonly type='text' id='idInputCopyValue" + idx + "' value='" + value + "'></td>";
		ele = ele + "<td><i class='fa fa-copy' id='idCopyIcon" + idx + "' data-clipboard-target='idValue" + idx + "' style='cursor: pointer;'></i></td>";
		ele = ele + "</tr>";
		$xinfo('#idTableResult').append(ele);	
		
		var objCopyIcon = $xinfo("#idCopyIcon" + idx); 
		objCopyIcon.click(function(event){
			var objValue = $xinfo("#idInputCopyValue" + idx);

			objValue.focus();
			objValue.select();
			
			document.execCommand("copy", false, objValue.select());
			showMessageBar(0, "Password is saved in clipboard.");
			
		});

		
		var tr = $xinfo("#idOneTR" + idx); 
		tr.click(function(event){
			//fillInputForm(key.encodeHTML(), value.encodeHTML());
			fillInputForm(key, value);
		});
		

	}
	
	function fillInputForm(key, value){
		$xinfo('#idInputKey').val(key);
		$xinfo('#idInputValue').val(value);
	}
	
	function init(){
		//
	}

});

String.prototype.trim=function(){
	return this.replace(/(^\s*)|(\s*$)/g, "");
}
String.prototype.ltrim=function(){
	return this.replace(/(^\s*)/g,"");
}
String.prototype.rtrim=function(){
	return this.replace(/(\s*$)/g,"");
}

String.prototype.decodeHTML=function(){
	return this.replace(/&amp;/gi, '&').replace(/&#39;/gi, "'").replace(/&quot;/gi, '"').replace(/&lt;/gi, '<').replace(/&gt;/gi, '>');
}

String.prototype.encodeHTML=function(){
	return this.replace(/&/gi, '&amp;').replace(/'/gi, "&#39;").replace(/"/gi, '&quot;').replace(/</gi, '&lt;').replace(/>/gi, '&gt;');
}

function showMessageBar(type, message){
	//0-success; 1-information; 2-alert; 3-danger
	var alertType = 'alert-info';
	
	switch(type){
		case 0:
			alertType = 'alert-success';
			break;
		case 1:
			alertType = 'alert-info';
			break;
		case 2:
			alertType = 'alert-warning';
			break;
		case 3:
			alertType = 'alert-danger';
			break;
		default:
			alertType = 'alert-success';
	}
		
	var now = new Date();
	var eID = now.getSeconds();
	var ele = '';
	ele = ele + '<div class="alert ' + alertType + ' alert-dismissible fade in" role="alert" id="alert' + eID + '">';
	ele = ele + '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>';
	ele = ele + message;
	ele = ele + '</div>';
	
	$xinfo('#idMessageBarEle').append(ele);
	var idAlert = $xinfo('#alert' + eID).alert();
	$xinfo('html,body').animate({scrollTop: $xinfo('#idMessageBarEle').offset().top},'slow');
	
	window.setTimeout(function() {
		idAlert.fadeTo(500, 0).slideUp(500, function(){
			$xinfo(this).remove(); 
		});
	}, 1000);
}	

	
function showMessageBox(type, message){
	//0-success; 1-information; 2-alert; 3-danger
	var alertType = 'alert-info';
	
	switch(type){
		case 0:
			alertType = 'alert-success';
			break;
		case 1:
			alertType = 'alert-info';
			break;
		case 2:
			alertType = 'alert-warning';
			break;
		case 3:
			alertType = 'alert-danger';
			break;
		default:
			alertType = 'alert-success';
	}
	$xinfo("#messageContentID").text(message);
	$xinfo("#alertTypeID").attr("class", "modal-dialog alert " + alertType);
	$xinfo("#messageBoxClicker").trigger("click");
}

function hideMessageBox(){
	//$xinfo("#messageBoxID").hide();
	$xinfo("#idBtnCloseMsgBox").trigger("click");
}



</script>
<section class="content">
<div class="pwd_div">
	<div class="page-header" id="header">
		<div class="nav nav-pills pull-right" role="tablist">
		</div>
		<h1><i id="idIAccountIcon" class="fa fa-th"></i> Password Library </h1>
		
		
	</div>

	<div class="page-header" id="header">
		<x id="idMessageBarEle">
		</x>
		<div id="idDivForm">
			<form class="form-inline" role="form" name="">
				<div class="panel panel-default panelForm">					
					<div class="panel-body">
						<div class="input-group">  
							<div class="input-group-addon"><i id="idIAccountIcon" class="fa fa-list"></i></div>  
							<input class="inputSizel form-control" type="text" placeholder="账户" title="账户" maxlength="200" id="idInputKey">
						</div>
						<div class="input-group">  
							<div class="input-group-addon"><i class="fa fa-key"></i></div>  
							<input class="inputSize2 form-control" type="password" placeholder="密码" title="密码" maxlength="200" id="idInputValue" />
						</div>				
						<div class="input-group">
							&nbsp;&nbsp;<button type="button" class="btn btn-primary" id="idBtnSearch" name="save">模糊查询</button>
							&nbsp;&nbsp;<button type="button" class="btn btn-success" id="idBtnModify" name="save">添加/修改</button>
							&nbsp;&nbsp;<button type="button" class="btn btn-danger" id="idBtnDelete" name="save">删除</button>

						</div>
					</div>

				</div>
			</form>
		</div>
		<div id="idDivResult">
		</div>
	
	</div>

</div>

<div>
	<div id="messageBoxClickerDiv" style="display:none">
		<a href="#" id="messageBoxClicker" data-toggle="modal" data-target="#messageBoxID">xx</a>
	</div>
	<div class="modal fade" id="messageBoxID" tabindex="-1" role="dialog" aria-labelledby="modeTitle" aria-hidden="true">
		<button type="button" class="close" style="display:none" data-dismiss="modal" id="idBtnCloseMsgBox"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		<div class="modal-dialog alert alert-success" role="alert" id="alertTypeID">
				<div class="modal-body" id="messageContentID"></div>
		</div>
	</div>
</div>
</section>