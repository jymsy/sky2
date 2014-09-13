<?php
namespace Sky\utils;

use Sky\Sky;
/**
 * 通过域名获取push服务器的ip、端口
 * @author Jiangyumeng
 *
 */
class PushServer{
	public static $cmd=1000;
	public static $svctype=102;
	public static $ver="2.0";

	/**
	 * 获取push服务器的地址
	 * @param string $serverName 获取地址的服务器域名
	 */
	public static function getHost($serverName)
	{
		$curl=Sky::$app->curl;
		$time=time();
		$deviceid=self::getDeviceId();
	
		$paramArr=array(
				'cmd'=>self::$cmd,
				'seqno'=>$time,
				'token'=>md5(self::$cmd.$deviceid.$time),
				'svctype'=>self::$svctype,
				'deviceid'=>$deviceid,
				'ver'=>self::$ver,
		);
		return $curl->post($serverName, http_build_query($paramArr));
	}
	
	protected static function getDeviceId()
	{
		$deviceid=ip2long(EXTIP);
		if ($deviceid===-1) {
			return sprintf('%u',ip2long('127.0.0.1'));
		}else{
			return sprintf('%u', $deviceid);
		}
	}
}