<?php
namespace Sky\caching;

use Sky\Sky;
/**
 * MemCache是基于{@link http://memcached.org/ memcached}实现的一个缓存应用组件。
 * 
 * 通过设置{@link setServers servers}属性，可以为MemCache 设置一系列的memcache服务器。
 * 默认情况，MemCache认为只有 一台memcache服务器位于localhost的11211端口。 
 * 
 * 注意，没有安全的措施以保护memcache中的数据。 
 * 运行于系统中的任何进程都可以访问memcache中的所有数据。
 * 
 * 要使用MemCache作为缓存应用组件，如下配置应用程序：
 * <pre>
 * array(
 *     'components'=>array(
 *         'cache'=>array(
 *             'class'=>'\\Sky\\caching\\MemCache',
 *             'servers'=>array(
 *                 array(
 *                     'host'=>'server1',
 *                     'port'=>11211,
 *                     'weight'=>60,
 *                 ),
 *                 array(
 *                     'host'=>'server2',
 *                     'port'=>11211,
 *                     'weight'=>40,
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 * </pre>
 * 在上面，使用了两个memcache服务器：server1和server2。 
 * 你可以设置每个服务器的属性，包括： host, port,weight.
 * 参见{@link http://www.php.net/manual/zh/memcached.addserver.php} 了解更多详情 
 * 
 * @property array $servers
 * @property mixed $memCache
 * @property array $options
 * 
 * @author Jiangyumeng
 *
 */
class MemCache extends Cache{
	/**
	 * @var string 默认情况下，Memcached实例在请求结束后会被销毁。
	 * 但可以在创建时通过persistentID为每个实例指定唯一的ID， 在请求间共享实例。
	 * 所有通过相同的persistent_id值创建的实例共享同一个连接。默认为""
	 */
	public $persistentID='';
	/**
	 * @var \Memcached Memcache实例
	 */
	private $_cache=null;
	/**
	 * @var array memcache服务器配置列表。
	 */
	private $_servers=array();
	/**
	 * @var array memcached配置数组
	 */
	private $_options=array();
	
	/**
	 * 初始化应用程序组件。
	 * 它创建memcache实例并且添加memcache服务器。
	 * @throws Exception 如果memcached扩展没有加载。
	 */
	public function init(){
		parent::init();	
		$cache=$this->getMemCache();
		if (!count($cache->getServerList())) {
			$servers=$this->getServers();
			if(count($servers)){
				foreach($servers as $server){
					$cache->addServer($server->host,$server->port,$server->weight);
				}
			}
			else
				$cache->addServer('localhost',11211);
			
			foreach ($this->_options as $option=>$value)
				$cache->setOption($option,$value);
		}
	}
	
	/**
	 * @return array memcache服务器配置列表。每一个元素都是一个 {@link MemCacheServerConfiguration}.
	 */
	public function getServers(){
		return $this->_servers;
	}
	
	/**
	 * @return array memcache配置数组。
	 */
	public function getOptions(){
		return $this->_options;
	}
	
	/**
	 * @param array $config memcache服务器配置列表。每个元素必须是一个数组，
	 * 包含如下键名： host, port, weight
	 * @see http://www.php.net/manual/zh/memcached.addserver.php
	 */
// 	public function setServers($config){
// 		foreach($config as $c)
// 			$this->_servers[]=new MemCacheServerConfiguration($c);
// 	}
	
	public function setServers($config){
		$configArr=explode(',', $config);
		foreach ($configArr as $c){
			$cArr=explode(':', $c);
			$t['host']=$cArr[0];
			if(isset($cArr[1]))
				$t['port']=$cArr[1];
			if (isset($cArr[2]))
				$t['weight']=$cArr[2];
			$this->_servers[]=new MemCacheServerConfiguration($t);
		}
	}
	
	/**
	 * @throws \Exception 如果扩展为被加载。
	 * @return \Memcached Memcache实例
	 */
	public function getMemCache(){
		if($this->_cache!==null)
			return $this->_cache;
		else{
			$extension='memcached';
			if(!extension_loaded($extension))
				throw new \Exception('MemCache requires PHP '.$extension.' extension to be loaded.');
			if ($this->persistentID!=='')
				return $this->_cache=new \Memcached($this->persistentID);
			else 
				return $this->_cache=new \Memcached;
		}
	}
	
