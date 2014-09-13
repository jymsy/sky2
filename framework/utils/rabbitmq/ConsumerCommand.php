<?php
namespace Sky\utils\rabbitmq;
declare(ticks = 1);
use Sky\console\ConsoleCommand;
use Sky\Sky;
/**
 * @author Jiangyumeng
 *
 */
abstract class ConsumerCommand extends ConsoleCommand{
	/**
	 * @var \Sky\utils\rabbitmq\Rabbit
	 */
	private $_rabbit;
	/**
	 * @var integer 从队列中读取的超时时间
	 */
	public $timeout=0;
	/**
	 * @var array() 存放消息的数组
	 */
	private $_msgArray  = array();
	/**
	 * @var integer 收到多少条消息后处理
	 */
	public $maxExecCount = 5;
	/**
	 * @var integer 当前收到的消息个数
	 */
	private $_counter=0;
	/**
	 * @var integer 循环接收消息时，
	 * 第一条消息和最后一条消息的最大时间间隔
	 */
	public $maxExecTime=10;
	/**
	 * @var integer 循环接收消息时的初始时间
	 */
	private $_beginTime;
	/**
	 * @var integer 消息在队列中的时间
	 */
	private $_totalTime=0;
	private $_isFinished=false;
	
	/* 初始化
	 * @see \Sky\console\ConsoleCommand::init()
	 */
	public function init()
	{
		$this->_rabbit=Sky::$app->rabbitmq;
		$this->_rabbit->createConnection();
		$this->_rabbit->setTimeOut($this->timeout, 'read');
		$this->registerSigHandlers();
	}
	
	/**
	 * 注册信号量处理函数
	 */
	protected function registerSigHandlers()
	{
		pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		pcntl_signal(SIGINT, array($this, 'signalHandler'));
	}
	
	/**
	 * 信号量处理函数
	 * 在消息数组为空的时候才会结束。
	 * @param integer $signal 信号量
	 */
	protected function signalHandler($signal)
	{
		echo "getting killed ".getmypid()."\n";
		switch ($signal)
		{
			case SIGTERM:
			case SIGINT:
// 				while (!empty($this->_msgArray)){
// 					echo "sleep\n";
// 					usleep(200000);
// 				}
				$this->_isFinished=true;
// 				exit;
				break;
		}
	}
	
	/**
	 * 初始化交换机
	 * @param string $name 交换机名
	 * @param string $type 交换机类型
	 * @param integer $flag 交换机属性
	 */
	public function initExchange($name, $type=AMQP_EX_TYPE_DIRECT, $flag=AMQP_DURABLE)
	{
		$this->_rabbit->exchange->init($name,$type,$flag);
	}
	
	/**
	 * 初始化队列
	 * @param string $name 队列名
	 * @param integer $flag 队列属性
	 */
	public function initQueue($name, $flag=AMQP_DURABLE)
	{
		$this->_rabbit->queue->init($name, $flag);
	}
	
	/**
	 * 将队列和交换机与路由key绑定
	 * @param string $name 交换机名
	 * @param string $routingKey 路由key
	 * @throws \Exception 
	 */
	public function bindQueue($name, $routingKey)
	{
		$this->_rabbit->queue->bind($name, $routingKey);
	}
	
	public function cancelQueue($consumerTag)
	{
		$this->_rabbit->queue->cancel($consumerTag);
	}
	
	/**
	 * 处理队列中的消息
	 * 该方法会先从队列中取出消息，然后放到{@link $_msgArray}中。
	 * 当满足读取消息超时或消息间隔超时或收到{@link $maxExecCount}
	 * 个消息后，会调用{@link processMsg()}方法。
	 * @return boolean
	 */
	public function consume()
	{
		echo "begin consume message...\n";
		$pid=getmypid();
		while (!$this->_isFinished)
		{
			try{
				$this->_beginTime = time();
				$this->_rabbit->queue->consume(array($this, 'myCallback'), 'update'.$pid);
			}catch(\Exception $e){
				if (!$this->_rabbit->isConnected()) {
					echo "connection timeout\n";
					echo $e->getMessage();
					return false;
				}else{
					echo "it has been too long since previous message.begin process..\n";
				}
			}
			try {
                $this->cancelQueue('update'.$pid);
				$ret=$this->processMsg($this->_msgArray);
                echo "process ".count($this->_msgArray)." msg over.\n";
                if (SKY_DEBUG && $this->_counter>0) {
					$avgTime=sprintf('%01.2f',$this->_totalTime/$this->_counter);
// 					$avgTime=$this->_totalTime/$this->_counter;
					Sky::log("msg:$this->_counter avg queue time:$avgTime ms");
				}
				if( $ret === FALSE){
					$this->_msgArray = array();
					return ;
				}
			}catch (\Exception $e){
				echo $e->getMessage();
				$this->_msgArray = array();
				return false;
			}

			$this->_msgArray = array();
			$this->_counter = 0;
			$this->_totalTime=0;
		}
	}
	
	/**
	 * 判断读取消息是否超时
	 * @param integer $first 初始时间
	 * @return boolean
	 */
	private function timeout($first)
	{
		return time()-$first > $this->maxExecTime;
	}
	
	/**
	 * 将收到的消息，传给子类的处理函数
	 * 子类必须要实现该方法
	 * @param array() $msgArray
	 */
	abstract function processMsg($msgArray);
	
	/**
	 * 收到一条消息后的回调函数
	 * @param \AMQPEnvelope $envelope
	 * @param \AMQPQueue $queue
	 * @return boolean
	 */
	public function myCallback($envelope, $queue)
	{
		$this->_msgArray[] = $envelope->getBody();
		
		if (SKY_DEBUG)
		{
			if ($time=$envelope->getTimeStamp()) {
				$this->_totalTime+=floor(microtime(true)*1000)-$time;
			}
		}
		
		$isTimeout = $this->timeout($this->_beginTime);
		// 		$queue->ack($envelope->getDeliveryTag()); //手动发送ACK应答
		if(++$this->_counter >= $this->maxExecCount || $isTimeout){
			if($isTimeout)
				echo "didn't get enough messages, but the round has timeout.\n";
			else
				echo "we got total $this->maxExecCount messages, begin process..\n";
			return FALSE;  //处理n个消息或超时后退出
		}
	}
}