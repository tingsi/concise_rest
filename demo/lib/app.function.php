<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb
/*
 *  公共函数 *  api 用到的函数，放到这里。
 *
 *  单个中文件可能不会被其他地方调用的函数在文件中单独定义
 */

 // @return string of 32char
function newRowID($source='')
{
	$t = date("YmdHis");
	$m = md5( uniqid($source) );
    return $t . substr($m, 14);
}

// @return string of 16char
function newShortID()
{
	return bin2hex(random_bytes(8));
}


function getRealIp()
{
	$ip = false;
	//客户端IP 或 NONE
	if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}

	//多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

		$ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);

		if ($ip) {
			array_unshift($ips, $ip);
			$ip = false;
		}
		for ($i = 0; $i < count($ips); $i++) {
			if (!fnmatch("^(10│172.16│192.168).", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	//客户端IP 或 (最后一个)代理服务器 IP
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

