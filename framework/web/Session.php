<?php
namespace Sky\web;

use Sky\base\Component;
use Sky\Sky;
use Sky\logging\Logger;
/**
 * Sesssion是一个Web应用组件，提供对session数据的管理和相关的操作。
 * 能够通过`Sky::$app->session`访问。
 * 
 * 要开始会话，调用[[open()]]。调用[[close()]]结束会话并发送session数据。
 * 要销毁session，调用[[destroy()]]。
 *
 * 默认情况下[[autoStart]]是true，这意味着当session组件第一次被访问的时候会话将会自动开始。
 *
 * Session 能够像数组一样使用来获取session数据，例如：
 *
 * ~~~
 * $session = new Session;//Sky::$app->session;
 * $session->open();
 * $value1 = $session['name1'];  // get session variable 'name1'
 * $value2 = $session['name2'];  // get session variable 'name2'
 * foreach ($session as $name => $value) // traverse all session variables
 * $session['name3'] = $value3;  // set session variable 'name3'
 * ~~~
 *
 * Session能够通过继承来支持自定义的session存储。
 * 如果要这么做的话，重写[[useCustomStorage()]]来返回true，
 * 并且还要重写以下方法：[[openSession()]], [[closeSession()]], 
 * [[readSession()]], [[writeSession()]],[[destroySession()]]和[[gcSession()]].
 * 
 * @author Jiangyumeng
 *
 */
class Session extends Component implements \IteratorAggregate, \ArrayAccess, \Countable{
	/**
	 * @var boolean 当创建session组建的时候是否要自动打开session
	 */
	public $autoStart = true;
	
	private $_opened = false;
	
	/**
	 * 初始化
	 */
	public function init(){	
	
		if($this->autoStart)
			$this->open();
		register_shutdown_function(array($this,'close'));
	}
	
	/**
	 * 该方法需要被实现自定义存储session的子类重写，并返回true。
	 * 要实现自定义session存储，重写这些方法: [[openSession()]], [[closeSession()]],
	 * [[readSession()]], [[writeSession()]], [[destroySession()]] and [[gcSession()]].
	 * @return boolean 是否自定义session存储。
	 */
	public function getUseCustomStorage(){
		return false;
	}
	
	/**
	 * 开始会话
	 */
	public function open(){
		// this is available in PHP 5.4.0+
// 		if (function_exists('session_status')) {
// 			if (session_status() == PHP_SESSION_ACTIVE) {
// 				$this->_opened = true;
// 				return;
// 			}
// 		}
		if (!$this->_opened) {
			if($this->getUseCustomStorage()){
				@session_set_save_handler(
						array($this,'openSession'),
						array($this,'closeSession'),
						array($this,'readSession'),
						array($this,'writeSession'),
						array($this,'destroySession'),
						array($this,'gcSession'));
			}
	
			@session_start();
			if(session_id()==''){
				$this->_opened = false;
				$error=error_get_last();
				$message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
				Sky::log($message, Logger::LEVEL_WARNING, __METHOD__);
			}else {
				$this->_opened=true;
			}
		}
	}
	
	/**
	 * 结束当前会话并且保存session数据。
	 */
	public function close(){	
		$this->_opened = false;
		if(session_id()!=='') {
			@session_write_close();
		}
	}
	
	/**
	 * 释放session变量，销毁session数据。
	 */
	public function destroy(){
		if(session_id()!==''){
			@session_unset();
			@session_destroy();
		}
	}
	
	/**
	 * @return boolean 是否已经开始会话。
	 */
	public function getIsActive(){
		if (function_exists('session_status')) {
			// available in PHP 5.4.0+
			return session_status() == PHP_SESSION_ACTIVE;
		} else {
			// this is not very reliable
			return $this->_opened && session_id() !== '';
		}
	}
	
	/**
	 * @return string 当前的session ID。
	 */
	public function getId(){
		return session_id();
	}
	
	/**
	 * @param string $value 当前会话的sessionID
	 */
	public function setId($value){
		session_id($value);
	}
	
	/**
	 * 用新生成的sessionID更新现有的sessionID。
	 * @param boolean $deleteOldSession 是否删除老的session。
	 */
	public function regenerateID($deleteOldSession = false){
		session_regenerate_id($deleteOldSession);
	}
	
	/**
	 * @return string 当前的session名。
	 */
	public function getName(){
		return session_name();
	}
	
	/**
	 * @param string $value 当前会话的session名，必须为字母数字的字符串。
	 * 默认为"PHPSESSID".
	 */
	public function setName($value){
		session_name($value);
	}
	
