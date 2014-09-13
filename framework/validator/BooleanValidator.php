<?php
namespace Sky\validator;

/**
 * BooleanValidator 检测属性值是否为bool值。
 * 
 * 可能的boolean值可以通过{@link trueValue}和{@link falseValue}设置。
 * 也可以设置是否严格{@link strict}比较。
 * 
 * @author Jiangyumeng
 *
 */
class BooleanValidator extends Validator{

	/**
	 * @var mixed 代表true状态的值。默认为 '1'.
	 */
	public $trueValue = '1';
	/**
	 * @var mixed 代表false状态的值。默认为 '0'.
	 */
	public $falseValue = '0';
	/**
	 * @var boolean 是否使用严格匹配。
	 * 当为true的时候，属性值和类型必须同时匹配{@link trueValue}和{@link falseValue}
	 * 默认为false，意味着只要值匹配就可以。
	 */
	public $strict = false;
	
	/**
	 * 初始化验证器。
	 */
	public function init()
	{
// 		parent::init();
		if ($this->message === null) {
			$this->message = '{attribute} must be either "{true}" or "{false}".';
		}
	}
	
	/**
	 * 验证对象的属性。
	 * 如果有错误的话，错误信息将会添加到对象中去。
	 * @param \Sky\base\Model $object 要验证的对象。
	 * @param string $attribute 要验证的属性。
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		if (!$this->validateValue($value)) {
			$this->addError($object, $attribute, $this->message, array(
					'{true}' => $this->trueValue,
					'{false}' => $this->falseValue,
			));
		}
	}
	
	/**
	 * 验证给定的值。
	 * @param mixed $value 要验证的值。
	 * @return boolean 值是否合法。
	 */
	public function validateValue($value)
	{
		return !$this->strict && ($value == $this->trueValue || $value == $this->falseValue)
		|| $this->strict && ($value === $this->trueValue || $value === $this->falseValue);
	}
}