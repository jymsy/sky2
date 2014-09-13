<?php
namespace Sky\db;
use Sky\base\Creatable;

class DBCommand{
	/**
	 * 返回数据的行数限制的缺省值
	 * @var int
	 */
	const DEFAULT_LIMIT=1000;
	/**
	 * 修改或删除表的行数限制的缺省值
	 * @var int
	 */
	const DEFAULT_LIMIT_MODIFY=1;
	
	private $isReadOnly=false;
	private $sql;
	private $table;
	private $operation;
	private $token;
	private $_text;
	private $lastPdoInstance;
	/**
	 * 
	 * @var array
	 */
	private $options=array();
		
	public function __construct($table, array $options=array()){
		$this->table=$table;
		$this->options($options);
	}
	
	public function __toString(){
		if (is_null($this->sql)){
			$m="_build".ucfirst($this->operation);			
			return $this->sql=$this->$m();
		}else
			return $this->sql;
	}
	
	public function setText($sql){
		if(!empty($this->_text))
			return $this->_text;
		if (isset($this->options['bindParams'])) {
			$keys=array_keys($this->options['bindParams']);
			$values=array_values($this->options['bindParams']);
			for($i=0;$i<count($keys);$i++){
				$keys[$i]=':'.$keys[$i];
				if (gettype($values[$i])=='string') {
					$values[$i]="'".$values[$i]."'";
				}
			}
			return $this->_text=str_replace($keys, $values, $sql);
		}else 
			return $this->_text=$sql;
	}
	
	/**
	 * 
	 * @param string $sql
	 * @param boolean $isReadOnly
	 * @return self
	 */
	public static function create($sql,array $bindParams=null,$isReadOnly=NULL,$options=array()){
		$instance=new self(null,$options);
		$instance->sql=$sql;
		if (!is_null($bindParams)){
			$instance->bind($bindParams);
		}
		if (is_null($isReadOnly)){
			$instance->isReadOnly=(strncasecmp($sql, "SELECT", 6)==0);
		}else
			$instance->isReadOnly=($isReadOnly);
		return $instance;
	}
	
	public function options(array $options){
		foreach ($options AS $k=>$v){
			$this->$k($v);
		}
		return $this;
	}
	
	public function token($token){
		$this->token=$token;
		return $this;
	}
	
	public function group($group){
		$this->options['group']=$group;
		return $this;
	}
	
	public function limit($limit){
		$this->options['limit']=intval($limit);
		return $this;
	}
	
	public function offset($offset){
		$this->options['offset']=intval($offset);
		return $this;
	}
	
	public function having($having){
		$this->options['having']=$having;
		return $this;
	}
	
	public function where(/*String|array,...*/){
		foreach (func_get_args() AS $cond){
			if (isset($this->options['where'])){
				$this->options['where']=array_merge($this->options['where'],(array)$cond);
			}else $this->options['where']=(array)$cond;
		}
		return $this;
	}
	
	public function order($order){
		$this->options['order']=$order;
		return $this;
	}
	
	public function join($table,$on,$joinMode="LEFT"){
		if (isset($this->options['join']))
			$this->options['join'][]=array($table,$on,$joinMode);
		else
			$this->options['join']=array(array($table,$on,$joinMode));
		return $this;
	}
	
	public function select($columns="*",$atTable=null){
		if (!is_null($atTable)){
			self::_formatColumns($columns, $atTable);
		}
		if (isset($this->options['select']))
			$this->options['select']=array_merge($this->options['select'],(array)$columns);
		else
			$this->options['select']=(array)$columns;
		$this->operation="Select";
		$this->isReadOnly=true;
		return $this;
	}
	
	private static function _formatColumns(&$columns,&$table){
		if (is_array($columns)){
			foreach ($columns AS &$column){
				self::_formatColumns($column, $table);
			}
		}else {
			$columns=$table.".".str_replace(",", ",$table.", $columns);
		}
	}
	
	public function insert($data,$insertMode="INSERT"){
		$this->operation="Insert";
		$this->options['insertMode']=$insertMode;
		$this->options['data']=$data;
		$this->options['bindParams']=$data;
		return $this;
	}
	
	public function update($data){
		$this->operation="Update";
		$this->options['data']=$data;
		$this->options['bindParams']=$data;
		return $this;
	}
	
	public function delete(){
		$this->operation="Delete";
		return $this;
	}
	
	public function bind(array $params){
		$this->options['bindParams']=
			isset($this->options['bindParams'])
				?array_merge($this->options['bindParams'],$params)
				:$params;
		return $this;
	}
	
	public function find(array $data,$columns="*"){
		$where=array();
		foreach (array_keys($data) AS $k){
			$where[]="`$k`=:$k";
		}
		return $this->where($where)->bind($data)->select($columns);
	}
	
