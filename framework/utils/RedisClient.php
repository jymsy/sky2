<?php
namespace Sky\utils;

use Sky\base\Component;
/**
 * Redis handler class
 * 
 * Usage:
 * 'components' => array(
 *   'redis' => array(
 *       'class' => 'Sky\utils\RedisClient',
 *       'masterhost' =>'127.0.0.1:6380' ,
 *	      'slavehost' =>'127.0.0.1:6379,127.0.0.1:6381' ,
 *       'timeout'=>10
 *   ),
 *   ...
 * ),
 * 
 * @author Jiangyumeng
 *
 */
class RedisClient extends Component{
	/**
	 * @var \Redis redis主客户端实例
	 */
	protected $_masterclient = null;
	/**
	 * @var \Redis redis从客户端实例
	 */
	protected $_slaveclient = null;
	/**
	 * @var string redis主服务器地址(只有一个)
	 */
	public $masterhost='localhost:6379';
	/**
	 * @var string redis从服务器地址，用","分割多个
	 */
	public $slavehost='localhost:6379';
	/**
	 * @var integer redis服务器端口
	 */
// 	public $port=6379;
	/**
	 * @var integer 要使用的redis数据库默认是0
	 */
	public $database=0;
	/**
	 * @var boolean 是否是长连接
	 */
	public $persistent=false;
	/**
	 * @var int 连接超时时间，默认为0 不限制时长
	 */
	public $timeout=0;
	/**
	 * @var string redis服务器密码
	 */
	public $password='';
	/**
	 * @var array 客户端选项
	 */
	public $options=array();
	/**
	 * @var object 事务对象
	 */
	protected $_transcation=null;
	
	public function init()
	{
// 		if ($this->_client === null) 
// 		{
// 			$this->_client = new \Redis;
// 			if($this->persistent)
// 				$this->_client->pconnect($this->hostname, $this->port,$this->timeout);
// 			else 
// 				$this->_client->connect($this->hostname, $this->port,$this->timeout);
// 			if ($this->password !=='')
// 			{
// 				if ($this->_client->auth($this->password) === false) {
// 					throw new \Exception('Redis authentication failed!');
// 				}
// 			}
// 			$this->setOption($this->options);
// 			if($this->database !== 0)
// 				$this->_client->select($this->database);
// 		}
	}
	
	/**
	 * 初始化redis实例
	 * @param \Redis $client redis实例
	 * @param boolean $isMaster 是否为主
	 * @throws \Exception
	 */
	public function initClient(\Redis $client,$isMaster)
	{
		if ($isMaster) {
			$host=substr($this->masterhost, 0, strpos($this->masterhost, ':'));
			$port=(int)substr($this->masterhost, strpos($this->masterhost, ':')+1);
		}else{
			$slaveArr=explode(',', $this->slavehost);
			$index=getmypid()%count($slaveArr);
			$slavehost=$slaveArr[$index];
			$host=substr($slavehost, 0, strpos($slavehost, ':'));
			$port=(int)substr($slavehost, strpos($slavehost, ':')+1);
		}
		
		if($this->persistent)
			$client->pconnect($host, $port,$this->timeout);
		else
			$client->connect($host, $port,$this->timeout);
		
		if ($this->password !=='')
		{
			if ($client->auth($this->password) === false) {
				throw new \Exception('Redis authentication failed!');
			}
		}
		
		foreach ($this->options as $key=>$value)
		{
			$client->setOption($key, $value);
		}
		if($this->database !== 0)
			$client->select($this->database);
	}
	
	/**
	 * 获取redis实例
	 * @param boolean $isMaster 是否是主，默认为主
	 * @return \Redis
	 */
	public function getClient($isMaster=true)
	{
		if ($isMaster) {
			if ($this->_masterclient===null) {
				$this->_masterclient = new \Redis;
				$this->initClient($this->_masterclient, true);
			}
			return $this->_masterclient;
		}else{
			if ($this->_slaveclient === null) {
				$this->_slaveclient = new \Redis;
				$this->initClient($this->_slaveclient, false);
			}
			return $this->_slaveclient;
		}
	}
	
	/**
	 * 设置redis模式参数
	 * @param $option array 参数数组键值对
	 * @return $return true/false
	 */
// 	public function setOption($option=array())
// 	{
// 		foreach ($option as $key=>$value)
// 		{
// 			$this->_client->setOption($key, $value);
// 		}
// 	}

