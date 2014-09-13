<?php
namespace Sky\validator;

use Sky\base\Component;
use Sky\Sky;

/**
 * Validator 是所有验证器类的基类。
 * 子类要重写{@link validateAttribute()}方法来提供验证数据的逻辑。
 * 
 *  Validator declares a set of [[builtInValidators|built-in validators] which can
 * be referenced using short names. They are listed as follows:
 * Validator声明了一些内置的验证器，如下：
 * 
 * - `boolean`: {@link BooleanValidator}
 * 
 * @author Jiangyumeng
 *
 */
abstract class Validator extends Component{
	/**
	 * @var string 用户自定义的错误消息。
	 * 它可以包含以下会被验证器替换的占位符：
	 *
	 * - `{attribute}`: 要验证的属性的标签。
	 * - `{value}`: 要验证的属性值。
	 */
	public $message;
	/**
	 * @var boolean 如果当前验证的属性之前已经有错误的话，是否跳过验证。
	 *  默认为true。
	 */
	public $skipOnError = true;
	/**
	 * @var boolean 如果属性值为null或空字符串的话是否跳过验证。
	 */
	public $skipOnEmpty = true;
	/**
	 * @var array 内置验证器列表 (name=>class)
	 */
	public static $builtInValidators = array(
			'boolean'=>'Sky\validator\BooleanValidator',
			'required'=>'Sky\validator\RequiredValidator',
			'match'=>'Sky\validator\RegularExpressionValidator',
			'file'=>'Sky\validator\FileValidator',
	);
	/**
	 * @var array 要验证的属性列表。
	 */
	public $attributes = array();
	
	/**
	 * 验证一个属性。
	 * 子类必须要实现该方法来提供实际的验证逻辑。
	 * @param \Sky\base\Model $object 要验证的数据对象。
	 * @param string $attribute 要验证的属性名。
	 */
	abstract public function validateAttribute($object, $attribute);
	
	/**
	 * 创建一个验证器对象
	 * @param string $type 验证器的类型。可以使内置验证器的名字或自定义验证器的类名。
	 * @param \Sky\base\Model $object 要被验证的数据对象。
	 * @param array|string $attributes 要被验证的属性列表。
	 * 既可以是包含属性名的数组，也可以是用逗号分隔的属性名。
	 * @param array $params 要被赋给验证器类的初始属性值。
	 * @return Validator 验证器类对象
	 */
	public static function createValidator($type, $object, $attributes, $params = array())
	{
		if(is_string($attributes))
			$attributes = preg_split('/[\s,]+/', $attributes, -1, PREG_SPLIT_NO_EMPTY);
		
		$params['attributes'] = $attributes;
		if (method_exists($object, $type)) {
			// 基于方法的验证器。
			$params['class'] = __NAMESPACE__ . '\InlineValidator';
			$params['method'] = $type;
		}else{ 
			if(isset(static::$builtInValidators[$type])) {
				$type = static::$builtInValidators[$type];
			}
			$params['class'] = $type;
		}
		
		$obj=Sky::createComponent($params);
		$obj->init();
		return $obj;
	}
	
	/**
	 * 验证具体的对象。
	 * @param \Sky\base\Model $object 要验证的数据对象。
	 * @param array|null $attributes 要验证的属性列表。
	 * 如果属性没有关联验证器，那么它将会被忽略。
	 * 如果该参数为null，那么{@link $this->attributes}中的每个属性都将被验证。
	 */
	public function validate($object, $attributes = null)
	{
		if (is_array($attributes)) {
			$attributes = array_intersect($this->attributes, $attributes);
		} else {
			$attributes = $this->attributes;
		}
		foreach ($attributes as $attribute) {
			$skip = $this->skipOnError && $object->hasErrors($attribute)
			|| $this->skipOnEmpty && $this->isEmpty($object->$attribute);
			if (!$skip) {
				$this->validateAttribute($object, $attribute);
			}
		}
	}
	
	/**
	 * 检测指定的值是否为空。
	 * 如果值为null、一个空数组或trim后为空字符串，那么该值将被认为是空。
	 * 注意，该方法不同于PHP的empty()方法。当值为0的时候它会返回false。
	 * @param mixed $value 要检测的值
	 * @param boolean $trim 检测的时候是否使用trim。默认为false。
	 * @return boolean 是否值为空。
	 */
	public function isEmpty($value, $trim = false)
	{
		return $value === null || $value === array() || $value === ''
				|| $trim && is_scalar($value) && trim($value) === '';
	}
	
	/**
	 * 向model对象的指定属性添加错误信息
	 * @param \Sky\base\Model $object 要验证的数据对象。
	 * @param string $attribute 要验证的属性。
	 * @param string $message 错误信息。
	 * @param array $params 要在错误信息中填充的值
	 */
	public function addError($object, $attribute, $message, $params = array())
	{
		$value = $object->$attribute;
		$params['{attribute}'] = $object->getAttributeLabel($attribute);
		$params['{value}'] = is_array($value) ? 'array()' : $value;
		$object->addError($attribute, strtr($message, $params));
	}
}