	/**
	 * 
	 * @return \PDOStatement
	 */
	private function _exec(){
		$options=$this->options;

		$pdo=ConnectionPool::pdo($this->getToken());
		if (isset($this->options['bindParams'])){

			$stmt=$pdo->prepare($this->__toString()) or $this->_throwQueryException($pdo->errorInfo());
			foreach ($this->options['bindParams'] AS $k=>$v){
				$stmt->bindValue(":$k", $v,self::_paramType($v));
			}
			if(SKY_DEBUG)
				\Sky\Sky::beginProfile($this->setText($this),'system.db');
			
			$stmt->execute() or $this->_throwQueryException($stmt->errorInfo());
		}else{
			if(SKY_DEBUG)
				\Sky\Sky::beginProfile($this->setText($this),'system.db');
			$stmt=$pdo->query($this->__toString()) or $this->_throwQueryException($pdo->errorInfo());
		}

		if(SKY_DEBUG)
			\Sky\Sky::endProfile($this->setText($this),'system.db');
		return $stmt;
	}
	
	private function getToken(){
		if(!$this->token){
			$this->token='TVOS';
		}
		return $this->token.($this->isReadOnly?"_SLAVE":"_MASTER");
	}
	
	/**
	 * 获取数据库连接实例
	 * @return \PDO
	 */
	public function getPdoInstance(){
		return ($this->lastPdoInstance=ConnectionPool::pdo($this->getToken()));
	}
	
	private function _throwQueryException($errorInfo=null){
		if(SKY_DEBUG)
			\Sky\Sky::endProfile($this->setText($this),'system.db');
		throw new \Exception(isset($errorInfo[2])?$errorInfo[2]:'NULL'/*var_export($errorInfo,true).PHP_EOL.$this->__toString()*/);
	}
	
	/**
	 * 
	 * @return int the number of rows affected by the last SQL statement
	 */
	public function exec(){
		$stmt=$this->_exec();
		$rowCount=$stmt->rowCount();
		$stmt->closeCursor();
		return $rowCount;
	}
	
	public function lastInsertID(){
		return $this->lastPdoInstance->lastInsertId();
	}
	
	
	public function toValue(){
		$stmt=$this->_exec();
		$row=$stmt->fetch(\PDO::FETCH_NUM);
		$stmt->closeCursor();
		if ($row){
			return $row[0];
		}else return null;
	}
	
	public function toObject($className=null){
		$stmt=$this->_exec();
		$row=$stmt->fetch();
		$stmt->closeCursor();
		if ($row){
			return is_null($className)?$row:$className::autoCreate($row);
		}else return null;
	}
	
	public function toList($className=null){
		$stmt=$this->_exec();
		$list = array();
		while ($row = $stmt->fetch()){
			array_push($list, $row);
		}
		$stmt->closeCursor();
		return $list;
	}
	
	private function _buildSelect(){
		$options=$this->options;
		$sqlComponents=array("SELECT",implode(",", $options['select']),"FROM",$this->table);
		if (isset($options["join"])){
			foreach ($options["join"] AS $t){
				$sqlComponents[]=$t[2]." JOIN ".$t[0]." ON ".$t[1];
			}
		}
		if (isset($options['where']) && count($options['where'])) $sqlComponents[]="WHERE ".implode(" AND ", $options['where']);
		if (isset($options['group'])) $sqlComponents[]="GROUP BY ".$options['group'];
		if (isset($options['having'])) $sqlComponents[]="HAVING ".$options['having'];
		if (isset($options['order'])) $sqlComponents[]="ORDER BY ".$options['order'];
		$sqlComponents[]="LIMIT ".(isset($options['limit'])?$options['limit']:self::DEFAULT_LIMIT);
		if (isset($options['offset'])) $sqlComponents[]="OFFSET ".$options['offset'];
		return implode(" ", $sqlComponents);
	}
	
	private function _buildInsert(){
		$options=$this->options;
		$cols="";
		$vals="";
		foreach ($options['data'] AS $k=>$v){
			$cols.="`$k`,";
			$vals.=":$k,";
		}
		return $options['insertMode']." INTO ".$this->table ."(".substr($cols, 0,-1).")"." VALUES(".substr($vals, 0,-1).")";
	}
	
	private function _buildUpdate(){
		$options=$this->options;
		$set="";
		foreach (array_keys($options['data']) AS $k){
			$set.="`".$k."`=:".$k.",";
		}
		return "UPDATE ".$this->table
		." SET ".substr($set,0,-1)
		." WHERE ".implode(" AND ", $options['where']);
		//." LIMIT ".(isset($options['limit'])?$options['limit']:self::DEFAULT_LIMIT_MODIFY).";";
	}
	
	private function _buildDelete(){
		$options=$this->options;
		return "DELETE FROM ".$this->table
			." WHERE ".implode(" AND ", $options['where'])
			." LIMIT ".(isset($options['limit'])?$options['limit']:self::DEFAULT_LIMIT_MODIFY).";";
	}
	
	private static function _paramType($var){
		switch (gettype($var)){
			case "boolean":
				return \PDO::PARAM_BOOL;
			case "integer":
				return \PDO::PARAM_INT;
			case "NULL":
				return \PDO::PARAM_NULL;
			default:
				return \PDO::PARAM_STR;	
		}
	}
}