    /**
     * 写入key-value
     * @param $key string 要存储的key名
     * @param $value mixed 要存储的值
     * @param int $time 过期时间(S)
     * @param int $type 写入方式 0:不添加到现有值后面 1:添加到现有值的后面 默认0
     * @param int $repeat 0:不判断重复 1:判断重复
     * @param int $old 1:返回旧的value 默认0
     * @return bool|int|string
     */
    public function set($key,$value,$time=0,$type=0,$repeat=0,$old=0)
	{
		if ($type) {
			return $this->getClient()->append($key, $value);
		} else {
			if ($old) {
				return $this->getClient()->getSet($key, $value);
			} else {
				if ($repeat) {
					return $this->getClient()->setnx($key, $value);
				} else {
					if ($time && is_numeric($time)) 
						return $this->getClient()->setex($key, $time, $value);
					else 
						return $this->getClient()->set($key, $value);
				}
			}
		}
	}
	
    /**
     * 获取某个key值 如果指定了start end 则返回key值的start跟end之间的字符
     * @param $key string/array 要获取的key或者key数组
     * @param $start int 字符串开始index
     * @param $end int 字符串结束index
     * @return mixed 如果key存在则返回key值 如果不存在返回false
     */
    public function get($key=null,$start=null,$end=null)
	{
		$return = null;
	
		if (is_array($key) && !empty($key)) {
			$return = $this->getClient(false)->getMultiple($key);
		} else {
			if (isset($start) && isset($end)) 
				$return = $this->getClient(false)->getRange($key,$start,$end);
			else 
				$return = $this->getClient(false)->get($key);
		}
	
		return $return;
	}

    /**
     * 将key->value写入hash表中
     * @param $hash string 哈希表名
     * @param $data array 要写入的数据 array('key'=>'value')
     * @return bool
     */
    public function hashSet($hash,$data)
	{
		if (is_array($data) && !empty($data)) {
			return $this->getClient()->hMset($hash, $data);
		}else 
			return false;
	}
	
	/**
	 * 获取hash表的数据
	 * @param $hash string 哈希表名
	 * @param $key mixed 表中要存储的key名 默认为null 返回所有key->value
	 * @param $type int 要获取的数据类型 0:返回所有key 1:返回所有value 2:返回所有key->value
	 * @return mixed
	 */
	public function hashGet($hash,$key=array(),$type=0)
	{
		$return = null;
	
		if ($key) 
		{
			if (is_array($key) && !empty($key))
				$return = $this->getClient(false)->hMGet($hash,$key);
			else
				$return = $this->getClient(false)->hGet($hash,$key);
		} else {
			switch ($type) 
			{
				case 0:
					$return = $this->getClient(false)->hKeys($hash);
					break;
				case 1:
					$return = $this->getClient(false)->hVals($hash);
					break;
				case 2:
					$return = $this->getClient(false)->hGetAll($hash);
					break;
				default:
					$return = false;
					break;
			}
		}
	
		return $return;
	}
	
    /**
     * 入队列
     * @param $list string 队列名
     * @param $value mixed 入队元素值
     * @param int $direction 0:数据入队列头(左) 1:数据入队列尾(右) 默认为0
     * @param int $repeat 判断value是否存在  0:不判断存在 1:判断存在 如果value存在则不入队列
     * @return bool|int|null
     */
    public function listPush($list,$value,$direction=0,$repeat=0)
	{
		$return = null;
	
		switch ($direction) {
			case 0:
				if ($repeat)
					$return = $this->getClient()->lPushx($list,$value);
				else
					$return = $this->getClient()->lPush($list,$value);
				break;
			case 1:
				if ($repeat)
					$return = $this->getClient()->rPushx($list,$value);
				else
					$return = $this->getClient()->rPush($list,$value);
				break;
			default:
				$return = false;
				break;
		}
	
		return $return;
	}
	
    /**
     * 获取list队列的index位置的元素值
     * @param $list string 队列名
     * @param int $index 队列元素开始位置 默认0
     * @param int $end 队列元素结束位置 $index=0,$end=-1:返回队列所有元素
     * @return array|null|void
     */
    public function listGet($list,$index=0,$end=null)
	{
		$return = null;
	
		if ($end) {
			$return = $this->getClient(false)->lrange($list, $index, $end);
		} else {
			$return = $this->getClient(false)->lGet($list, $index);
		}
	
		return $return;
	}
	
