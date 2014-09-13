<?php
namespace Sky\base;
/**
 * HttpException 用来处理有终端用户非法操作产生的异常
 *.
 *HTTP 错误码可以通过{@link statusCode}获得.
 * 错误处理程序可通过此状态码决定如何格式化错误页面。
 * @author Jiangyumeng
 */
class HttpException extends \Exception{
	/**
	 * @var integer HTTP 状态码, 例如403, 404, 500等等.
	 */
	public $statusCode;
	
	/**
	 * 构造函数
	 * @param integer $status HTTP 状态码, 例如403, 404, 500等等.
	 * @param string $message 错误信息。
	 * @param integer $code 错误码
	 */
	public function __construct($status,$message=null,$code=0)
	{
		$this->statusCode=$status;
		parent::__construct($message,$code);
	}
}