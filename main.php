<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

/*
 to find bom in files, use this:

find -type f|while read file;do [ "`head -c3 -- "$file"`" == $'\xef\xbb\xbf' ] && echo "found BOM in: $file";done

 */

defined('DS')    || define('DS', DIRECTORY_SEPARATOR);
define('CROOT', dirname( __FILE__ ) . DS );
defined('AROOT') || define('AROOT', dirname( CROOT ) . DS );

set_include_path(get_include_path() . PATH_SEPARATOR . CROOT);
set_include_path(get_include_path() . PATH_SEPARATOR . AROOT. DS . 'lib');

date_default_timezone_set('PRC'); // 中国时区

error_reporting(E_ALL);

// framworks lib
include_once( CROOT . DS . 'core.function.php' );
include_once( CROOT . DS . 'rest.function.php' );
include_once( CROOT . DS . 'error.function.php' );
include_once( CROOT . DS . 'cache.function.php' );
include_once( CROOT . DS . 'db.class.php' );  // 函数式数据库访问
include_once( CROOT . DS . 'odb.class.php' ); // 支持多数据库切换的访问，以支持SaaS
include_once( CROOT . DS . 'dbt.class.php');  // 基于强封装的表访问
include_once( CROOT . DS . 'odb.class.php' );
include_once( CROOT . DS . 'http.php' );

// Application lib
@include_once( p(AROOT, 'config.php') );
@include_once( p(AROOT, 'lib', 'app.function.php') );

// 自动加载用户类
spl_autoload_register(function ($class_name) {
        $classfile = p(AROOT, 'lib', 'class', strtolower("$class_name.class.php"));
        if (!file_exists($classfile)) $classfile = p(AROOT, 'lib', 'class', strtolower("$class_name.php"));
        if (!file_exists($classfile)) $classfile = p(CROOT, 'class', strtolower("$class_name.class.php"));
        if (!file_exists($classfile)) $classfile = p(CROOT, 'class', strtolower("$class_name.php"));
        if (file_exists($classfile)) include_once $classfile;
        });


//站点名称
$protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? "https" : "http";

$site = "{$protocol}://{$_SERVER['HTTP_HOST']}";
$site .= (isset($_SERVER['HTTP_PORT']) && $_SERVER['HTTP_PORT'] != 80)  ? ":{$_SERVER['HTTP_PORT']}/" : "/";
define('SITE_URL', $site); unset($site); unset($protocol);

//语言检测
setLocalLang();


// HTTP 头，会话等启动。
if (!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos('gzip',$_SERVER['HTTP_ACCEPT_ENCODING']) !== false)  ob_start("ob_gzhandler");

// 调试处理
if (v('debug') == date('mdY')) define('DEBUG', true);


header('P3P:CP="NOI ADM DEV PSAi COM NAV OUR OTR STP IND DEM"');
header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PATCH, PUT");
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	header("Access-Control-Allow-Headers: content-type,x-http-method-override, token");
	exit();
}

// 退出处理
# register_shutdown_function('OUT::end');

// 错误处理
set_error_handler('onUnhandledError');
set_exception_handler('onUnhandledException');

// 预先处理
$file = p(AROOT, "action", 'InitInstance.php');
if (file_exists($file)) require_once($file);

//完整请求串 /dasd/admin?a=c&d=23...
$uri = strtolower($_SERVER['REQUEST_URI']);
//指令 /dasd/admin //参数字符串 ?a=c&d=234
$i = strpos($uri, '?');
if ($i) {
	$actions = strtolower(substr($uri,0,$i));
} else {
	$actions = strtolower($uri);
}
$actions = explode('/', $actions);
foreach ($actions as $k => $v) {
	if (empty($v)) unset ($actions[$k]);
	else $actions[$k] = preg_replace(array('/\.html$/i', '/\.htm$/i', '/\.php$/i'), '', $v); //静态化，忽略的文件扩展名。
}
if (defined('DEFAULT_ACTION') && empty($actions)){
	$actions = array(constant('DEFAULT_ACTION'));
}

//解开参数字段方便使用。 由于只能在当前范围生效，因此只能放在这个全局未知，且只能被全局的Action调用。
//在函数内部，类内部都是无法直接使用的。
// extract($_REQUEST, EXTR_PREFIX_ALL, "");

/*
 default_action 的意义是区分　/a/b/c 和　/a/b.
 /a/b/c 将导致　/a.action.php => /a/b.action.php => /a/b-c.action.php
 而这将覆盖　/a, 　/a/b 的操作。
 加上　default_action　后，　/a 将变成　/a/home.action.php  /a/b 将变成　/a/b-home.action.php。
 这样子　/a, /a/b, /a/b/c 都可以单独定义，并且不影响　/a/b/c 从　/a, /a/b　路过。

 但是这个意义不是很大，action设计上完全可以避免掉　同时有　/a/b 和　/a/b/c的ａｃｔｉｏｎ。
 */
/*  由于出错，且真的意义不大，删除掉default_action的处理。
 */

// 预先处理
$file = p(AROOT, "action", 'beforeaction.php');
if (file_exists($file)) require_once($file);

// 循环处理 action.
$actfile = '';

/*
   分隔符区分层次，可以多重单独定义。

   default '/--' means:   a/b/c/d => action/a/b-c-d.action.php
   eg: '//-.-' means: a/b/c/d/e/f => action/a/b/c-d.e-f.action.php, this will execute though:
        action/a.action.php
        action/a/b.action.php
        action/a/b/c.action.php
        action/a/a/c-d.action.php
        action/a/b/c-d.e.action.php
        action/a/b/c-d.e-f.action.php
        action/a/b/c-d.e-f-METHOD.action.php
   eg: '-/-.-' means: a/b/c/d/e/f => action/a-b/c-d.e-f.action.php

 */
defined('SEP') || define( 'SEP', '/--' );
$seperator = str_split(constant('SEP'));
$lastsep = array_pop($seperator);
$seperator = array_pad($seperator, 10, $lastsep); // 补足到１０层，应该足够用了。
array_unshift($seperator, '');  // 首位为空, 为了拼接方便。

// 检查下一节是否是ID.支持多级别ｉｄ检测。如：
//  /user/11/photo/3

defined('IDEXP') || define('IDEXP', '/^\d+$/');
OUT::$_res['ack'] = implode(':', $actions);

while ($actions){
    $action = @array_shift($actions);
    if(preg_match(IDEXP, reset($actions))){
        $id= @array_shift($actions);
    } else $id = null;

	$actfile .=  array_shift($seperator) . $action;
	$file = p(AROOT, "action", $actfile . '.action.php');
	if (!file_exists($file))  $file = p(CROOT, "action", $actfile . '.action.php');
	if (file_exists($file)) require_once($file);
	else {
		throw new REClientNotExist("$action not found!.");
	}
}

// HTTP 动词，包括：GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,TRACE,CONNECT
$http_method = strtolower($_SERVER['REQUEST_METHOD']);
// 动词处理
$file = p(AROOT, "action", $actfile . array_shift($seperator) . $http_method . '.action.php');
if (!file_exists($file))
  $file = p(CROOT, "action", $actfile . array_shift($seperator) . $http_method . '.action.php');
if (file_exists($file)) require_once($file);

// 后置处理
$file = p(AROOT, "action", 'afteraction.php');
if (file_exists($file)) require_once($file);


/*  对于API来说，正常情况下已经有结果输出了。不需要执行到这里，如果执行到这里，说明流程出错了，应该抛出异常。 */
throw new REServerNotImplement("unkown request. 无法理解的请求！");
