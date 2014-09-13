<?php
namespace Sky\web\widgets;

use Sky\help\Html;
class ActiveForm extends Widget{
	/**
	 * @var array 要渲染到表单的其他HTML属性。
	 */
	public $htmlOptions=array();
	/**
	 * @var boolean 是否生成有状态的form (参见 {@link Html::statefulForm}). 默认为false.
	 */
	public $stateful=false;
	public $action='';
	/**
	 * @var string 表单的提交方法. 应该为'post' 或 'get'.
	 * 默认为 'post'.
	 */
	public $method='post';
	/**
	 * @var string 错误信息的css类名。
	 */
	public $errorMessageCssClass;
	
	/**
	 * Initializes the widget.
	 * This renders the form open tag.
	 */
	public function init()
	{
		if(!isset($this->htmlOptions['id']))
			$this->htmlOptions['id']=$this->getId();
		else
			$this->id=$this->htmlOptions['id'];
		
		if($this->stateful)
			echo Html::statefulForm($this->action, $this->method, $this->htmlOptions);
		else
			echo Html::beginForm($this->action, $this->method, $this->htmlOptions);
			
		if($this->errorMessageCssClass===null)
			$this->errorMessageCssClass=Html::$errorMessageCss;
	}
	
	public function run()
	{
		echo Html::endForm();
	}
	
	public function passwordField($model,$attribute,$htmlOptions=array())
	{
		return Html::activePasswordField($model,$attribute,$htmlOptions);
	}
	
	public function error($model,$attribute,$htmlOptions=array())
	{
		if(!isset($htmlOptions['class']))
			$htmlOptions['class']=$this->errorMessageCssClass;
		
		return Html::error($model,$attribute,$htmlOptions);
	}
	
	public function labelEx($model,$attribute,$htmlOptions=array())
	{
		return Html::activeLabelEx($model,$attribute,$htmlOptions);
	}
	
	public function textField($model,$attribute,$htmlOptions=array())
	{
		return Html::activeTextField($model,$attribute,$htmlOptions);
	}
	
	public function dropDownList($model,$attribute,$data,$htmlOptions=array())
	{
		return Html::activeDropDownList($model,$attribute,$data,$htmlOptions);
	}
	
	public function fileField($model,$attribute,$htmlOptions=array())
	{
		return Html::activeFileField($model,$attribute,$htmlOptions);
	}
}