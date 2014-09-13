<?php
namespace Sky\utils\ip;
use Sky\Sky;
class IPAnalyze {
	function convertip($ip) {
		
	    $dat_path = __DIR__.DIRECTORY_SEPARATOR.'ip.dat';
	    
	    if(!preg_match("/^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/", $ip)) {
	        return 'IP格式错误';
	    }
	  
	    if(!$fd = @fopen($dat_path, 'rb')){
	        return '未找到文件或无权限';
	    }
	    $ip = explode('.', $ip);
	    $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];
	    $DataBegin = fread($fd, 4);
	    $DataEnd = fread($fd, 4);
	    $ipbegin = implode('', unpack('L', $DataBegin));
	    if($ipbegin < 0) $ipbegin += pow(2, 32);
	    $ipend = implode('', unpack('L', $DataEnd));
	    if($ipend < 0) $ipend += pow(2, 32);
	    $ipAllNum = ($ipend - $ipbegin) / 7 + 1;
	    $BeginNum = 0;
	    $EndNum = $ipAllNum;
	    $ip1num = 0;
	    $ip2num = 0;
	    while($ip1num>$ipNum || $ip2num<$ipNum) {
	        $Middle= intval(($EndNum + $BeginNum) / 2);
	        fseek($fd, $ipbegin + 7 * $Middle);
	        $ipData1 = fread($fd, 4);
	        if(strlen($ipData1) < 4) {
	            fclose($fd);
	            return 'SystemError';
	        }
	        $ip1num = implode('', unpack('L', $ipData1));
	        if($ip1num < 0) $ip1num += pow(2, 32);
	        if($ip1num > $ipNum) {
	            $EndNum = $Middle;
	            continue;
	        }
	        $DataSeek = fread($fd, 3);
	        if(strlen($DataSeek) < 3) {
	            fclose($fd);
	            return 'SystemError';
	        }
	        $DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
	        fseek($fd, $DataSeek);
	        $ipData2 = fread($fd, 4);
	        if(strlen($ipData2) < 4) {
	            fclose($fd);
	            return 'SystemError';
	        }
	        $ip2num = implode('', unpack('L', $ipData2));
	        if($ip2num < 0) $ip2num += pow(2, 32);
	        if($ip2num < $ipNum) {
	            if($Middle == $BeginNum) {
	                fclose($fd);
	                return 'Unknown';
	            }
	            $BeginNum = $Middle;
	        }
	    }
	    $ipAddr1 = "";
	    $ipAddr2 = "";
	    $ipFlag = fread($fd, 1);
	    if($ipFlag == chr(1)) {
	        $ipSeek = fread($fd, 3);
	        if(strlen($ipSeek) < 3) {
	            fclose($fd);
	            return 'SystemError';
	        }
	        $ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
	        fseek($fd, $ipSeek);
	        $ipFlag = fread($fd, 1);
	    }
	    if($ipFlag == chr(2)) {
	        $AddrSeek = fread($fd, 3);
	        if(strlen($AddrSeek) < 3) {
	            fclose($fd);
	            return 'SystemError';
	        }
	        $ipFlag = fread($fd, 1);
	        if($ipFlag == chr(2)) {
	            $AddrSeek2 = fread($fd, 3);
	            if(strlen($AddrSeek2) < 3) {
	                fclose($fd);
	                return 'SystemError';
	            }
	            $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
	            fseek($fd, $AddrSeek2);
	        } else {
	            fseek($fd, -1, SEEK_CUR);
	        }
	        while(($char = fread($fd, 1)) != chr(0))
	            $ipAddr2 .= $char;
	        $AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
	        fseek($fd, $AddrSeek);
	        while(($char = fread($fd, 1)) != chr(0))
	            $ipAddr1 .= $char;
	    } else {
	        fseek($fd, -1, SEEK_CUR);
	        while(($char = fread($fd, 1)) != chr(0))
	            $ipAddr1 .= $char;
	        $ipFlag = fread($fd, 1);
	        if($ipFlag == chr(2)) {
	            $AddrSeek2 = fread($fd, 3);
	            if(strlen($AddrSeek2) < 3) {
	                fclose($fd);
	                return 'SystemError';
	            }
	            $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
	            fseek($fd, $AddrSeek2);
	        } else {
	            fseek($fd, -1, SEEK_CUR);
	        }
	        while(($char = fread($fd, 1)) != chr(0)){
	            $ipAddr2 .= $char;
	        }
	    }
	    fclose($fd);
	    if(preg_match('/http/i', $ipAddr2)) {
	        $ipAddr2 = '';
	    }
	    $ipaddr = "$ipAddr1 $ipAddr2";
	    $ipaddr = preg_replace('/CZ88.Net/is', '', $ipaddr);
	    $ipaddr = preg_replace('/^s*/is', '', $ipaddr);
	    $ipaddr = preg_replace('/s*$/is', '', $ipaddr);
	    if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
	        $ipaddr = 'Unknown';
	    }
	    $ipaddr = iconv("GB2312","UTF-8//IGNORE",$ipaddr);
	    return $ipaddr;
	}
	
	function get_str($str) {
	    $a = explode(' ',$str);
	    $addr = $a[0];
	    return $addr;
	}
	
	function get_ip($ip) {
		$addr = $this->convertip($ip);
		if ($addr != "IP格式错误" && $addr != "未找到文件或无权限" && $addr != "SystemError") {
		    $start_str = substr($addr,0,6);
		    if ($start_str == "上海" || $start_str == "北京" || $start_str == "天津" || $start_str == "重庆") {
		        return "中国,".$start_str."市,".$start_str."市";
		    } elseif ($start_str == "西藏" || $start_str == "新疆" || $start_str == "广西" || $start_str == "宁夏") {
		        $addrs = $this->get_str($addr);
		        $city = substr($addrs,6);
				return "中国,".$start_str.",".$city;
		    } elseif ($start_str == "内蒙") {
		        $addrs = $this->get_str($addr);
		        $city = substr($addrs,9);
				return "中国,内蒙古,".$city;
		    } elseif ($start_str == "香港") {
		        return "中国,".$start_str.",".$start_str;
		    } else {
		        $addrs = $this->get_str($addr);
		        if (strpos($addrs, "省")) {
		        	$addr_array = explode("省",$addrs);
		        	$addr_str = implode("省,",$addr_array);
		        	return "中国,".$addr_str;
		        } else {
		        	return "";
		        }
		    }
		} else {
			return "Error:$addr";
		}
	}
}

//$ip = "183.12.12.12";
//$i = new ip_analyze();
//$addr = $i->get_ip($ip);
//echo $addr;