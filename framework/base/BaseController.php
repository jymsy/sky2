<?php
namespace Sky\base;

use Sky\Sky;
/**
 * BaseController 是{@link Controller}的基类
 * @author Jiangyumeng
 */
abstract class BaseController extends Component{
	private $_widgetStack=array();
	
	/**
	 * 渲染一个视图
	 * @param string $viewFile 视图文件路径
	 * @param mixed $data 视图要获取的数据
	 * @param boolean $return 渲染的结果是否要返回而不是echo出来
	 * @return string 渲染的结果。 如果渲染结果不需要的话返回Null
	 */
	public function renderFile($viewFile,$data=null,$return=false){

		$content=$this->renderInternal($viewFile,$data,$return);
		return $content;
	}
	
	/**
	 * @param string $viewName
	 */
	abstract public function getViewFile($viewName);
	
	/**
	 * 渲染一个视图
	 * 该方法把视图文件当作php脚本包含进来并且捕获显示结果如果需要的话。
	 * 
	 * @param string $_viewFile_ 视图文件
	 * @param array $_data_ 要被视图文件获取的数据
	 * @param boolean $_return_ 是否渲染的结果要被当成字符串返回。
	 * @return string 渲染的结果。 如果渲染结果不需要的话返回Null
	 */
	public function renderInternal($_viewFile_,$_data_=null,$_return_=false){
		// we use special variable names here to avoid conflict when extracting data
		if(is_array($_data_))
			extract($_data_,EXTR_PREFIX_SAME,'data');
		else
			$data=$_data_;
		if($_return_){
			ob_start();
			ob_implicit_flush(false);
			require($_viewFile_);
			return ob_get_clean();
		}
		else
			require($_viewFile_);
	}
	
	/**
	 * 创建一个小部件并初始化。
	 * 该方法首先创建指定小部件的实例。
	 * 然后根据给定的初始值配置小部件的属性。
	 * 最后调用{@link \Sky\web\widgets\Widget::init}初始化小部件。
	 * @param string $className 类名。
	 * @param array $properties 初始值
	 * @return  \Sky\web\widgets\Widget 小部件实例。
	 */
	public function createWidget($className,$properties=array()){
		$widget=Sky::$app->getWidgetFactory()->createWidget($this,$className,$properties);
		$widget->init();
		return $widget;
	}
	
	/**
	 * 创建并执行一个小部件。
	 * @param string $className 小部件类名。
	 * @param array $properties 小部件的初始属性列表。 (属性名 =>属性值)
	 * @param boolean $captureOutput 是否捕获小部件的输出。true的话捕获，并返回。
	 * false的话直接显示，返回小部件对象。
	 * @return mixed 当$captureOutput 为false的时候返回小部件实例, 当$captureOutput 为 true的时候返回小部件输出.
	 */
	public function widget($className,$properties=array(),$captureOutput=false){
		if($captureOutput){
			ob_start();
			ob_implicit_flush(false);
			$widget=$this->createWidget($className,$properties);
			$widget->run();
			return ob_get_clean();
		}else{
			$widget=$this->createWidget($className,$properties);
			$widget->run();
			return $widget;
		}
	}
	
	/**
	 * 创建并执行一个小部件。
	 * 该方法跟 {@link widget()} 很像，只是在执行结束时
	 * 需要调用{@link endWidget()}
	 * @param string $className 小部件类名。
	 * @param array $properties 小部件的初始属性列表。 (属性名 =>属性值)
	 * @return \Sky\web\widgets\Widget 创建后的小部件
	 * @see endWidget
	 */
	public function beginWidget($className,$properties=array())
	{
		$widget=$this->createWidget($className,$properties);
		$this->_widgetStack[]=$widget;
		return $widget;
	}
	
	/**
	 * 停止执行小部件。
	 * 该方法和 {@link beginWidget()}配套使用。
	 * @param string $id 调式用的标记。
	 * @return \Sky\web\widgets\Widget 刚刚结束的小部件。
	 * @throws \Exception 如果多调了endWidget
	 * @see beginWidget
	 */
	public function endWidget($id='')
	{
		if(($widget=array_pop($this->_widgetStack))!==null)
		{
			$widget->run();
			return $widget;
		}
		else
			throw new \Exception(get_class($this).' has an extra endWidget('.$id.') call in its view.');
	}
	
	public function beginContent($view=null,$data=array())
	{
		$this->beginWidget('Sky\web\widgets\ContentDecorator',array('view'=>$view, 'data'=>$data));
	}
	
	/**
	 * Ends the rendering of content.
	 * @see beginContent
	 */
	public function endContent()
	{
		$this->endWidget('ContentDecorator');
	}
}