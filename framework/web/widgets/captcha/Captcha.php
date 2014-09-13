<?php
namespace Sky\web\widgets\captcha;

use Sky\web\widgets\Widget;
use Sky\help\Html;
class Captcha extends Widget{
	/**
	 * @var array 应用到图片元素的HTML属性。
	 */
	public $imageOptions=array();
	/**
	 * @var string  提供验证码图像的actionID。默认为'captcha',
	 * 意味着当前controller的captcha action。该属性也可以是
	 * 'ControllerID/ActionID'的格式。
	 */
	public $captchaAction='captcha';
	
	/**
	 * 渲染小部件。
	 */
	public function run(){
		if(self::checkRequirements()){
			$this->renderImage();
// 			$this->registerClientScript();
		}else
			throw new \Exception('GD with FreeType or ImageMagick PHP extensions are required.');
	}
	
	/**
	 * 渲染验证码图像。
	 */
	protected function renderImage(){
		if(!isset($this->imageOptions['id']))
			$this->imageOptions['id']=$this->getId();
	
		$url=$this->getController()->createUrl($this->captchaAction,array('v'=>uniqid()));
		$alt=isset($this->imageOptions['alt'])?$this->imageOptions['alt']:'';
		echo Html::image($url,$alt,$this->imageOptions);
	}
	
	/**
	 * 检测具体的图像扩展是否加载。
	 * @param string 要检测的扩展名。可能值为 'gd', 'imagick' 和 null.
	 * 默认值为null，意味着两种扩展都将被检查。
	 * @return boolean true如果支持PNG的ImageMagick扩展或支持FreeType的GD扩展已经加载，否则返回false。
	 */
	public static function checkRequirements($extension=null){
		if(extension_loaded('imagick')){
			$imagick=new \Imagick();
			$imagickFormats=$imagick->queryFormats('PNG');
		}
		if(extension_loaded('gd')){
			$gdInfo=gd_info();
		}
		if($extension===null){
			if(isset($imagickFormats) && in_array('PNG',$imagickFormats))
				return true;
			if(isset($gdInfo) && $gdInfo['FreeType Support'])
				return true;
		}elseif($extension=='imagick' && isset($imagickFormats) && in_array('PNG',$imagickFormats))
			return true;
		elseif($extension=='gd' && isset($gdInfo) && $gdInfo['FreeType Support'])
			return true;
		return false;
	}
}