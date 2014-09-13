<?php
namespace Sky\logging;

use Sky\base\Event;
/**
 * Logger在内存中记录日志信息。
 * 
 * Logger能够根据日志的级别和分类来过滤日志信息
 * 日志信息的格式为:
 * array(
 *   [0] => message (string)
 *   [1] => level (string)
 *   [2] => category (string)
 *   [3] => timestamp (float, obtained by microtime(true))
 *   
 * @author Jiangyumeng
 */
class Logger extends \Sky\base\Component{
	const LEVEL_TRACE='trace';
	const LEVEL_WARNING='warning';
	const LEVEL_ERROR='error';
	const LEVEL_INFO='info';
	const LEVEL_PROFILE='profile';
	const LEVET_BI='bi';
	/**
	 * @var integer 记录了多少条日志之后就flush到处理函数。
	 * 默认10000，意味着每记录10000条日志就会自动调用一次 {@link flush} 方法。
	 * .如果为0的话，意味着不会自动flush。
	 */
	public $autoFlush=10000;
	/**
	 * @var boolean 该属性默认为false，
	 * 意味着在调用 {@link flush()} 之后内存中继续保留过滤后的消息。
	 * If this is true, the filtered messages如果为true的话，
	 * 过滤后的消息会直接写道处理函数。
	 */
	public $autoDump=false;
	/**
	 * @var array 日志信息
	 */
	private $_logs=array();
	/**
	 * @var integer 日志信息的数量
	 */
	private $_logCount=0;
	/**
	 * @var array 日志信息级别
	 */
	private $_levels;
	/**
	 * @var array 日志分类信息
	 */
	private $_categories;
	/**
	 * @var boolean 是否正在处理日志
	 */
	private $_processing=false;
	
	/**
	 * 记录一条消息
	 * 通过这个方法记录的消息可以通过{@link getLogs}取回。
	 * @param string $message 要被记录的消息
	 * @param string $level 消息的级别 (例如 'Trace', 'Warning', 'Error'). 大小写敏感
	 * @param string $category 消息的分类 (例如 'system.web').大小写敏感
	 * @see getLogs
	 */
	public function log($message,$level='info',$category='application'){
		$this->_logs[]=array($message,$level,$category,microtime(true));
		$this->_logCount++;

		if($this->autoFlush>0 && $this->_logCount>=$this->autoFlush && !$this->_processing)
		{
			$this->_processing=true;
			$this->flush($this->autoDump);
			$this->_processing=false;
		}
	}
	
	/**
	 * 取回日志信息。
	 * 消息可能会被日志级别and/or类别过滤。
	 * 一个级别过滤器是通过用逗号或空格分隔的等级列表指定的(例如'trace, error')。
	 * 类别过滤器类似于等级过滤器 (例如'system, system.web')。
	 *不同的地方在于在分类过滤器中你能够使用像system.*的方式来获取所有以system.*开头的分类
	 *
	 * 如果你没有指定等级过滤器，它将取回所有等级的日志。这同样适用于类别过滤器。 
	 *
	 * 等级过滤器和类别过滤器是可以组合的。 例如，仅仅同时满足两个条件的信息才会返回。
	 *
	 * @param string $levels 等级过滤
	 * @param string $categories 类别过滤
	 * @return array 信息列表 
	 * 每一个数组元素代表一个下面结构的信息
	 * array(
	 *   [0] => message (string)
	 *   [1] => level (string)
	 *   [2] => category (string)
	 *   [3] => timestamp (float, 通过 microtime(true)获得);
	 *   
	 */
	public function getLogs($levels='',$categories=array()){
		$this->_levels=preg_split('/[\s,]+/',strtolower($levels),-1,PREG_SPLIT_NO_EMPTY);
		
		if (is_string($categories))
			$this->_categories=preg_split('/[\s,]+/',strtolower($categories),-1,PREG_SPLIT_NO_EMPTY);
		else
			$this->_categories=array_filter(array_map('strtolower',$categories));
		
		$ret=$this->_logs;
	
		if(!empty($levels))
			$ret=array_values(array_filter($ret,array($this,'filterByLevel')));
		
		if(!empty($this->_categories))
			$ret=array_values(array_filter($ret,array($this,'filterByCategory')));
		return $ret;
	}
	
	/**
	 * 过滤等级方法
	 * @param array $value 要被过滤的元素
	 * @return boolean true 如果是合法的日志, false如果不是.
	 */
	private function filterByLevel($value){
		return in_array(strtolower($value[1]),$this->_levels);
	}
	
	/**
	 * 过滤分类方法
	 * @param array $value 要被过滤的元素
	 * @return boolean true如果是合法的日志, false如果不是.
	 */
	private function filterByCategory($value){
		return $this->filterAllCategories($value, 2);
	}
	
	/**
	 * @param array $value 要被过滤的元素
	 * @param integer 要被检查的values数组的索引
	 * @return boolean 
	 */
	private function filterAllCategories($value, $index){
		$cat=strtolower($value[$index]);
		$ret=empty($this->_categories);
		foreach($this->_categories as $category){
			if($cat===$category || (($c=rtrim($category,'.*'))!==$category && strpos($cat,$c)===0))
				$ret=true;
		}
		return $ret;
	}
	
	/**
	 * 返回服务当前请求的总时间。 
	 * 这个方法计算现在和常量SKY_BEGIN_TIME定义的时间戳之间的不同
	 * 为了估算执行时间更加准确,此常量应该尽可能早的定义(最好在进入脚本时开始。)
	 * @return float 服务当前请求的总时间。
	 */
	public function getExecutionTime(){
		return microtime(true)-SKY_BEGIN_TIME;
	}
	
	/**
	 * 返回当前应用程序的内存使用量。 
	 * 这个方法依靠PHP的函数memory_get_usage()。 
	 * 如果它不可用,该方法将尝试使用操作系统程序去确定内存的使用 
	 * 如果内存使用量仍不能确定将返回0。
	 * @return integer 应用程序的内存使用量(字节)。
	 */
	public function getMemoryUsage(){
		if(function_exists('memory_get_usage'))
			return memory_get_usage();
		else{
			$output=array();
			if(strncmp(PHP_OS,'WIN',3)===0){
				exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST',$output);
				return isset($output[5])?preg_replace('/[\D]/','',$output[5])*1024 : 0;
			}
			else{
				$pid=getmypid();
				exec("ps -eo%mem,rss,pid | grep $pid", $output);
				$output=explode('  ',$output[0]);
				return isset($output[1]) ? $output[1]*1024 : 0;
			}
		}
	}
	
	/**
	 * 从内存中移除所有记录的日志。
	 * 该方法会抛出{@link onFlush}事件。
	 * @param boolean $dumpLogs 当日志被传到日志路由的时候是否要立即处理。
	 */
	public function flush($dumpLogs=false)
	{
		$this->onFlush(new Event($this, array('dumpLogs'=>$dumpLogs)));
		$this->_logs=array();
		$this->_logCount=0;
	}
	
	/**
	 * 抛出 <code>onFlush</code> 事件.
	 * @param Event $event 
	 */
	public function onFlush($event)
	{
		$this->raiseEvent('onFlush', $event);
	}
}