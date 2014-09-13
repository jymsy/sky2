<?php
namespace Sky\web\widgets\captcha;

use Sky\base\Action;
use Sky\Sky;
use Sky\help\JSON;
/**
 * @author Jiangyumeng
 *
 */
class CaptchaAction extends Action{
	
	/**
	 * 标志验证码图片是否要重新生成的GET参数名。
	 */
	const REFRESH_GET_VAR='refresh';
	/**
	 * 变量名前缀。
	 */
	const SESSION_VAR_PREFIX='CaptchaAction.';
	/**
	 * @var string 固定的验证码。 当该属性设置的时候，
	 * {@link getVerifyCode}将总返回该值。
	 * 主要用在自动化测试的时候。
	 * 默认为null，意味着随机生成验证码。
	 */
	public $fixedVerifyCode;
	/**
	 * @var integer 生成字符串的最小长度。默认为 6.
	 */
	public $minLength = 6;
	/**
	 * @var integer 生成字符串的最大长度。默认为 7.
	 */
	public $maxLength = 7;
	/**
	 * @var string 用来显示验证码图片的图像扩展。可能值为
	 * 'gd', 'imagick' 和 null. Null意味着两种都检测，更倾向于imagick。
	 *  默认值为null
	 */
	public $backend;
	/**
	 * @var boolean 是否使用透明背景。默认为false
	 */
	public $transparent = false;
	/**
	 * @var integer 背景颜色。 如 0x55FF00.
	 * 默认为 0xFFFFFF（白色）
	 */
	public $backColor = 0xFFFFFF;
	/**
	 * @var integer 字体颜色。如 0x55FF00. 默认为 0x2040A0 (蓝色).
	 */
	public $foreColor = 0x2040A0;
	/**
	 * @var integer 生成验证码图片的宽度。 默认为 120.
	 */
	public $width = 120;
	/**
	 * @var integer 生成验证码图片的高度。 默认为 50.
	 */
	public $height = 50;
	/**
	 * @var string TrueType 字体文件。默认为 SpicyRice.ttf 。
	 */
	public $fontFile;
	/**
	 * @var integer 字符间距。默认为 -2。
	 * 你可以改变这个值来更改验证码的可读性。
	 **/
	public $offset = -2;
	/**
	 * @var integer 文本周围的填充. 默认为 2.
	 */
	public $padding = 2;
	
	/**
	 * 运行action
	 */
	public function run()
	{
		if(isset($_GET[self::REFRESH_GET_VAR]))  // AJAX request for regenerating code
		{
			$code=$this->getVerifyCode(true);
// 			echo JSON::encode(array(
// 					'hash1'=>$this->generateValidationHash($code),
// 					'hash2'=>$this->generateValidationHash(strtolower($code)),
// 					// we add a random 'v' parameter so that FireFox can refresh the image
// 					// when src attribute of image tag is changed
// 					'url'=>$this->getController()->createUrl($this->getId(),array('v' => uniqid())),
// 			));
		}
		else
			$this->renderImage($this->getVerifyCode());
		Sky::$app->end();
	}
	
	/**
	 * 根据验证码使用特定的库来渲染验证码图片，推荐imagick
	 * @param string $code 验证码
	 */
	protected function renderImage($code)
	{
		if($this->backend===null && Captcha::checkRequirements('imagick') || $this->backend==='imagick')
			$this->renderImageImagick($code);
		else if($this->backend===null && Captcha::checkRequirements('gd') || $this->backend==='gd')
			$this->renderImageGD($code);
	}
	
	/**
	 * 获取验证码。
	 * @param boolean $regenerate 是否要重新生成验证码。
	 * @return string 验证码。
	 */
	public function getVerifyCode($regenerate=false)
	{
		if($this->fixedVerifyCode !== null)
			return $this->fixedVerifyCode;
	
// 		$session = Sky::$app->session;
// 		$session->open();
// 		$name = $this->getSessionKey();
// 		if($session[$name] === null || $regenerate)
// 		{
// 			$session[$name] = $this->generateVerifyCode();
// 			$session[$name . 'count'] = 1;
// 		}
// 		return $session[$name];
		return $this->generateVerifyCode();
	}
	
