<?php
namespace Sky\help;

/**
 * 
 * @author Jiangyumeng
 *
 */
class Inflector{
	/**
	 * 将一个驼峰形式的字符串转换为空格分隔的字符串。
	 * 例如, 'PostTag' 将被转换为'Post Tag'.
	 * @param string $name 要转换的字符串。
	 * @param boolean $ucwords 是否将首字母大写
	 * @return string 转换后的结果
	 */
	public static function camel2words($name, $ucwords = true)
	{
		$label = trim(strtolower(str_replace(array(
				'-',
				'_',
				'.'
		), ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name))));
		return $ucwords ? ucwords($label) : $label;
	}
}