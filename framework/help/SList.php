<?php
namespace Sky\help;

/**
 * SList 实现了一个整数索引的集和类。
 *
 * 通过{@link itemAt}, {@link add}, {@link insertAt}, {@link removeAt}.
 * 你可以访问，追加，插入，删除一个元素
 * 
 * 使用{@link getCount}获得list中的元素数量。
 * SList 也可以像数组那样使用。
 * <pre>
 * $list[]=$item;  // 尾部追加
 * $list[$index]=$item; // $index 必须在 0 到 $list->count之间
 * unset($list[$index]); // 删除在 $index处的元素。
 * if(isset($list[$index])) // list是否在$index处有元素。
 * foreach($list as $index=>$item) // 遍历list中的元素。
 * $n=count($list); // 返回list中的元素个数。
 * </pre>
 *
 * @property boolean $readOnly list是否为只读。默认为false。
 * @property Iterator $iterator 用来遍历list元素的迭代器。
 * @property integer $count list中的元素个数。
 * 
 * @author Jiangyumeng
 *
 */
class SList extends \Sky\base\Component implements \IteratorAggregate,\ArrayAccess,\Countable{
	/**
	 * @var integer 元素的个数
	 */
	private $_c=0;
	/**
	 * @var array 存储数据的数组
	 */
	private $_d=array();
	/**
	 * @var boolean list是否只读
	 */
	private $_r=false;
	
	/**
	 * @return boolean list是否为只读。默认为false。
	 */
	public function getReadOnly()
	{
		return $this->_r;
	}
	
	/**
	 * @param boolean $value list是否为只读。默认为false。
	 */
	protected function setReadOnly($value)
	{
		$this->_r=$value;
	}
	
	/**
	 * 返回用来遍历list中的元素的迭代器。
	 * IteratorAggregate接口需要该方法。
	 * @return Iterator 用来遍历list元素的迭代器。
	 */
	public function getIterator()
	{
		return new ListIterator($this->_d);
	}
	
	/**
	 * 在list的尾部添加元素。
	 * @param mixed $item 新的元素
	 * @return integer 元素在list中的索引。
	 */
	public function add($item)
	{
		$this->insertAt($this->_c,$item);
		return $this->_c-1;
	}
	
	/**
	 * 返回在指定位置的元素。
	 * 该方法和{@link offsetGet}完全一样。
	 * @param integer $index 元素的索引。
	 * @return mixed 在该索引处的元素。
	 * @throws \Exception 如果索引超出范围的话。
	 */
	public function itemAt($index)
	{
		if(isset($this->_d[$index]))
			return $this->_d[$index];
		elseif($index>=0 && $index<$this->_c) // value is  null
			return $this->_d[$index];
		else
			throw new \Exception('List index '.$index.' is out of bound.');
	}
	
	/**
	 * 在指定的位置插入元素。
	 * 原来在该位置的元素将会向后移动。
	 * @param integer $index 指定的位置。
	 * @param mixed $item 新元素。
	 * @throws \Exception 如果位置超过范围或list为只读。
	 */
	public function insertAt($index,$item)
	{
		if(!$this->_r){
			if($index===$this->_c)
				$this->_d[$this->_c++]=$item;
			elseif($index>=0 && $index<$this->_c){
				array_splice($this->_d,$index,0,array($item));
				$this->_c++;
			}
			else
				throw new \Exception('List index '.$index.' is out of bound.');
		}else
			throw new \Exception('The list is read only.');
	}
	
	/**
	 * 在指定位置移除一个元素。
	 * @param integer $index 要移除元素的索引。
	 * @return mixed 移除的元素。
	 * @throws \Exception 如果位置超过范围或list为只读。
	 */
	public function removeAt($index){
		if(!$this->_r){
			if($index>=0 && $index<$this->_c){
				$this->_c--;
				if($index===$this->_c)
					return array_pop($this->_d);
				else{
					$item=$this->_d[$index];
					array_splice($this->_d,$index,1);
					return $item;
				}
			}else
				throw new \Exception('List index '.$index.' is out of bound.');
		}
		else
			throw new \Exception('The list is read only.');
	}
	
	/**
	 * 返回list中的元素个数。
	 * Countable 接口需要该方法。
	 * @return integer list中的元素个数。
	 */
	public function count()
	{
		return $this->getCount();
	}
	
	/**
	 * 返回list中的元素个数。
	 * @return integer list中的元素个数。
	 */
	public function getCount()
	{
		return $this->_c;
	}
	
	/**
	 * 返回是否在该位置有元素。
	 * ArrayAccess接口需要该方法。
	 * @param integer $offset 要查看的位置。
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset>=0 && $offset<$this->_c);
	}
	
	/**
	 * 返回指定位置的元素。
	 * ArrayAccess接口需要该方法。
	 * @param integer $offset 索引。
	 * @return mixed 在该位置元素。
	 * @throws \Exception 如果索引非法。
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}
	
	/**
	 * 在指定位置插入元素。
	 * ArrayAccess接口需要该方法。
	 * @param integer $offset 要插入的位置。
	 * @param mixed $item 元素值。
	 */
	public function offsetSet($offset,$item)
	{
		if($offset===null || $offset===$this->_c)
			$this->insertAt($this->_c,$item);
		else
		{
			$this->removeAt($offset);
			$this->insertAt($offset,$item);
		}
	}
	
	/**
	 * 在指定位置移除元素。
	 * ArrayAccess接口需要该方法。
	 * @param integer $offset 元素的位置。
	 */
	public function offsetUnset($offset)
	{
		$this->removeAt($offset);
	}
}