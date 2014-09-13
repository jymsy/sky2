<?php
namespace Sky\web\widgets;

use Sky\base\BaseController;
use Sky\Sky;
use Sky\base\Controller;
/**
 * @property string $id 小部件的id
 * 
 * @author Jiangyumeng
 *
 */
class Widget extends BaseController{
	/**
	 * @var BaseController 当前小部件的owner/creator。既可以小部件或controller。
	 */
	private $_owner;
	/**
	 * @var string 小部件的id
	 */
	private $_id;
	/**
	 * @var integer id计数器。
	 */
	private static $_counter=0;
	
	/**
	 * Constructor.
	 * @param BaseController $owner
	 */
	public function __construct($owner=null){
		$this->_owner=$owner===null?Sky::$app->getController():$owner;
	}
	
	/**
	 * 初始化小部件。
	 * 该方法被{@link BaseController::createWidget}调用。
	 * 当小部件的属性被初始化后被{@link BaseController::beginWidget}调用。
	 */
	public function init(){
	}
	
	/**
	 * 执行小部件。
	 * 该方法被{@link BaseController::endWidget}调用.
	 */
	public function run(){
	}
	
	/**
	 * 返回小部件的ID或生成一个新的如果没有的话。
	 * @param boolean $autoGenerate 如果之前没有设置的话是否生成一个ID
	 * @return string 小部件的ID
	 */
	public function getId($autoGenerate=true){
		if($this->_id!==null)
			return $this->_id;
		elseif($autoGenerate)
			return $this->_id='yw'.self::$_counter++;
	}
	
	/**
	 * @return \Sky\base\BaseController
	 */
	public function getOwner()
	{
		return $this->_owner;
	}
	
	/**
	 * 设置小部件的ID
	 * @param string $value 小部件的ID
	 */
	public function setId($value){
		$this->_id=$value;
	}
	
	/**
	 * 返回小部件属于的controller
	 * @return Controller
	 */
	public function getController(){
		if($this->_owner instanceof Controller)
			return $this->_owner;
		else
			return Sky::$app->getController();
	}
	
	public function getViewFile($viewName)
	{
		echo $viewName;
	}
}