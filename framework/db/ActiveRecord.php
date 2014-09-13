<?php
namespace Sky\db;

use Sky\base\Creatable;
use Sky\base\Model;

abstract class ActiveRecord extends Model implements Creatable{
	const MODE_INSERT="INSERT";
	const MODE_INSERT_REPLACE="REPLACE";
	const MODE_INSERT_IGNORE="INSERT IGNORE";
	const MODE_INSERT_DELAYED="INSERT DELAYED";
	/**
	 * @var ConnectionPool
	 */
	public static $db;
	/**
	 * 
	 * 存储数据
	 * @var array
	 */
	private $_record;
	/**
	 * 
	 * @var boolean
	 */
	private $_isNewRecord;
	
	private static $_models=array();			// class name => model
	
	/**
	 * 返回指定AR类的model。
	 *
	 * 每个派生类必须重写此方法，例如：
	 * <pre>
	 * public static function model($className=__CLASS__){
	 *     return parent::model($className);
	 * }
	 * </pre>
	 *
	 * @param string $className active record 类名.
	 * @return ActiveRecord 模型实例
	 */
	public static function model($className=__CLASS__){
		if(isset(self::$_models[$className]))
			return self::$_models[$className];
		else{
			return self::$_models[$className]=new $className(null);
// 			$model->getDbConnection();
// 			return $model;
		}
	}
	
	/**
	 * 返回active record使用的数据库连接。
	 * 默认使用'db'应用组件作为数据库连接。
	 * 如果你想使用不同的数据库连接的话你可以重写该方法。
	 * @return ConnectionPool active record使用的数据库连接。
	 */
	public static function getDbConnection(){
		if(static::$db!==null)
			return static::$db;
		else{
			return static::$db=\Sky\Sky::$app->getDb();
		}
	}
	
	public function __isset($attribute_name){
		return array_key_exists($attribute_name, $this->_record) || array_key_exists($attribute_name, $this->_attributes);
	}
	public function __set($name,$value){
		$this->_record[$name]=$value;
		$this->_isNewRecord=true;
	}
	public function __get($name){
		if (isset($this->_record[$name])){
			return $this->_record[$name];
		}elseif (isset($this->_attributes[$name])) {
			return null;
		}else throw new \Exception("NO property $name in ".get_called_class());
	}
	
	public function __call($method, $args){
		echo "Call $method with ";var_dump($args);
	}
	
	/**
	 * 
	 * @param array $attributes
	 * @return ActiveRecord
	 */
	public function __invoke(array $attributes){
		$this->_record=array_merge($this->_record,$attributes);
		$this->_isNewRecord=true;
		return $this;
	}
	
	function __construct($data,$isNew=true){
		$this->_record=$data;
		$this->_isNewRecord=$isNew;
	}
	
	static function autoCreate($data){
		$class_name = get_called_class();
		return new $class_name($data,false);
	}
	
	/**
	 * 
	 * 创建对象并保存
	 * @param array $attributes
	 * @param boolean $validate True if the validators should be run
	 * @return self
	 */
	static function create(array $attributes, $validate=true){
		$class_name = get_called_class();
		$model = new $class_name($attributes,true);
		$model->save($validate);
		return $model;
	}
	
	/**
	 * 
	 * 保存对象到数据库
	 * @param boolean $validate True if the validators should be run
	 */
	function save($validate=false){
		if ($this->_isNewRecord){
			$this->_isNewRecord=false;
			return self::insert($this->_record,self::MODE_INSERT_REPLACE);
		}else return true;
	}
	
	/**
	 * 删除对象在数据库的记录。删除后，调用save()方法可以恢复记录。
	 * @see ActiveRecord::save
	 */
	function delete(){
		if ($this->_isNewRecord)
			return true;
		else{
			/**
			 * @todo
			 */
		}
		$this->_isNewRecord=true;
	}
	
	/**
	 * 
	 * 插入一行数据
	 * @param array $record
	 * @param string $mode
	 * @return int lastInsertID
	 */
	static function insert(array $record,$mode=self::MODE_INSERT){
		$cmd=self::command();
		if($cmd->insert($record,$mode)->exec()){
			return $cmd->getPdoInstance()->lastInsertID();
		}
	}
	
	/**
	 * 
	 * 更新一行记录
	 * @param array $record
	 * @return int
	 */
	static function update(array $record,$where=array(),$bindParams=array()){
		$model=get_called_class();
		foreach ($model::$primeKey AS $pKey){
			if (isset($record[$pKey])){
				$where[] = "`$pKey`=:$pKey";
				$bindParams[$pKey]=$record[$pKey];
				unset($record[$pKey]);
			}else{
				throw new \Exception("record should be have key:$pKey");
				return false;
			}
		}
		return self::command()
			->update($record)
			->where($where)
			->bind($bindParams)
			->exec();
	}
	
	/**
	 * 批量插入记录
	 * @param array $data
	 * @param string $mode
	 * @see ActiveRecord::insert
	 */
	static function insertAll(array $data,$mode=self::MODE_INSERT){
		/**
		 * @todo
		 */
	}
	
	/**
	 * @return self
	 * @see ActiveRecord::findAll
	 */
	static function find(array $attributes=array(),array $options=array()){
		return self::command()
			->options($options)
			->limit(1)
			->find($attributes,"*")
			->toObject(get_called_class());
	}
	
	/**
	 * 
	 * @return multitype:self
	 * @see ActiveRecord::find
	 */
	static function findAll(array $attributes,array $options=array()){
		return self::command()
			->options($options)
			->find($attributes,"*")
			->toList(get_called_class());
	}
	
	/**
	 * @return array
	 */
	static function fetchData(array $attributes=array(),array $options=array()){
		return self::command()
			->options($options)
			->find($attributes,"*")
			->toList();
	}
	
	/**
	 * @return DBCommand
	 */
	static function command($alisName=null){
// 		$model=get_called_class();
// 		self::model($model);
		static::getDbConnection();
// 		return new DBCommand(static::$tableName.(is_null($alisName)?"":" AS $alisName"),array("token"=>ConnectionPool::$_tmpCFG['token']));
		return new DBCommand(static::$tableName.(is_null($alisName)?"":" AS $alisName"),array("token"=>static::$db->mtoken));
	}
	
	static function createSQL($sql,array $bindParams=null,$isReadOnly=NULL){
		static::getDbConnection();
// 		return DBCommand::create($sql,$bindParams,$isReadOnly,array("token"=>ConnectionPool::$_tmpCFG['token']));
		return DBCommand::create($sql,$bindParams,$isReadOnly,array("token"=>static::$db->mtoken));
	}
}