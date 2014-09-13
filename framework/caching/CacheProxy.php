<?php
namespace Sky\caching;

use Sky\Sky;
/**
 * cache代理类
 * 用来简化编写缓存的逻辑判断代码。
 * Usage:
 * 	$ret=CacheProxy::newInstance(SkyCategory::model(),3600)->getCategory();
 *	var_dump($ret);
 *	
 * @author Jiangyumeng
 *
 */
class CacheProxy{
	/**
	 * @var object 要调用缓存的类对象
	 */
	private $_obj;
	/**
	 * @var int 缓存的过期时间(秒)
	 */
	private $_expire=0;
	/**
	 * @var object cache类对象
	 */
	private static $_cache;
	
	/**
	 * 构造函数
	 * @param object $object 要调用缓存的类对象
	 * @param int $expire 缓存的过期时间(秒)
	 */
	private function __construct($object,$expire=0){
		$this->_obj=$object;
		$this->_expire=$expire;
	}

	/**
	 * 创建缓存代理类实例
	 * @param object $object 要调用缓存的类对象
	 * @param int $expire 缓存的过期时间(秒)
	 * @return \demos\components\CacheProxy
	 */
	public static function newInstance($object,$expire=0){
		return new self($object,$expire);
	}
	
	/**
	 * PHP魔术方法，不要直接调用
	 * 如果你试着调用一个对象中不存在的方法，__call 方法将会被自动调用。 
	 * @param string $name 方法名
	 * @param array $arguments 参数数组
	 * @return mixed 如果缓存中有key对应的值，则返回该值；
	 * 否则调用原始方法并将结果缓存在返回。
	 */
	function __call($name,$arguments){
		$class=get_class($this->_obj);
		$key=$class.$name.serialize($arguments);

		if (self::getCache() && ($ret=self::getCache()->get($key))!==false) {
			return $ret;
		}else{
			$ret=call_user_func_array(array($class,$name), $arguments);
			if (self::getCache()) {
				Sky::$app->cache->set($key, $ret,$this->_expire);
			}
			return $ret;
		}
	}
	
	 /**
	  * 获取cache对象
	  * @return \Sky\caching\Cache
	  */
	 private static function getCache(){
	 	if (self::$_cache==null) {
	 		self::$_cache=Sky::$app->cache;
	 	}
	 	return self::$_cache;
	 }
}