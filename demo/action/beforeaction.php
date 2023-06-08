<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

// use to check login required.
//IN::mustHave("User-Agent", '/\w+/');
$device = getallheaders()['User-Agent'];
IN::mustHave("deviceid", '/\w+/');
$deviceid = $_deviceid;
unset($_deviceid);

$token = cookie("token");
IN::mayHave("token");
if ($_token) $token = $_token;
unset($_token);

$ip = getRealIp();

// 登录api加上这个以绕过token验证
IN::mayHave("authing");

if (empty($token) && empty($_authing)) {
	// resp code: 401
	throw new REClientNotLogin("请登录");
}

# 固定可用的全局变量有：$device, $deviceid, $token, $ip,
if	($_authing) return ; // 认证过程忽略一下用户相关的预处理。


$cache = new MCache($token);
$profile = Profile::fromCached($cache, $token);
if (empty($profile))
	$profile = Profile::fromDatabase($token);
if (empty($profile))
	throw new REClientNotLogin("登录过期请重新登录");

// 更新缓存
$profile->count++;
$accesstime = $profile->utime;
$profile->utime = time();
$profile->expire = $profile->utime + $profile->count * $GLOBALS['config']['sys']['token_plus'];
Profile::saveToCache($cache, $profile);
if (getdate($accesstime)['yday'] != getdate()['yday']) {
	// sync to db
	UserM::updateDevice($profile->id, $profile->expire, $profile->count, $ip);
}
$uid = $profile->uid;

# 固定可用的全局变量有
# $device, $deviceid, $token, $ip, $uid, $profile