	/**
	 * @return float GC过程启动的概率，默认为1%
	 */
	public function getGCProbability(){
		return (float)(ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
	}
	
	/**
	 * @param float $value GC过程启动的概率
	 * @throws \Exception 如果概率值不在[0,100]以内。
	 */
	public function setGCProbability($value){
		if($value>=0 && $value<=100){
			// percent * 21474837 / 2147483647 ≈ percent * 0.01
			ini_set('session.gc_probability',floor($value*21474836.47));
			ini_set('session.gc_divisor',2147483647);
		}else
			throw new \Exception('GCProbability must be a value between 0 and 100.');
	}
	
	/**
	 * @return integer session的生存时间（秒数），默认为 1440秒
	 */
	public function getTimeout(){
		return (int)ini_get('session.gc_maxlifetime');
	}
	
	/**
	 * @param integer $value session的生存时间（秒数）
	 */
	public function setTimeout($value){
		ini_set('session.gc_maxlifetime',$value);
	}
	
	/**
	 * 根据session变量名返回session变量值。
	 * 如果session变量不存在，将返回$defaultValue
	 * @param string $key session变量名。
	 * @param mixed $defaultValue 当session变量不存在的时候返回的默认值。
	 * @return mixed session变量值或默认值。
	 */
	public function get($key, $defaultValue = null)
	{
		return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
	}
	
	/**
	 * 添加一个session变量。
	 * 如果该变量已经存在，那么之前的值会被覆盖。
	 * @param string $key session变量名
	 * @param mixed $value session变量值
	 */
	public function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}
	
	/**
	 * 移除一个session变量。
	 * @param string $key 要删除的session变量名。
	 * @return mixed 删除的变量值。如果变量不存在返回null
	 */
	public function remove($key)
	{
		if (isset($_SESSION[$key])) {
			$value = $_SESSION[$key];
			unset($_SESSION[$key]);
			return $value;
		} else {
			return null;
		}
	}
	
	/**
	 * Session open handler.
	 * 如果[[useCustomStorage()]] 返回 true的话，该方法要被重写.
	 * 不要直接调用该方法
	 * @param string $savePath session 保存路径
	 * @param string $sessionName session名
	 * @return boolean session是否被成功打开
	 */
	public function openSession($savePath, $sessionName){
		return true;
	}
	
	/**
	 * Session close handler.
	 * 如果[[useCustomStorage()]] 返回 true的话，该方法要被重写.
	 * 不要直接调用该方法
	 * @return boolean session是否成功关闭
	 */
	public function closeSession(){
		return true;
	}
	
	/**
	 * Session read handler.
	 * 如果[[useCustomStorage()]] 返回 true的话，该方法要被重写.
	 * 不要直接调用该方法
	 * @param string $id session ID
	 * @return string the session 数据
	 */
	public function readSession($id){
		return '';
	}
	
	/**
	 * Session write handler.
	 * 如果[[useCustomStorage()]] 返回 true的话，该方法要被重写.
	 * 不要直接调用该方法
	 * @param string $id session ID
	 * @param string $data session 数据
	 * @return boolean 是否成功写入session数据
	 */
	public function writeSession($id, $data){
		return true;
	}
	
	/**
	 * Session destroy handler.
	 * 如果[[useCustomStorage()]] 返回 true的话，该方法要被重写.
	 * 不要直接调用该方法
	 * @param string $id session ID
	 * @return boolean session是否成功销毁
	 */
	public function destroySession($id){
		return true;
	}
	
	/**
	 * Session GC (garbage collection) handler.
	 * 如果[[useCustomStorage()]] 返回 true的话，该方法要被重写.
	 * 不要直接调用该方法
	 * @param integer $maxLifetime session的生存时间。
	 * @return boolean session是否被成功gc
	 */
	public function gcSession($maxLifetime){
		return true;
	}
	
	//------ The following methods enable Session to be Map-like -----
	
	/**
	 * ArrayAccess接口需要该方法.
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset){
		return isset($_SESSION[$offset]);
	}

	/**
	 * ArrayAccess接口需要该方法.
	 * @param integer $offset
	 * @return mixed 在开下标处的元素。 如果没有的话返回null
	 */
	public function offsetGet($offset){
		return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
	}

	/**
	 * ArrayAccess接口需要该方法.
	 * @param integer $offset 
	 * @param mixed $item
	 */
	public function offsetSet($offset,$item){
		$_SESSION[$offset]=$item;
	}

	/**
	 * ArrayAccess接口需要该方法.
	 * @param mixed $offset
	 */
	public function offsetUnset($offset){
		unset($_SESSION[$offset]);
	}

	/**
	 * 返回用来遍历session变量的迭代器
	 * IteratorAggregate接口需要该方法。.
	 * @return SessionIterator
	 */
	public function getIterator(){
		return new SessionIterator;
	}

	/**
	 * 返回session变量中的元素个数
	 * @return integer
	 */
	public function getCount(){
		return count($_SESSION);
	}

	/**
	 * 返回session变量中的元素个数
	 * Countable接口需要该方法.
	 * @return integer
	 */
	public function count(){
		return $this->getCount();
	}

	
}