	/**
	 * @param array $options
	 */
	public function setOptions($options){
		$this->_options=$options;
	}
	
	/**
	 * 从缓存中检索一个匹配指定键名的值。 
	 * 这是在父类中定义的方法的具体实现。
	 * @param string $key 用以甄别缓存值的唯一键名
	 * @return string 缓存中存储的值，如果该值不存在或者已过期则返回false。
	 */
	protected function getValue($key){
		Sky::trace('Memcache get:'.$key,'memcache');
		return $this->_cache->get($key);
	}
	
	/**
	 * 从缓存中检索出多个匹配指定键名的值。
	 * @param array $keys 用以甄别缓存值的键名列表
	 * @return array 以$keys为键名的缓存值列表
	 */
	protected function getValues($keys){
		return $this->_cache->getMulti($keys);
	}
	
	/**
	 * 往缓存中存储一个用键名区分的值。 
	 * 这是在父类中定义的方法的具体实现。
	 *
	 * @param string $key 用以甄别缓存值的键名
	 * @param string $value 要缓存的值
	 * @param integer $expire 以秒为单位的数值，表示缓存的过期时间。为0则永不过期。
	 * @return boolean 成功存储到缓存中则返回true，否则返回false
	 */
	protected function setValue($key,$value,$expire){
		if($expire>0)
			$expire+=time();
		else
			$expire=0;
		Sky::trace('Memcache set:'.$key.'=>'.$value."($expire)",'memcache');
		return $this->_cache->set($key,$value,$expire);
	}
	
	/**
	 * 仅仅在缓存值的键名不存在的情况下，往缓存中存值。 
	 * 这是在父类中定义的方法的具体实现。
	 *
	 * @param string $key 用以甄别缓存值的键名
	 * @param string $value 要缓存的值
	 * @param integer $expire 以秒为单位的数值，表示缓存的过期时间。为0则永不过期。
	 * @return boolean 成功存储到缓存中则返回true，否则返回false
	 */
	protected function addValue($key,$value,$expire){
		if($expire>0)
			$expire+=time();
		else
			$expire=0;
		Sky::trace('Memcache add:'.$key.'=>'.$value."($expire)",'memcache');
		return $this->_cache->add($key,$value,$expire);
	}
	
	/**
	 * 从缓存中删除指定键名对应的值 
	 * 这是在父类中定义的方法的具体实现。
	 * @param string $key 要删除值的键名
	 * @return boolean 是否删除期间没有发生错误
	 */
	protected function deleteValue($key){
		Sky::trace('Memcache delete:'.$key,'memcache');
		return $this->_cache->delete($key, 0);
	}
	
	/**
	 * 删除所有缓存值。
	 * 这是在父类中定义的方法的具体实现。
	 * @return boolean 是否flush操作成功。
	 */
	protected function flushValues(){
		return $this->_cache->flush();
	}
}

/**
 * MemCacheServerConfiguration代表单个memcache的服务器的配置数据。 
 *
 * 参见 {@link http://www.php.net/manual/zh/memcached.addserver.php}
 * 了解每个配置属性的详细解释。
 *
 */
class MemCacheServerConfiguration extends \Sky\base\Component{
	/**
	 * @var string memcache服务器主机名或者IP地址
	 */
	public $host;
	/**
	 * @var integer memcache服务器端口
	 */
	public $port=11211;
	/**
	 * @var integer 在所有服务器中使用这台服务器的概率。
	 */
	public $weight=1;
	
	/**
	 * Constructor.
	 * @param array $config memcache服务器配置列表。
	 * @throws Exception 如果配置信息不是数组。
	 */
	public function __construct($config){
		if(is_array($config)){
			foreach($config as $key=>$value)
				$this->$key=$value;
			if($this->host===null)
				throw new \Exception('MemCache server configuration must have "host" value.');
		}
		else
			throw new \Exception('MemCache server configuration must be an array.');
	}
}