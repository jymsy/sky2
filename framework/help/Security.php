<?php
namespace Sky\help;

/**
 * 
 * @author Jiangyumeng
 *
 */
class Security {
	/**
	 * 根据密码和随机盐生成安全的hash值。
	 *
	 * 生成的hash值可以存到数据库中 (例如 `CHAR(64) CHARACTER SET utf8` on MySQL).
	 * 后面要验证密码的时候，hash可以传给{@link validatePassword()}
	 * 例如：
	 *
	 * ~~~
	 * // 生成hash (通常在用户注册或更改密码的时候)
	 * $hash = Security::generatePasswordHash($password);
	 * // ...在数据库中保存$hash...
	 *
	 * // 登陆的时候，从数据库中拿到$hash来验证传入的密码是否正确
	 * if (Security::validatePassword($password, $hash) {
	 *     // 密码正确
	 * } else {
	 *     // 密码错误
	 * }
	 * ~~~
	 *
	 * @param string $password 要生成hash的密码。
	 * @param integer $cost Blowfish哈希算法的Cost参数
	 * cost的值越大，生成hash值的时间越长。
	 * 然而更大的cost能够延长暴力破解的时间。
	 * 为了最好的防止暴力破解，在生产服务器上将它设置为可容忍的最大值。
	 * 计算hash值的时间随着$cost的增加成倍增长。
	 * 例如，如果当$cost的值为14的时候要花1秒，那么随着$cost的增加，
	 * 花费的时间为2^($cost - 14)秒。
	 * @throws \Exception password或cost参数格式错误。
	 * @return string 密码的hash值, ASCII 编码并且长度不超过64个字符。
	 * @see validatePassword()
	 */
	public static function generatePasswordHash($password, $cost = 13)
	{
		$salt = static::generateSalt($cost);
		$hash = crypt($password, $salt);
		
		if (!is_string($hash) || strlen($hash) < 32) {
			throw new \Exception('Unknown error occurred while generating hash.');
		}
	
		return $hash;
	}
	
	/**
	 * 使用hash验证密码
	 * @param string $password 要验证的密码。
	 * @param string $hash 用来验证密码的hash
	 * @return boolean 密码是否正确。
	 * @throws \Exception 密码格式错误或crypt()不支持Blowfish
	 * @see generatePasswordHash()
	 */
	public static function validatePassword($password, $hash)
	{
		if (!is_string($password) || $password === '') {
			throw new \Exception('Password must be a string and cannot be empty.');
		}
	
		if (!preg_match('/^\$2[axy]\$(\d\d)\$[\.\/0-9A-Za-z]{22}/', $hash, $matches) || $matches[1] < 4 || $matches[1] > 31) {
			throw new \Exception('Hash is invalid.');
		}
	
		$test = crypt($password, $hash);
		$n = strlen($test);
		if ($n < 32 || $n !== strlen($hash)) {
			return false;
		}
	
		// 使用for循环来比较两个字符串以防止计时攻击。参见:
		// http://codereview.stackexchange.com/questions/13512
		$check = 0;
		for ($i = 0; $i < $n; ++$i) {
			$check |= (ord($test[$i]) ^ ord($hash[$i]));
		}
	
		return $check === 0;
	}
	
	/**
	 * 生成用来生成hash密码的随机盐。
	 *
	 * PHP [crypt()](http://php.net/manual/en/function.crypt.php) 
	 * 内制方法使用Blowfish哈希算法的时候，需要一个特殊格式的盐字符串。
	 * "$2a$", "$2x$" 或 "$2y$", 一个两位 cost 参数, "$",
	 * 和 22 个 "./0-9A-Za-z"中的字符。
	 *
	 * @param integer $cost cost参数。
	 * @return string 随机盐字符串。
	 * @throws \Exception 如果cost参数小于4或大于31.
	 */
	protected static function generateSalt($cost = 13)
	{
		$cost = (int)$cost;
		if ($cost < 4 || $cost > 31) {
			throw new \Exception('Cost must be between 4 and 31.');
		}
	
		// 通过mt_rand()获得 20 * 8bits的为随机熵
		$rand = '';
		for ($i = 0; $i < 20; ++$i) {
			$rand .= chr(mt_rand(0, 255));
		}
	
		$rand .= microtime();
		$rand = sha1($rand, true);
		// 使用cost参数格式化Blowfish算法的前缀。
		$salt = sprintf("$2y$%02d$", $cost);
		$salt .= str_replace('+', '.', substr(base64_encode($rand), 0, 22));
		return $salt;
	}
	
	/**
	 * 随机生成指定长度的字符串
	 * @param integer $length 字符串长度
	 * @param string $chars 字符库，默认为英文大小写字母和数字。
	 * @return string
	 */
	public static function generateStr($length=8, $chars='')
	{
		if ($chars=='') {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		}
		
		$password = '';
		$len=strlen($chars);
		for ( $i = 0; $i < $length; $i++ )
		{
			$password .= $chars[ mt_rand(0, $len - 1) ];
		}
		
		return $password;
	}
	
	/**
	 * 基于base64的改进方法加解密字符串
	 * @param string $string 要加密的字符串
	 * @param string $action  ENCODE加密，DECODE解密
	 * @param string $key 密钥
	 * @return Ambigous <string, boolean>
	 */
	public static function strCode($string,$action='ENCODE',$key='')
	{
		$string.='';
		if($action != 'ENCODE')
			$string = self::base64url($string, false);
		$code = '';
		$key  = substr(md5($key),7,18);

		$keylen = strlen($key);
		$strlen = strlen($string);
		for ($i=0;$i<$strlen;$i++)
		{
			$k = $i % $keylen;
			$code  .= $string[$i] ^ $key[$k];
		}
	
		return ($action!='DECODE' ?self::base64url($code, true) : $code);
	}
	
	/**
	 * 适合url的base64编解码。
	 * 将base64中的+,/,=进行替换，以适配url
	 * @param string $str 要编解码的字符串
	 * @param boolean $encode 编码true，解码false
	 * @return mixed|string 编解码后的字符串
	 */
	public static function base64url($str='',$encode)
	{
		if ($encode===true) {
			return str_replace(array('+','/','='), array(',','_','.'), base64_encode($str));
		}else{
			$str=str_replace(array(',','_','.'), array('+','/','='), $str);
			return base64_decode($str);
		}
	}
}