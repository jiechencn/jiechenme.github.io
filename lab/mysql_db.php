<?php
error_reporting(E_ALL);

/*
 *PHP管理MYSQL的数据库类
 使用方法：$dbConn = new DbConn([$数据库名(可选)]);
 允许返回的方法：
  Execute($sql)                            //执行SQL语句，没有返回值;
  getRs($sql)                            //返回查询语句的记录，一条;
  getRsArray($sql)                        //返回记录集数组;
  getGoPageRs($sql,$maxline,$offset)    //返回翻页程序所需要的数组;
  Quit()                                //关闭数据库连接
  getParameter()                        //返回分页以及排序的参数
  getInsertID()                            //返回插入的最后一条记录

 允许返回的属性：
  $num_rows;                            //执行操作的记录集的数量;
  $affected_rows                        //执行操作影响的记录数;
*/


class DbConn{

var $servername="qdm170255295.my3w.com";       // 数据库连接服务地址
//var $servername="localhost";       // 数据库连接服务地址
// for localhost
var $dbname="qdm170255295_db";              // 连接数据库名
var $username = "qdm170255295";            // 登陆用户


var $password = "helloworld";                // 登陆密码
var $conn;                         // 数据库连接指针
var $num_rows;                     // 返回的条目数
var $query_id;                     // 执行query命令的指针
var $affected_rows;                // 传回query命令所影响的列数目
var $insertid;               // 最新插入记录的ID号

//下面是和分页程序有关的变量；
var $offset;                       //分页偏移量
var $maxline = 30;                 //显示行数

var $tpages;                       //总页数
var $total;                        //总记录数



 function DbConn(){ //构造函数，建立数据库连接，可以指定连接到其他数据库

        $this->conn = @mysql_connect($this->servername, $this->username, $this->password) or die(mysql_error());
        mysql_query("set names 'utf8'");
        //if($dbname!=""){
        //        $this->dbname = $dbname;
        //}
        if(!mysql_select_db($this->dbname)){
                $this->getErr("数据库链接失败");
        }

        return $this->conn;
 }

 function Quit(){    //关闭数据库连接
        mysql_close($this->conn);
 }
 function Execute($sql){
        $query_id = $this->query($sql);
        $this->affected_rows=mysql_affected_rows($this->conn);
        $this->free_result($query_id);
 }
 function getRs($sql) {   //只返回一条记录
        $query_id=$this->query($sql);
        $returnarray=mysql_fetch_array($query_id);
        $this->num_rows=mysql_num_rows($query_id);
        $this->free_result($query_id);
        return $returnarray;
 }
 function getRsArray($sql) {   //只返回所有记录
        $query_id=$this->query($sql);
        $this->num_rows=mysql_num_rows($query_id);
        for($i=0;$i<$this->num_rows;$i++){
                $returnarray[$i]=mysql_fetch_array($query_id);
        }
        $this->free_result($query_id);
        return $returnarray;
 }
/**
 * 调用方式，可以设置$maxline显示的条数，也可以不设置，默认是12条记录，
 * 如果不设置$maxline调用就直接使用 getGopageRs($sql);
 * 否则需要调用 getGopageRs($sql,$maxline);
*/
 function getGopageRs($sql,$maxline=""){     //返回翻页程序 需要传递sql语句,每页显示条数，以及偏移量
        global $page;
        if(empty($page)){
                $page=1;
        }
        if($maxline!=""){
                $this->maxline = $maxline;
        }
        $this->offset = ceil(($page-1)*$this->maxline);

        $query_id=$this->query($sql);
        $this->total =mysql_num_rows($query_id); //计算出总记录数
        $this->free_result($query_id);

        $sql= $sql." LIMIT ".$this->offset.",".$this->maxline;
        $query_id=$this->query($sql);
        $this->num_rows=mysql_num_rows($query_id);       //当前页的记录数
        for($i=0;$i<$this->num_rows;$i++){
                $returnarray[$i]=mysql_fetch_array($query_id);
        }
        $this->free_result($query_id);

        $this->tpages = ceil($this->total/$this->maxline); //计算总页数
        return $returnarray;
 }
 /**
  *参数列表字符串
 */
 function getParameter(){
        global $deferentparameter;
        global $selfvariable;
        global $page;
        $parameter = "page=".$page.$selfvariable.$deferentparameter;
        return $parameter;
        }

 function getInsertID(){ //得到最新插入的一条记录的自增ID号
    $this->insertid = mysql_insert_id();
    if (!$this->insertid){
        $this->getErr("无法得到最新的记录ID！");
    }
    return $this->insertid; 
 }

 function query($sql){   // 执行queyr指令
        $this->query_id=@mysql_query($sql,$this->conn);
        if(!$this->query_id){
        $this->getErr("错误的SQL语句: ".$sql);
        }
        return $this->query_id;
 }

 function num_rows($queryid) { //返回记录数
        $this->num_rows = mysql_num_rows($queryid);
        return $this->num_rows;
 }
 function free_result($query_id){  // 释放query资源
        @mysql_free_result($query_id);
 }

   function getErr($errmsg)  // 数据库出错，无法连接成功
   {
            $msg="<h3>-数据库错误</h3><br>";
            $msg.=$errmsg;
            echo $msg;
            die();
   }



};



function rmHtmlTag($content){
  $content = str_replace ( '&', '&amp;', $content );
  $content = str_replace ( '\'', '&#039;', $content);
  $content = str_replace ( '\\', '&#92', $content);
  
  $content = str_replace ( '"', '&quot;', $content );
  $content = str_replace ( '<', '&lt;', $content );
  $content = str_replace ( '>', '&gt;', $content );

  return $content;
}







?>