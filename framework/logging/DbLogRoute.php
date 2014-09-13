<?php
namespace Sky\logging;

use Sky\db\ConnectionPool;
use Sky\Sky;
/**
 * DbLogRoute 将日志存到数据库
 * 
 * 
 * @author Jiangyumeng
 *
 */
class DbLogRoute extends LogRoute{
	/**
	 * @var ConnectionPool|string 数据库连接对象或数据库连接的组件ID。
	 * 当DbLogRoute对象创建之后，如果你想修改这个属性，
	 * 你只能传入ConnectionPool对象。
	 */
	public $db = 'db';
	/**
	 * @var string 用来存放日志内容的数据表名。
	 * 该表应该按照如下的方式事先创建：
	 * 
	 * ~~~
	 * CREATE TABLE tbl_log (
	 *	   id       BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 *	   level    VARCHAR(128),
	 *	   category VARCHAR(128),
	 *	   log_time CHAR(50),
	 *	   message  TEXT,
	 *     INDEX idx_log_level (level),
	 *     INDEX idx_log_category (category)
	 * )
	 * ~~~
	 * 
	 * 上面索引的声明并不是必须的，主要是用来提高查询的效率。
	 */
	public $logTable = 'tbl_log';
	
	/* 初始化
	 * @see \Sky\logging\LogRoute::init()
	 */
	public function init()
	{
		if (is_string($this->db)) {
			$this->db = Sky::$app->getComponent($this->db);
		}
		if (!$this->db instanceof ConnectionPool) {
			throw new \Exception("DbLogRoute::db must be either a DB connection instance or the application component ID of a DB connection.");
		}
	}
	
	/**
	 * 将日志存到数据库
	 * @param array $logs 日志消息列表
	 */
	public function processLogs($logs)
	{
		foreach ($logs as $log)
		{
			$this->db->createCommand(
					sprintf('insert into %s (level,category,log_time,message)
						values (:level, :category, :log_time, :message)',addslashes($this->logTable)),
					array(
							'level'=>$log[1],
							'category'=>$log[2],
							'log_time'=>@date('Y/m/d H:i:s',$log[3]),
							'message'=>$log[0]
					)
			)->exec();
		}
	}
}