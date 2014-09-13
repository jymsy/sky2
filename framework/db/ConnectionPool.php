<?php
namespace Sky\db;
use \PDO;

class ConnectionPool extends \Sky\base\Component{
	
	const MODE_CACHED=0x00000001;
	const MODE_RANDOM=0x00000002;
	public static $mode=0x00000003;//MODE_CACHED|MODE_RANDOM
	private static $_cache=array();
	private static $_dsnMap=array();
	private static $_settings;
	public $mtoken='';
	
	public static $_tmpCFG=array();
	public function __set($name,$value){
		self::$_tmpCFG[$name]=$value;
		if ($name === 'token') {
			$this->mtoken=$value;
		}
	}
	
	
	/**
	 * @return \PDO
	 */
	static function pdo($token){
		if (array_key_exists($token, self::$_cache)){
			return self::$_cache[$token];
		}else{
			$pdo=new PDO(self::_getDsn($token), self::$_settings[$token][0], self::$_settings[$token][1], self::$_settings[$token][2]);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			if (self::$mode&self::MODE_CACHED){
				self::$_cache[$token]=$pdo;
			}
			return $pdo;
		}
	}
	
	private static function _getDsn($token){
		if (self::$mode&self::MODE_RANDOM){
			return self::_randomDsn($token);
		}
	}
	
	private static function _randomDsn($token){
		$dsns=self::$_dsnMap[$token];
		return $dsns[rand(0, count($dsns)-1)];
	}
	
	static function loadDsn($token,array $dsns,$username=null,$password=null,$options=array()){
		self::$_dsnMap[$token]=$dsns;
		self::$_settings[$token]=array($username,$password,$options);
	}
	
	private static function setDsns($servers){
		if (strpos($servers, ',')!==false) {
			$serverArr=explode(',', $servers);
			foreach ($serverArr AS $server){
				$host=substr($server, 0,strpos($server, ':'));
				$port=substr($server, strpos($server, ':')+1);
				$dsns[]="mysql:host=$host;port=$port";
			}
		}else {
			$host=substr($servers, 0,strpos($servers, ':'));
			$port=substr($servers, strpos($servers, ':')+1);
			$dsns[]="mysql:host=$host;port=$port";
		}
		return $dsns;
	}
	
	static function loadConfig(array $cfg){
		switch ($cfg["product"]){
			case "mysql":
				$dsns=self::setDsns($cfg['server_master']);
				self::loadDsn($cfg["token"]."_MASTER", $dsns,$cfg["user"],$cfg["password"],$cfg["option"]);

				$dsns=self::setDsns($cfg["server_slave"]);
				self::loadDsn($cfg["token"]."_SLAVE", $dsns,$cfg["user"],$cfg["password"],$cfg["option"]);
				break;
			case "sqlite":
				
				self::loadDsn($cfg["token"]."_SLAVE", array('sqlite:'.$cfg['path']));
				self::loadDsn($cfg["token"]."_MASTER", array('sqlite:'.$cfg['path']));
				break;
			default:
				throw new \Exception("No Support DB Product:".$cfg["product"]);
		}
	}
	
	public function init(){
// 		var_dump(self::$_tmpCFG);
		self::loadConfig(self::$_tmpCFG);
		self::$_tmpCFG=array();
	}
	
	/**
	 * @param string $sql
	 * @param array $bindParams
	 * @param boolean $isReadOnly
	 * @return \Sky\db\DBCommand
	 */
	public function createCommand($sql,array $bindParams=null,$isReadOnly=NULL){
// 		return DBCommand::create($sql,$bindParams,$isReadOnly,array("token"=>self::$_tmpCFG['token']));
		return DBCommand::create($sql,$bindParams,$isReadOnly,array("token"=>$this->mtoken));
	}
	
}