<?php
namespace Sky\base;

use Sky\validator\Validator;
use Sky\help\Inflector;
use Sky\validator\RequiredValidator;
/**
 * 
 * @property array $attributes Attribute values (name => value).
 * 
 * @author Jiangyumeng
 *
 */
class Model extends Component{
	/**
	 * @var array 验证器错误 (attribute name => array of errors)
	 */
	private $_errors = array();
	/**
	 * @var ArrayObject 验证器列表。
	 */
	private $_validators;
	
	/**
	 * @return array 验证器规则。
	 */
	public function rules()
	{
		return array();
	}
	
	/**
	 * 执行验证。
	 *
	 * 该方法执行被声明在 {@link rules} 中的验证规则。
	 *
	 * 在执行过程中出现的错误可以通过{@link getErrors}获得。
	 *
	 * @param array $attributes 要验证的属性列表。
	 * 默认为null,意味着所有的属性都要被验证。
	 * @param boolean $clearErrors 在执行验证之前是否调用 {@link clearErrors}。
	 * @return boolean 验证是否成功。
	 * @see beforeValidate
	 * @see afterValidate
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		if($clearErrors)
			$this->clearErrors();
		if($this->beforeValidate())
		{
			foreach($this->getValidators() as $validator)
				$validator->validate($this, $attributes);
			$this->afterValidate();
			return !$this->hasErrors();
		}
		else
			return false;
	}
	
	/**
	 * 移除某一属性或所有属性的错误信息。
	 * @param string $attribute 属性名。为null的话则移除所有。
	 */
	public function clearErrors($attribute = null)
	{
		if($attribute === null)
			$this->_errors = array();
		else
			unset($this->_errors[$attribute]);
	}
	
	/**
	 * 返回当前可用的验证器。
	 * @return array 当前可用的验证器。
	 */
	public function getValidators()
	{
		if ($this->_validators === null) {
			$this->_validators = $this->createValidators();
		}
		return $this->_validators;
	}
	
	/**
	 * 基于{@link rules}中的规则生成验证器对象。
	 * @throws \Exception 如果验证规则配置错误。
	 * @return \ArrayObject 验证器对象。
	 */
	public function createValidators()
	{
		$validators = new \ArrayObject;
		foreach($this->rules() as $rule)
		{
			if(isset($rule[0], $rule[1]))  // attributes, validator name
				$validators->append(Validator::createValidator($rule[1], $this, $rule[0], array_slice($rule, 2)));
			else
				throw new \Exception('Invalid validation rule: a rule must specify both attribute names and validator type.');
		}
		return $validators;
	}
	
	protected function beforeValidate()
	{
		return true;
	}
	
	protected function afterValidate()
	{
		
	}
	
	/**
	 * 返回该属性是否是必须的
	 * @param string $attribute 属性名。
	 * @return boolean 该属性是否是必须的。
	 */
	public function isAttributeRequired($attribute)
	{
		foreach($this->getValidators($attribute) as $validator)
		{
			if($validator instanceof RequiredValidator)
				return true;
		}
		return false;
	}
	
	/**
	 * 返回boolean以判断是否有验证错误。
	 * @param string $attribute 属性名。检测所有属性的话用null。
	 * @return boolean 是否有错误。
	 */
	public function hasErrors($attribute = null)
	{		
		return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
	}
	
	/**
	 * 向指定的规则添加错误。
	 * @param string $attribute 属性名。
	 * @param string $error 新错误信息。
	 */
	public function addError($attribute, $error = '')
	{
		$this->_errors[$attribute][] = $error;
	}
	
	/**
	 * 返回指定属性的第一个错误。
	 * @param string $attribute 属性名。
	 * @return string 错误信息. 如果没有错误的话返回null。
	 */
	public function getError($attribute)
	{
		return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
	}
	
	/**
	 * 返回指定属性或全部属性的错误。
	 * @param string $attribute 属性名。为null的话则获取全部属性。
	 * @return array 错误数组。没有错误的话返回空数组。
	 * 注意当返回所有属性的错误时，结果时一个二维数组，如下：
	 * ~~~
	 * array(
	 *     'username' => array(
	 *         'Username is required.',
	 *         'Username must contain only word characters.',
	 *     ),
	 *     'email' => array(
	 *         'Email address is invalid.',
	 *     )
	 * )
	 * ~~~
	 *
	 */
	public function getErrors($attribute = null)
	{
		if ($attribute === null) {
			return $this->_errors === null ? array() : $this->_errors;
		} else {
			return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : array();
		}
	}
	
	/**
	 * 返回指定属性的标签。
	 * @param string $attribute 属性名
	 * @return string 属性标签。
	 * @see generateAttributeLabel
	 * @see attributeLabels
	 */
	public function getAttributeLabel($attribute)
	{
		$labels = $this->attributeLabels();
		return isset($labels[$attribute]) ? $labels[$attribute] : $this->generateAttributeLabel($attribute);
	}
	
	/**
	 * 返回属性标签。
	 *
	 * 属性标签的主要目的是用来显示。例如，一个属性名为'firstName'，
	 * 我们可以定义一个对用户更有好的标签'First Name'来显示给终端用户。
	 *
	 * 默认的话使用{@link generateAttributeLabel()}生成属性的标签。
	 *
	 * @return array 属性标签 (name => label)
	 * @see generateAttributeLabel
	 */
	public function attributeLabels()
	{
		return array();
	}
	
	/**
	 * 基于给定的属性名生成用户友好的标签。
	 * 通过将下划线，横线和点替换为空格符，
	 * 将每个单词的首字母转换为大写，来完成这件事。
	 * 例如, 'department_name' 或'DepartmentName' 将生成'Department Name'.
	 * @param string $name 属性名
	 * @return string 属性的标签
	 */
	public function generateAttributeLabel($name)
	{
		return Inflector::camel2words($name, true);
	}
	
	/**
	 * 返回属性名列表。
	 * 默认情况下，该方法返回类的所有的public非static属性
	 * 你可以重写该方法来改变默认的做法。
	 * @return array 属性名列表。
	 */
	public function attributes()
	{
		$class = new \ReflectionClass($this);
		$names = array();
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			if (!$property->isStatic()) {
				$names[] = $name;
			}
		}
		return $names;
	}
	
	/**
	 * 返回属性值。
	 * @param array $names 要获取值的属性列表。
	 * 默认为null，意味着返回所有的属性。
	 * 如果为数组的话，只返回里面的属性值。
	 * @param array $except 不希望返回的属性名
	 * @return array 属性值(name => value).
	 */
	public function getAttributes($names = null, $except = array())
	{
		$values = array();
		if ($names === null) {
			$names = $this->attributes();
		}
		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}
		foreach ($except as $name) {
			unset($values[$name]);
		}
	
		return $values;
	}
	
	/**
	 * 设置属性值。
	 * @param array $values 指定给model的属性值 (name => value).
	 */
	public function setAttributes($values)
	{
		if (is_array($values)) {
			$attributes = array_flip($this->attributes());
			foreach ($values as $name => $value) {
				if (isset($attributes[$name]))
					$this->$name = $value;
			}
		}
	}
}
