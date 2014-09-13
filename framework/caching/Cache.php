<?php
namespace Sky\caching;

/**
 * 
 * @author Jiangyumeng
 *
 */
class Cache extends \Sky\base\Component implements \Sky\base\ICache{
	/**
	 * @var string 加在每个缓存键名前的字符串以保证键名是唯一的。默认值是application ID。
	 */
	public $keyPrefix;
	/**
	 * @var array|boolean 
	 * 用来序列化和反序列化cache数据的方法。默认为null，意味着使用PHP的serialize()和unserialize()方法。
	 * 如果你想使用一些更高效的序列化方法（比如igbinary(http://pecl.php.net/package/igbinary)），你可以
	 * 将该属性配置为一个两个元素的数组。第一个元素是序列化方法，第二个元素是反序列化方法。如果该属性
	 * 被设置为false，数据将会被直接放入缓存，不经过序列化。
	 */
	public $serializer;
	
	public $hashKey=true;
	
	public function init(){
		parent::init();
		if($this->keyPrefix===null)
			$this->keyPrefix=\Sky\Sky::$app->getId();
	}
	
	/**
	 * @param string $key 用以甄别缓存值的键名
	 * @return string 根据提供的键名生成的键名，该键名确保在整个应用程序里是唯一的。
	 */
	protected function generateUniqueKey($key){
		return $this->hashKey ? md5($this->keyPrefix.$key) : $this->keyPrefix.$key;
	}
	
	/**
	 * 从缓存中检索一个匹配指定键名的值。
	 * @param string $id 用以甄别缓存值的键名
	 * @return mixed 缓存中存储的值，若该值不在缓存中、已过期时则返回false。
	 */
	public function get($id){
		$value=$this->getValue($this->generateUniqueKey($id));
		if ($value === false || $this->serializer === false) {
			return $value;
		} elseif ($this->serializer === null) {
			$value = unserialize($value);
		} else {
			$value = call_user_func($this->serializer[1], $value);
		}
		return $value;
	}
	
	/**
	 * 从缓存中检索出多个匹配指定键名的值。 
	 * @param array $ids 用以甄别缓存值的键名列表
	 * @return array 以指定键名列表对应的缓存值列表。 
	 * 以（键名，值）对的形式返回数组。 
	 * 若某值不在缓存中或已过期则数组中对应的值为false。
	 */
	public function mget($ids){
		$uids = array();
		foreach ($ids as $id)
			$uids[$id] = $this->generateUniqueKey($id);
		
		$values = $this->getValues($uids);
		$results = array();
		if($this->serializer === false){
			foreach ($uids as $id => $uid)
				$results[$id] = isset($values[$uid]) ? $values[$uid] : false;
		}else{
			foreach($uids as $id => $uid){
				$results[$id] = false;
				if(isset($values[$uid])){
					$results[$id] = $this->serializer === null ? unserialize($values[$uid]) : call_user_func($this->serializer[1], $values[$uid]);
				}
			}
		}
		
		return $results;
	}
	
	/**
	 * 根据一个用以甄别的键名往缓存中存储一个值。 
	 * 若缓存中已经包含该键名，则之前的缓存值 和过期时间会被新的替换掉。
	 *
	 * @param string $id 用以甄别缓存值的键名
	 * @param mixed $value 要缓存的值
	 * @param integer $expire 以秒为单位的数值，表示缓存的过期时间。为0则永不过期。
	 * @return boolean 成功存储到缓存中则返回true，否则返回false
	 */
	public function set($id,$value,$expire=0){
		if ($this->serializer === null) {
			$value = serialize($value);
		} elseif ($this->serializer !== false) {
			$value = call_user_func($this->serializer[0],$value);
		}
		return $this->setValue($this->generateUniqueKey($id), $value, $expire);
	}
	
