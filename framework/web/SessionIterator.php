<?php
namespace Sky\web;

/**
 * SessionIterator 实现了通过[[Session]]遍历session变量的方法
 * @author Jiangyumeng
 *
 */
class SessionIterator implements \Iterator{
	/**
	 * @var array list of keys in the map
	 */
	private $_keys;
	/**
	 * @var mixed 当前 key
	 */
	private $_key;
	
	/**
	 * Constructor.
	 */
	public function __construct(){
		$this->_keys = array_keys($_SESSION);
	}
	
	/**
	 * 返回当前的数组元素。
	 * Iterator接口需要该方法
	 * @return mixed 当前的数组元素。
	 */
	public function current(){
		return isset($_SESSION[$this->_key]) ? $_SESSION[$this->_key] : null;
	}

	/**
	 * 将内部指针指向下一个数组元素
	 * Iterator接口需要该方法
	 */
	public function next(){
		do {
			$this->_key = next($this->_keys);
		} while (!isset($_SESSION[$this->_key]) && $this->_key !== false);
	}

	/**
	 * 返回当前数组元素的key
	 * Iterator接口需要该方法
	 * @return mixed
	 */
	public function key(){
		return $this->_key;
	}

	/**
	 * 判断数组在当前的位置是否有元素
	 * Iterator接口需要该方法
	 * @return boolean
	 */
	public function valid(){
		return $this->_key !== false;
	}

	/**
	 * 重置当前数组指针
	 * Iterator接口需要该方法
	 */
	public function rewind(){
		$this->_key = reset($this->_keys);
	}

	
}