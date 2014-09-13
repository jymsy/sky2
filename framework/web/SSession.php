<?php
namespace Sky\web;

use Sky\base\Component;
use Sky\Sky;
use Sky\db\ConnectionPool;
use Sky\logging\Logger;
/**
 * 
 * 没有依赖PHP的session机制
 * Usage:
 * 	~~~
 * $session = Sky::$app->session;
 * $session->open();
 * $value1 = $session['name1'];  // get session variable 'name1'
 * $value2 = $session['name2'];  // get session variable 'name2'
 * $session['name3'] = $value3;  // set session variable 'name3'
 * ~~~
 * @author Jiangyumeng
 *
 */
class SSession extends Component implements \ArrayAccess, \Countable{
	/**
	 * @var boolean 当创建session组建的时候是否要自动打开session
	 */
	public $autoStart = true;
	private  $_sessionArr=array();
	private  $_sessionId='';
	/**
	 * @var boolean 当前的session数据是否经过修改。
	 */
	protected $_valueChanged=false;
	/**
	 * @var int 超时时间
	 */
	public $lifeTime=172800;//2 days
	/**
	 * @var boolean 是否已经开始会话
	 */
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
	 * 开始会话
	 */
	public function open(){
		if (!$this->_opened) {
			$this->sessionStart();
			if($this->_sessionId==''){
				$this->_opened = false;
				Sky::log('Failed to start session.', Logger::LEVEL_WARNING, __METHOD__);
			}else {
				$this->_opened=true;
			}
		}
	}
	
	public function sessionStart(){
		$this->sessionGC();
		if (($sid=$this->getClientSessionId())!==null) {
			$this->_sessionId=$sid;
			$this->_sessionArr=$this->sessionRead($this->_sessionId);
		}else{
			$this->_sessionId=$this->generateID();
		}	
	}
	
	/**
	 * 获取客户端提交的session id
	 * @return string 客户端提交的sessionid
	 */
	public function getClientSessionId(){
		throw new \Exception(get_class($this).' does not support get() functionality.');
	}
	
	/**
	 * 生成session id
	 * @return string
	 */
	protected function generateID(){
		throw new \Exception(get_class($this).' does not support generate() functionality.');
	}
	
	/**
	 * 用新生成的sessionID更新现有的sessionID。
	 * @param boolean $deleteOldSession 是否删除老的session。
	 * @return boolean 成功返回true，失败返回false。
	 */
	public function regenerateID($deleteOldSession = false)
	{
		if ($this->_opened) {
			if ($deleteOldSession===false) {
				$this->_sessionId=$this->generateID();
			}else{
				$tempArr=$this->_sessionArr;
				$this->destroy();
				$this->_sessionId=$this->generateID();
				$this->_sessionArr=$tempArr;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @return string 当前的session ID。
	 */
	public function getId(){
		return $this->_sessionId;
	}
	
	/**
	 * @param string $value 当前会话的sessionID
	 * 在open之前设置才有效
	 */
	public function setId($value){
		if ($this->_opened===false) {
			$this->_sessionId=$value;
		}
	}
	
	/**
	 * 结束当前会话并且保存session数据。
	 */
	public function close(){
		if ($this->_opened) {
			$this->_opened=false;
			if ($this->_sessionId!=='') {
				$this->sessionWrite($this->_sessionId, $this->_sessionArr);
			}
		}
	}
	
	protected function badSession(){
		throw new \Exception(get_class($this).' does not support read() functionality.');
	}
	
	/**
	 * 验证session合法性
	 * @return boolean
	 */
	public function illegalSession(){
		return $this->badSession();
	}
	
	protected function emtpySessionArray(){
		$this->_sessionArr=array();
	}
	
	/**
	 * 从数据库中读取指定sessionid 的session内容
	 * @param string $id Session id
	 * @return array 
	 */
	protected function sessionRead($id){
		throw new \Exception(get_class($this).' does not support read() functionality.');
	}
	
	/**
	 * 将session数据写入数据库
	 * @param string $id session id
	 * @param mixed $value
	 */
	protected function sessionWrite($id,$value){
		throw new \Exception(get_class($this).' does not support write() functionality.');
	}
	
	/**
	 * 从数据库中删除指定session id的会话
	 * @param string $id
	 */
	protected function sessionDestroy($id){
		throw new \Exception(get_class($this).' does not support destroy() functionality.');
	}
	
	/**
	 * session回收
	 * @throws \Exception
	 */
	protected function sessionGC(){
		throw new \Exception(get_class($this).' does not support destroy() functionality.');
	}
	
	/**
	 * 销毁当前会话数据
	 */
	public function destroy(){
		if (!empty($this->_sessionId)) {
			$this->sessionDestroy($this->_sessionId);
		}
	}
	
	//------ The following methods enable Session to be Map-like -----
	
	/* 
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		return isset($this->_sessionArr[$offset]);
	}

	/*
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		return isset($this->_sessionArr[$offset]) ? $this->_sessionArr[$offset] : null;
	}

	/* 
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->_sessionArr[$offset]=$value;
		$this->_valueChanged=true;
	}

	/* 
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		unset($this->_sessionArr[$offset]);
		$this->_valueChanged=true;
	}

	/*
	 * @see Countable::count()
	 */
	public function count() {
		return count($this->_sessionArr);
	}

	
}