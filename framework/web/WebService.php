<?php
namespace Sky\web;
use Sky\base\Component;
/**
 * WebService 提供基于WSDL的WebService。
 * WebService利用WsdlGenerator便可自动联机生成WSDL，免去自己写WSDL的复杂麻烦。
 * 生成WSDL是基于服务提供类中的注释，可以调用generateWsdl或者renderWsdl。
 * @author Jiangyumeng
 *
 */
class WebService extends Component{
	/**
	 * @var string Web service 编码。默认为UTF-8
	 */
	public $encoding='UTF-8';
	/**
	 * @var string|array WSDL 生成器的配置信息。
	 * 通过这个属性你可以生成适合你自己的WSDL。 
	 * 该属性的值将会被传递给{@link \Sky\Sky::createComponent} 
	 * 用来创建生成器的对象。默认值为'Sky\base\WsdlGenerator'
	 */
	public $generatorConfig='Sky\web\WsdlGenerator';
	/**
	 * @var string|object 提供web service的类或者对象。
	 *  当值为类名时，此处需使用namespace来表示。
	 */
	public $provider;
	/**
	 * @var string WSDL的namespace
	 */
	public $namespace;
	
	/**
	 * 构造函数
	 * @param string|object $provider
	 * @param stirng $namespace
	 */
	public function __construct($provider,$namespace){
		$this->provider=$provider;
		$this->namespace=$namespace;
	}
	
	/**
	 * 生成和显示通过provider定义的WSDL
	 * @see generateWsdl
	 */
	public function renderWsdl(){
		$wsdl=$this->generateWsdl();
		header('Content-Type: text/xml;charset='.$this->encoding);
		header('Content-Length: '.(function_exists('mb_strlen') ? mb_strlen($wsdl,'8bit') : strlen($wsdl)));
		echo $wsdl;
	}
	
	/**
	 * 生成由provider定义的WSDL
	 * @return string 生成的WSDL字符串
	 */
	public function generateWsdl(){
		$providerClass=is_object($this->provider) ? get_class($this->provider) : \Sky\Sky::import($this->provider,true);
		$generator=\Sky\Sky::createComponent($this->generatorConfig);
		$wsdl=$generator->generateWsdl($providerClass,$this->namespace,$this->encoding);

		return $wsdl;
	}
}