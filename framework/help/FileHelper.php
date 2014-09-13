<?php
namespace Sky\help;

/**
 * @author Jiangyumeng
 *
 */
class FileHelper{
	/**
	 * 创建一个新目录
	 *
	 * 该方法与PHP的mkdir()函数类似，不同的在于它使用了chmod()来设置
	 * 创建的目录的权限。
	 *
	 * @param string $path 要创建的目录的路径。
	 * @param integer $mode 新创建目录的权限。
	 * @param boolean $recursive 如果父目录不存在的话是否创建。
	 * @return boolean 目录是否成功创建。
	 */
	public static function createDirectory($path, $mode = 0775, $recursive = true)
	{
		if (is_dir($path)) {
			return true;
		}
		$parentDir = dirname($path);
		if ($recursive && !is_dir($parentDir)) {
			static::createDirectory($parentDir, $mode, true);
		}
		$result = mkdir($path, $mode);
		chmod($path, $mode);
		return $result;
	}
}