	/**
	 * 生成新的验证码。
	 * @return string 生成的验证码。
	 */
	protected function generateVerifyCode()
	{
		if($this->minLength > $this->maxLength)
			$this->maxLength = $this->minLength;
		if($this->minLength < 3)
			$this->minLength = 3;
		if($this->maxLength > 20)
			$this->maxLength = 20;
		$length = mt_rand($this->minLength,$this->maxLength);
	
		$letters = 'bcdfghjklmnpqrstvwxyz';
		$vowels = 'aeiou';
		$code = '';
		for($i = 0; $i < $length; ++$i)
		{
			if($i % 2 && mt_rand(0,10) > 2 || !($i % 2) && mt_rand(0,10) > 9)
				$code.=$vowels[mt_rand(0,4)];
			else
				$code.=$letters[mt_rand(0,20)];
		}
	
		return $code;
	}
	
	/**
	 * 返回用来存储验证码的session变量名
	 * @return string session变量名
	 */
	protected function getSessionKey()
	{
		return self::SESSION_VAR_PREFIX . $this->getController()->getUniqueId() . '.' . $this->getId();
	}
	
	/**
	 * 使用ImageMagick库显示验证码图片。
	 * @param string $code 验证码
	 */
	protected function renderImageImagick($code)
	{
		$backColor=$this->transparent ? new \ImagickPixel('transparent') : new \ImagickPixel(sprintf('#%06x',$this->backColor));
		$foreColor=new \ImagickPixel(sprintf('#%06x',$this->foreColor));
	
		$image=new \Imagick();
		$image->newImage($this->width,$this->height,$backColor);
	
		if($this->fontFile===null)
			$this->fontFile=__DIR__.DIRECTORY_SEPARATOR.'SpicyRice.ttf';
	
		$draw=new \ImagickDraw();
		$draw->setFont($this->fontFile);
		$draw->setFontSize(30);
		$fontMetrics=$image->queryFontMetrics($draw,$code);
	
		$length=strlen($code);
		$w=(int)($fontMetrics['textWidth'])-8+$this->offset*($length-1);
		$h=(int)($fontMetrics['textHeight'])-8;
		$scale=min(($this->width-$this->padding*2)/$w,($this->height-$this->padding*2)/$h);
		$x=10;
		$y=round($this->height*27/40);
		for($i=0; $i<$length; ++$i)
		{
			$draw=new \ImagickDraw();
			$draw->setFont($this->fontFile);
			$draw->setFontSize((int)(rand(26,32)*$scale*0.8));
			$draw->setFillColor($foreColor);
			$image->annotateImage($draw,$x,$y,rand(-10,10),$code[$i]);
			$fontMetrics=$image->queryFontMetrics($draw,$code[$i]);
			$x+=(int)($fontMetrics['textWidth'])+$this->offset;
		}
	
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-Type: image/png");
		$image->setImageFormat('png');
		echo $image;
	}
	
	/**
	 * 使用GD库显示验证码图片。
	 * @param string $code 验证码
	 */
	protected function renderImageGD($code)
	{
		$image = imagecreatetruecolor($this->width,$this->height);
	
		$backColor = imagecolorallocate($image,
				(int)($this->backColor % 0x1000000 / 0x10000),
				(int)($this->backColor % 0x10000 / 0x100),
				$this->backColor % 0x100);
		imagefilledrectangle($image,0,0,$this->width,$this->height,$backColor);
		imagecolordeallocate($image,$backColor);
	
		if($this->transparent)
			imagecolortransparent($image,$backColor);
	
		$foreColor = imagecolorallocate($image,
				(int)($this->foreColor % 0x1000000 / 0x10000),
				(int)($this->foreColor % 0x10000 / 0x100),
				$this->foreColor % 0x100);
	
		if($this->fontFile === null)
			$this->fontFile = __DIR__.DIRECTORY_SEPARATOR.'SpicyRice.ttf';
	
		$length = strlen($code);
		$box = imagettfbbox(30,0,$this->fontFile,$code);
		$w = $box[4] - $box[0] + $this->offset * ($length - 1);
		$h = $box[1] - $box[5];
		$scale = min(($this->width - $this->padding * 2) / $w,($this->height - $this->padding * 2) / $h);
		$x = 10;
		$y = round($this->height * 27 / 40);
		for($i = 0; $i < $length; ++$i)
		{
			$fontSize = (int)(rand(26,32) * $scale * 0.8);
			$angle = rand(-10,10);
			$letter = $code[$i];
			$box = imagettftext($image,$fontSize,$angle,$x,$y,$foreColor,$this->fontFile,$letter);
			$x = $box[2] + $this->offset;
		}
	
		imagecolordeallocate($image,$foreColor);
	
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-Type: image/png");
		imagepng($image);
		imagedestroy($image);
	}
}