	/**
	 * 仅仅在缓存值的键名不存在的情况下，往缓存中存储值。
	 * 若缓存中已有该键名则什么也不做。
	 * @param string $id 用以甄别缓存值的键名
	 * @param mixed $value 要缓存的值
	 * @param integer $expire 以秒为单位的数值，表示缓存的过期时间。为0则永不过期。
	 * @return boolean 成功存储到缓存中则返回true，否则返回false
	 */
	public function add($id,$value,$expire=0){
		if ($this->serializer === null) {
			$value = serialize($value);
		} elseif ($this->serializer !== false) {
			$value = call_user_func($this->serializer[0],$value);
		}
		return $this->addValue($this->generateUniqueKey($id), $value, $expire);
	}
	
	/**
	 * 从缓存中删除指定键名对应的值
	 * @param string $id 要删除值的键名
	 * @return boolean 如果删除期间没有发生错误
	 */
	public function delete($id){
		return $this->deleteValue($this->generateUniqueKey($id));
	}
	
	/**
	 * 删除所有缓存值。
	 * 若缓存被多个应用程序共享，务必小心执行此操作。
	 * @return boolean 是否清空操作成功执行。
	 */
	public function flush(){
		return $this->flushValues();
	}
	
	/**
	 * 从缓存中检索一个匹配指定键名的值。 
	 * 该方法必须被子类实现以从指定缓存存储中检索数据。
	 * @param string $key 用以甄别缓存值的唯一键名
	 * @return 缓存中存储的值，如果该值不存在或者已过期则返回false。
	 * @throws \Exception 如果该方法没有被子类重写。
	 */
	protected function getValue($key){
		throw new \Exception(get_class($this).' does not support get() functionality.');
	}
	
	/**
	 * 从缓存中检索出多个匹配指定键名的值。
	 * 默认以多次调用 {@link getValue} 来依次检索被缓存的值的方式实现。
	 * 若底层缓存存储支持多个获取，该方法应该重写以利用其特性。
	 * @param array $keys 用以甄别缓存值的键名列表
	 * @return array 以$keys为键名的缓存值列表
	 */
	protected function getValues($keys){
		$results=array();
		foreach($keys as $key)
			$results[$key]=$this->getValue($key);
		return $results;
	}
	
	/**
	 * 往缓存中存储一个用键名区分的值。 
	 * 该方法必须在子类中实现，以此往指定缓存存储中存储数据。
	 *
	 * @param string $key 用以甄别缓存值的键名
	 * @param string $value 要缓存的值
	 * @param integer $expire 以秒为单位的数值，表示缓存的过期时间。为0则永不过期。
	 * @return boolean 成功存储到缓存中则返回true，否则返回false
	 * @throws Exception 如果该方法没有被子类重写。
	 */
	protected function setValue($key,$value,$expire){
		throw new \Exception(get_class($this).' does not support set() functionality.');
	}
	
	/**
	 * 仅仅在缓存值的键名不存在的情况下，往缓存中存储值。
	 * 该方法必须在子类中实现，以此往指定缓存存储中存储数据。
	 *
	 * @param string $key 用以甄别缓存值的键名
	 * @param string $value 要缓存的值
	 * @param integer $expire 以秒为单位的数值，表示缓存的过期时间。为0则永不过期。
	 * @return boolean 成功存储到缓存中则返回true，否则返回false
	 * @throws Exception 如果该方法没有被子类重写。
	 */
	protected function addValue($key,$value,$expire){
		throw new \Exception(get_class($this).' does not support add() functionality.');
	}
	
	/**
	 * 从缓存中删除指定键名对应的值。
	 * 该方法必须在子类中实现以从实际缓存存储中删除数据。
	 * @param string $key 要删除值的键名
	 * @return boolean 如果删除期间没有发生错误
	 * @throws Exception 如果该方法没有被子类重写。
	 */
	protected function deleteValue($key){
		throw new \Exception(get_class($this).' does not support delete() functionality.');
	}
	
	/**
	 * 删除所有缓存值。
	 * 子类可以实现该方法以实现清空操作。
	 * @return boolean 是否清空操作成功执行。
	 * @throws Exception 如果该方法没有被子类重写。
	 */
	protected function flushValues(){
		throw new \Exception(get_class($this).' does not support flush() functionality.');
	}
}