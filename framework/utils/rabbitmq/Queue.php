<?php
namespace Sky\utils\rabbitmq;

/**
 * RabbitMQ Queue wrapper
 * 
 * 
 * @author Jiangyumeng
 *
 */
class Queue{
	/**
	 * @var boolean 是否初始化过
	 */
	protected $_isInitialized = false;
	/**
	 * @var \AMQPQueue amqp实例
	 */
	private $_q;
	/**
	 * @var string 队列名
	 */
	public $name;
	/**
	 * @var \AMQPChannel 频道实例
	 */
	public $channel;
	
	/**
	 * @param \AMQPChannel $channel
	 */
	public function __construct($channel)
	{
		$this->channel=$channel;
	}
	
	/**
	 * 初始化队列
	 * @param string $name
	 * @param integer $flag
	 */
	public function init($name, $flag)
	{
		if ($this->getIsInitialized())
			return;
		$this->_q = new \AMQPQueue($this->channel);
		$this->_q->setName($name);
		$this->_q->setFlags($flag);
		$this->_q->declareQueue();
		$this->name = $name;
		$this->flag = $flag;
		$this->setIsInitialized(true);
	}
	
	/**
	 * 将队列和交换机与路由key绑定
	 * @param string $name 交换机名
	 * @param string $routingKey 路由key
	 * @throws \Exception 
	 */
	public function bind($name, $routingKey = null)
	{
		if(!$this->getIsInitialized())
			throw new \Exception('Queue must be initialized!');
		$this->_q->bind($name, $routingKey);
	}
	
	public function cancel($consumerTag='')
	{
		if(!$this->getIsInitialized())
			throw new \Exception('Queue must be initialized!');
		$this->_q->cancel($consumerTag);
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
	
	/**
	 * 从队列中获取下一条消息。
	 * 如果队列中没有消息，那么该方法回立即返回FALSE。
	 * 目前flag参数只支持AMQP_AUTOACK，
	 * 意味着当消息发给客户端之后代理会自动将它标记为ack。
	 * 
	 * 另外，根据{@link http://backend.blog.163.com/blog/static/202294126201322563245975/}
	 * 所说，绝对不可以通过循环调用get来代替consume，
	 * 这是因为RabbitMQ在get实际执行的时候，是首先consume某一个队列，
	 * 然后检索第一条消息，然后再取消订阅。
	 * 如果是高吞吐率的消费者，最好还是建议使用consume。
	 * 
	 * @param string $flags
	 * @return \AMQPEnvelope|boolean
	 */
	public function get($flags=AMQP_AUTOACK)
	{
		return $this->_q->get($flags);
	}
	
	/**
	 * 消费队列中的消息。
	 * 
	 * 阻塞方法，当队列中有消息的时候会调用callback。
	 * 该方法不会将处理线程返回给PHP脚本，
	 * 除非回调函数返回FALSE。
	 * 
	 * @param callable $callback 用来消费消息的回调函数，
	 * 该回调函数有一个必须的AMQPEnvelope实例参数和一个
	 * 可选的当前队列实例参数
	 * @param string $flag
	 */
	public function consume($callback, $consumerTag='', $flag=AMQP_AUTOACK)
	{
		if (is_callable($callback)) {
			$this->_q->consume($callback, $flag, $consumerTag);
		}
	}
}