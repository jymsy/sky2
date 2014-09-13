<?php
namespace Sky\help;

/**
 * ListIterator 是为{@link SList}实现的迭代器。
 * 它允许SList返回用来遍历list的迭代器。
 * 
 * @author Jiangyumeng
 *
 */
class ListIterator implements \Iterator{
	/**
	 * @var array 用来遍历的数据
	 */
	private $_d;
	/**
	 * @var integer 当前元素的索引
	 */
	private $_i;
	/**
	 * @var integer 数据项总数
	 */
	private $_c;
	
	/**
	 * @param array $data 用来遍历的数据
	 */
	public function __construct(&$data)
	{
		$this->_d=&$data;
		$this->_i=0;
		$this->_c=count($this->_d);
	}
	
	/**
	 * 重置内部数组指针。
	 * Iterator接口需要该方法。
	 */
	public function rewind()
	{
		$this->_i=0;
	}
	
	/**
	 * 返回当前元素的索引
	 * Iterator接口需要该方法。
	 * @return integer 当前元素的索引
	 */
	public function key()
	{
		return $this->_i;
	}
	
	/**
	 * 返回当前数组元素
	 * Iterator接口需要该方法。
	 * @return mixed 当前数组元素。
	 */
	public function current()
	{
		return $this->_d[$this->_i];
	}
	
	/**
	 * 将内部指针移到数组的下一项
	 * Iterator接口需要该方法。
	 */
	public function next()
	{
		$this->_i++;
	}
	
	/**
	 * 返回在当前位置是否有元素。
	 * Iterator接口需要该方法。
	 * @return boolean
	 */
	public function valid()
	{
		return $this->_i<$this->_c;
	}
}