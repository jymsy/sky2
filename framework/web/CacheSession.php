<?php
namespace Sky\web;

use Sky\Sky;
use Sky\caching\Cache;
/**
 * CacheSession 实现了一种通过Cache存储session的方法。
 * 
 * @author Jiangyumeng
 *
 */
class CacheSession extends Session{
	/**
	 * @var Cache|string cache对象或cache对象组件的id
	 * session数据将会通过这个cache对象来存储。
	 */
	public $cache = 'cache';
	
	/**
	 * 初始化
	 */
	public function init(){
		
		if (is_string($this->cache)) {
			$this->cache = Sky::$app->getComponent($this->cache);
		}
		if (!$this->cache instanceof Cache) {
			throw new \Exception('CacheSession::cache must refer to the application component ID of a cache object.');
		}
		
		parent::init();
	}
	
	/**
	 * 该方法重写了父类，并返回true
	 * @return boolean 是否自定义session存储。
	 */
	public function getUseCustomStorage(){
		return true;
	}

	/**
	 * Session read handler.
	 * 不要直接调用该方法
	 * @param string $id session ID
	 * @return string the session 数据
	 */
	public function readSession($id){
		$data = $this->cache->get($this->calculateKey($id));
		return $data === false ? '' : $data;
	}
	
	/**
	 * Session write handler.
	 * 不要直接调用该方法
	 * @param string $id session ID
	 * @param string $data session 数据
	 * @return boolean 是否成功写入session数据
	 */
	public function writeSession($id, $data){
		return $this->cache->set($this->calculateKey($id), $data, $this->getTimeout());
	}
	
	/**
	 * Session destroy handler.
	 * 不要直接调用该方法
	 * @param string $id session ID
	 * @return boolean session是否成功销毁
	 */
	public function destroySession($id){
		return $this->cache->delete($this->calculateKey($id));
	}
	
	/**
	 * 产生一个唯一的key，用来在cache中存session数据
	 * @param string $id session ID
	 * @return string
	 */
	protected function calculateKey($id){
		return __CLASS__.$id;
	}
	
}