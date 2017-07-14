<?php
/**
 * @author Dhammika Gunaratne
 * @email dhammika97@gmail.com
 * @copyright 2014
 */
class database{

private $db_host = "localhost:3306";
private $db_user = "root";
private $db_pass = "";
private $db_name = "daakor";

//require_once 'Config.php';

private $con ;
private $results = array();
private $insertid ;
private $json;
private $numrows;

	function __construct(){
		$this->connect();	
	}

	function connect(){
		if(!$this->con){
			$myconn = mysqli_connect($this->db_host,$this->db_user,$this->db_pass,$this->db_name);
			$this->con = $myconn;
		}else{
			return true;	
		}
	}
	
	function disconnect(){
		if($this->con){
			if(mysqli_close($this->con)){
				return true;
			}else{
				return false;	
			}
		}
	}
	
	
	function select($table,$rows,$where,$order,$limit){
		$q = 'select '.$rows.' from '.$table;
		if($where!=""){
			$q .= ' where '.$where;
		}
		if($order!=""){
			$q .= ' order by '.$order;
		}
		if($limit!=""){
			$q .= ' limit '.$limit;
		}
		// echo $q;
		$query = mysqli_query($this->con,$q);
		$numRows = mysqli_num_rows($query);

		$this->numrows = $numRows;
		if($numRows){
			for($i=0; $i<$numRows; $i++){
				$r= mysqli_fetch_array($query);
				$key = array_keys($r);
				for($x=0; $x<count($key); $x++){
					if($numRows > 1){
						$this->results[$i][$key[$x]] = $r[$key[$x]];
					} else if ($numRows < 1){
						$this->results = null;
					} else{
						$this->results[$key[$x]] = $r[$key[$x]];	
					}
				}
			}
		}else{
			return false;	
		}
		mysqli_free_result($query);
		$this->disconnect();
	}
	
	function selectJson($table = null,$rows = null,$where = null,$order = null,$limit = null,$group = null){
		$q = 'select '.$rows.' from '.$table;
		if($where!=""){
			$q .= ' where '.$where;
		}
		if($order!=""){
			$q .= ' order by '.$order;
		}
		if($group!=""){
			$q .= ' group by '.$group;
		}
		if($limit!=""){
			$q .= ' limit '.$limit;
		}
		
		//echo $q;
		$query = mysqli_query($this->con,$q);
		$numRows = mysqli_num_rows($query);
		$this->numrows = $numRows;
		if($numRows){
			while($row=mysqli_fetch_assoc($query)){
				$show[] = $row;	
			}
			$this->json = json_encode($show);
		}
		
		mysqli_free_result($query);
		$this->disconnect();
	}
	
	function getJson(){
		return $this->json;	
	}
	
	function getNumRows(){
		return $this->numrows;
	}
	
	function getResults(){
		return $this->results;
	}
	
	function getInsertId(){
		return $this->insertid;
	}
	
	
	function insert($table,$values,$rows){
		$insert = "insert into ".$table;
		if($rows != ""){
			$insert .= " (".$rows.")";
		}
		if($values != ""){
			$insert .= " values (".$values.");";
		}
		// echo $insert;die();
		$ins = mysqli_query($this->con,$insert);
		$this->insertid = mysqli_insert_id($this->con);
		if($ins){
			return true;
		}else{
			return false;	
		}
		$this->disconnect();
	}
	
	
	
	function delete($table,$where){
		if($where != ""){
			$del = 'delete from '.$table.' where '.$where;
		}else{
			$del = 'delete '.$table;
		}
		$delete = mysqli_query($this->con,$del);
		if($delete){
			return true;
		}else{
			return false;
		}
		$this->disconnect();
	}
	

	
	public function update($table,$rows,$where){
        $update = 'update '.$table.' set ';
		$keys = array_keys($rows);
		for($i=0; $i<count($rows); $i++){
			if(is_string($rows[$keys[$i]])){
				$update .= $keys[$i].'="'.$rows[$keys[$i]].'"';
			}else{
				$update .= $keys[$i].'='.$rows[$keys[$i]];
			}
			if($i != count($rows)-1){
				$update .= ',';
			}
		}
		$update .= ' where '.$where;
		//echo $update;
		if($up = mysqli_query($this->con,$update)){
			return true;	
		}else{
			return false;	
		}
		$this->disconnect();
    }
    public function updatePreparedStatment($query){
    
    	if($up = mysqli_query($this->con,$query)){
    		return true;
    	}else{
    		return false;
    	}
    	$this->disconnect();
    }
	
	function tableExists($table){
		$tablesInDb = @mysqli_query($this->con,'show tables from '.$this->db_name.' like "'.$table.'"')	;
		if($tablesInDb){
			if(mysqli_num_rows($tablesInDb)==1){
				return true;
			}else{
				return false;
			}
		}
	}	
	
}
?>