	//+++-------------------------集合操作-------------------------+++//
	
	/**
	 * 将value写入set集合 如果value存在 不写入 返回false
	 * 如果是有序集合则根据score值更新该元素的顺序
	 * @param $set string 集合名
	 * @param $value mixed 值
	 * @param $stype int 集合类型 0:无序集合 1:有序集和 默认0
	 * @param $score int 元素排序值
	 * @return 有序集添加成功返回1，否则0;无序集添加的成员个数。
	 */
	public function setAdd($set,$value=null,$stype=0,$score=null)
	{
		$return = null;
	
		if ($stype && $score !== null) {
			$return =  $this->getClient()->zAdd($set, $score, $value);
		} else {
			$return =  $this->getClient()->sAdd($set, $value);
		}
	
		return $return;
	}
	
	/**
	 * ***只针对有序集合操作
	 * 返回有序集 set 中，所有 score 值介于 start 和 end 之间(包括等于 start 或 end )的成员。
	 * @param $set string 集合名
	 * @param $start int|string 最小值
	 * @param $end int|string 最大值
	 * @param $score bool 元素排序值 false:返回数据不带score true:返回数据带score 默认false 
	 * @return array
	 */
	public function setRangeByScore($set,$start,$end,$score=false)
	{
		$return = null;
	
		if ($score) {
			$return = $this->getClient(false)->zRangeByScore($set, $start, $end, array('withscores' => TRUE));
		} else {
			$return = $this->getClient(false)->zRangeByScore($set, $start, $end);
		}
	
		return $return;
	}
	
	/**
	 * ***只针对有序集合操作
	 * 删除set中score从start到end的所有元素
	 * @param $set string 集合名
	 * @param $start  double or "+inf" or "-inf" string 开始score
	 * @param $end  double or "+inf" or "-inf" string 结束score
	 * @return 从集合中删除的元素个数
	 */
	public function setDeleteRange($set,$start,$end)
	{
		return $this->getClient()->zRemRangeByScore($set, $start, $end);
	}
	
	/**
	 * 判断某个key是否存在
	 * @param $key string 要查询的key名
	 * @return boolean 
	 */
	public function exists($key)
	{
		return $this->getClient(false)->exists($key);
	}

    /**
     * 返回指定key的类型
     * @param $key string 要查询的key名
     * @return int
     */
    public function type($key)
    {
        return $this->getClient(false)->type($key);
    }

    /**
     * 查询某个key的生存时间(s)
     * @param $key string 要查询的key名
     * @return int  如果key没有生存时间返回FALSE，如果key不存在返回-2.
     */
    public function ttl($key)
    {
        return $this->getClient(false)->ttl($key);
    }

    /**
     * 开始进入事务操作
     * @param bool $pipe pipeline
     * @return \Redis
     */
    public function tranStart($pipe=true)
	{
		if ($pipe) {
			return $this->_transcation=$this->getClient()->multi(\Redis::PIPELINE);
		}else
			return $this->_transcation=$this->getClient()->multi();
	}
	
    /**
     * 提交完成事务
     * @return bool 事务执行成功 提交操作
     */
    public function tranCommit()
	{
		return $this->_transcation->exec();
	}
	
	/**
	 * 取消事务，放弃执行事务块内的所有命令。
	 * @return $return bool
	 */
	public function tranRollback()
	{
		return $this->_transcation->discard();
	}
	
	/**
	 * 设置某个key的生存时间
	 * @param $key string key的名字
	 * @param $time integer 生存时间（秒）
	 */
	public function setKeyExpire($key, $time)
	{
		return $this->getClient()->setTimeout($key, $time);
	}
	
	/**
	 * 删除某个key值
	 * @param $key array key数组
	 * @return longint 删除成功的key的个数
	 */
	public function delete($key=array())
	{
		return $this->getClient()->delete($key);
	}
	
	public function __destruct()
	{
		if (!$this->persistent) 
		{
			if ($this->_masterclient) 
				$this->_masterclient->close();
			if ($this->_slaveclient)
				$this->_slaveclient->close();
		}
	}
}