<?php
namespace Sky\web;

use Sky\Sky;
use Sky\db\ConnectionPool;
/**
 * DbSession 扩展了{@link Session},通过数据库存储session数据。
 * 
 * 默认情况下，DbSession将session数据存储在'tbl_session'表中。
 * 该表必须提前创建。表明可以通过{@link sessionTable}设置。
 * 
 * 下面的例子介绍了你该怎样配置应用来使用DbSession：
 * ~~~
 * 'session' => array(
 *     'class' => 'Sky\web\DbSession',
 *     // 'db' => 'mydb',
 *     // 'sessionTable' => 'my_session',
 * )
 * ~~~
 * 
 * @author Jiangyumeng
 *
 */
class DbSession extends Session{
	/**
	 * @var ConnectionPool|string
	 */
	public $db = 'db';
	/**
	 * @var string 用来存储session数据的表明。
	 *
	 * ~~~
	 * CREATE TABLE tbl_session
	 * (
	 *     id CHAR(40) NOT NULL PRIMARY KEY,
	 *     expire INTEGER,
	 *     session_data BLOB
	 * )
	 * ~~~
	 *
	 *当DbSession用在生产环境的时候，建议你为'expire'字段创建索引来提高性能。
	 */
	public $sessionTable = 'tbl_session';
	
	/**
	 * 初始化DbSession组件。
	 * 该方法会初始化db属性，以确保它是一个合法的数据库连接。
	 * @throws \Exception 如果{@link db}非法
	 */
	public function init()
	{
		if (is_string($this->db)) {
			$this->db = Sky::$app->getComponent($this->db);
		}
		if (!$this->db instanceof ConnectionPool) {
			throw new \Exception("DbSession::db must be either a DB connection instance or the application component ID of a DB connection.");
		}
		parent::init();
	}
	
	/**
	 * 是否采用用户自定义的session存储方式。
	 * 该方法重写的父类的方法，总返回true。
	 * @return boolean 是否采用用户自定义的session存储方式。
	 */
	public function getUseCustomStorage()
	{
		return true;
	}
	
	/**
	 * 更新当前的session ID 为新ID。
	 * 详细参见{@link http://php.net/manual/zh/function.session-regenerate-id.php}
	 * @param boolean $deleteOldSession 是否删除旧的session
	 */
	public function regenerateID($deleteOldSession = false)
	{
		$oldID = session_id();
		
		if (empty($oldID)) {
			return;
		}
		
		parent::regenerateID(false);
		$newID = session_id();
		
		$row=$this->db->createCommand(
			sprintf('select * from %s where id=:id',addslashes($this->sessionTable)),
			array('id'=>$oldID)
		)->toList();
		
		if (count($row)) {
			if ($deleteOldSession) {
				$this->db->createCommand(
					sprintf('update %s set id=:newID where id=:oldID',addslashes($this->sessionTable)),
					array(
						'newID'=>$newID,
						'oldID'=>$oldID
					)
				)->exec();
			}else{
				$this->db->createCommand(
					sprintf('insert into %s (id,expire,session_data) values (:id,:expire,:session_data)',addslashes($this->sessionTable)),
					array(
						'id'=>$newID,
						'expire'=>$row[0]['expire'],
						'session_data'=>$row[0]['session_data']
					)
				)->exec();
			}
		}else{
			$this->db->createCommand(
				sprintf('insert into %s (id,expire,session_data) values (:id,:expire,:session_data)',addslashes($this->sessionTable)),
				array(
					'id'=>$newID,
					'expire'=>time() + $this->getTimeout(),
					'session_data'=>''
				)
			)->exec();
		}
	}
	
	/**
	 * Session read handler.
	 * 不要直接调用该方法。
	 * @param string $id session ID
	 * @return string the session 数据
	 */
	public function readSession($id)
	{
		$data=$this->db->createCommand(
				sprintf('select session_data from %s where expire>:expire AND id=:id',addslashes($this->sessionTable)),
				array(
						'expire'=>time(),
						'id'=>$id
				)
		)->toValue();
		return $data===null?'':$data;
	}
	
	/**
	 * Session write handler.
	 * 不要直接调用该方法。
	 * @param string $id session ID
	 * @param string $data session 数据
	 * @return boolean 是否写入成功
	 */
	public function writeSession($id, $data)
	{
		// 异常必须在session write handler内部被捕获。
		// http://www.php.net/manual/zh/function.session-set-save-handler.php
		try {
			$expire = time() + $this->getTimeout();
			$exists=$this->db->createCommand(
					sprintf('select id from %s where id=:id',addslashes($this->sessionTable)),
					array('id'=>$id)
			)->toValue();
			
			if ($exists===null) {
				$this->db->createCommand(
					sprintf('insert into %s (id,expire,session_data) values (:id,:expire,:session_data)',addslashes($this->sessionTable)),
					array(
						'id'=>$id,
						'expire'=>$expire,
						'session_data'=>$data
					)
				)->exec();
			}else{
				echo 
				$this->db->createCommand(
						sprintf('update %s set expire=:expire,session_data=:session_data where id=:id',addslashes($this->sessionTable)),
						array(
								'id'=>$id,
								'expire'=>$expire,
								'session_data'=>$data
						)
				)->exec();
			}
		} catch (\Exception $e) {
			if (SKY_DEBUG) {
				echo $e->getMessage();
			}
			// 记录错误信息已经太晚
			return false;
		}
		return true;
	}
	
	/**
	 * Session destroy handler.
	 * 不要直接调用该方法。
	 * @param string $id session ID
	 * @return boolean 
	 */
	public function destroySession($id)
	{
		$this->db->createCommand(
				sprintf('delete from %s where id=:id',$this->sessionTable),
				array('id'=>$id)
		)->exec();
		return true;
	}
	
	/**
	 * Session GC (垃圾回收) handler.
	 * 不要直接调用该方法。
	 * @param integer $maxLifetime session的有效时间。
	 * @return boolean 
	 */
	public function gcSession($maxLifetime)
	{
		$this->db->createCommand(
			sprintf('delete from %s where expire<:expire',$this->sessionTable),
			array('expire'=>time())
		)->exec();
		return true;
	}
}