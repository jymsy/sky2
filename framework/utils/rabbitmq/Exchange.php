<?php
namespace Sky\utils\rabbitmq;

/**
 * RabbitMQ Exchange wrapper
 * 
 * 交换机的作用：
 * 接受生产者发送的消息，并根据Binding规则将消息路由给服务器中的队列。
 * 交换机类型决定了交换机路由消息的行为。
 * 在RabbitMQ中，交换机类型有direct、Fanout、Topic和Headers四种，
 * 不同类型的Exchange路由的行为是不一样的。
 * 
 * 交换机的具体介绍：{@link http://www.rabbitmq.com/tutorials/amqp-concepts.html}
 * @author Jiangyumeng
 *
 */
class Exchange {
	/**
	 * @var string 交换机的名字
	 */
	public $name;
	/**
	 * @var \AMQPExchange 交换机实例
	 */
	private $_ex;
	/**
	 * @var string 交换机类型
	 *  可以是AMQP_EX_TYPE_DIRECT, 
	 *  AMQP_EX_TYPE_FANOUT, 
	 *  AMQP_EX_TYPE_HEADERS 
	 *  或AMQP_EX_TYPE_TOPIC
	 */
	public $type;
	/**
	 * @var integer AMQP_DURABLE或AMQP_PASSIVE
	 */
	public $flag;
	/**
	 * @var \AMQPChannel 频道实例
	 */
	public $channel;
	/**
	 * @var boolean 是否初始化过
	 */
	protected $_isInitialized = false;
	
	/**
	 * @param \AMQPChannel $channel 频道实例
	 */
	public function __construct($channel)
	{
		$this->channel=$channel;
	}
	
	/**
	 * 初始化
	 * @param string $name 交换机的名字
	 * @param string $type 交换机类型
	 * @param integer $flag 交换机属性
	 */
	public function init($name, $type, $flag)
	{
		if ($this->getIsInitialized())
			return;
		$this->_ex = new \AMQPExchange($this->channel);
		$this->_ex->setName($name);
		$this->_ex->setType($type);
		$this->_ex->setFlags($flag);
		$this->_ex->declareExchange();
		// 		$this->name = $name;
		// 		$this->type = $type;
		// 		$this->flag = $flag;
		$this->setIsInitialized(true);
	}
	
	/**
	 * 将消息发送到交换机
	 * @param string $msg 消息内容
	 * @param string $routeKey 消息的路由key
	 * @param array $options 消息属性，
	 * 默认为delivery_mode =2 ，意味着消息持久化
	 * @param integer $flag 消息类型。 
	 */
	public function send($msg, $routeKey, $options=array('delivery_mode' => 2), $flag =AMQP_NOPARAM)
	{
		$time=floor(microtime(true)*1000);
// 		$options[]=array('timestamp'=>$time);
		$options = array_merge($options, array('timestamp'=>$time));
		$this->_ex->publish($msg, $routeKey, $flag, $options);
	}
	
	/**
	 * @param boolean $isInitialized
	 */
	public function setIsInitialized($isInitialized)
	{
		$this->_isInitialized = $isInitialized;
	}
	
	/**
	 * @return boolean
	 */
	public function getIsInitialized()
	{
		return $this->_isInitialized;
	}
}