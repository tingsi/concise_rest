<?php

### REST Exception of Client Fail

/*中间问题*/
/*资源没变化，使用缓存即可*/
class REResourceNotChange extends Exception {
	function __construct($msg) {parent::__construct($msg, 304); }
}


/*客户端请求错误*/
class _ClientFail extends Exception {
    public $extra = "";
    function __construct($msg, $code) {parent::__construct($msg, $code); }
    function setExtra($extra){$this->extra = $extra; return $this;}
}

class REClientFail extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 400); }
}
/*客户端未登录: 注，特殊用途，用于转向登录，或者踢出用户。*/
class REClientNotLogin extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 401); }
}
/*客户端未授权*/
class REClientNotAllow extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 403); }
}
/*客户端请求目标不存在*/
class REClientNotExist extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 404); }
}
/*客户端操作不允许*/
class REClientMethodNotAllow extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 405); }
}
/*客户端参数不被接受，不合法*/
class REClientNotGood extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 406); }
}
/*客户端超时, 非服务端超时，如上传太久等*/
class REClientTimeout extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 408); }
}
/*客户端请求冲突*/
class REClientConflict extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 409); }
}
/*客户端前置条件失败*/
class REClientPreconditionFail extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 412); }
}
/*客户端需要前置条件*/
class REClientPreconditionNeed extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 428); }
}
/*客户端未符合要求，需要的字段没提供等*/
class REClientExpectFail extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 417); }
}
/*客户端依赖失败*/
class REClientDependencyFail extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 424); }
}
/*客户端需要升级*/
class REClientNeedUpgrade extends _ClientFail {
	function __construct($msg) {parent::__construct($msg, 426); }
}



### REST Exception of  Server Fail
/*服务器一般错误*/
class REServerFail extends Exception {
    function __construct($msg) { parent::__construct("ServerFail:" . $msg, 500); }
}
/*功能还未实现*/
class REServerNotImplement extends Exception {
    function __construct($msg){ parent::__construct($msg, 501); }
}

/*服务不可用：暂时用于数据库错误处理*/
class REServerUnavailable extends Exception {
    function __construct($msg){ parent::__construct($msg, 503); }
}


function onUnhandledError($errno, $errstr, $errfile, $errline) {
    $errstr = addslashes($errstr);
    $error = "[$errno] $errstr ".$errfile." 第 $errline 行.";

    if (!(error_reporting() & $errno)) {
        defined('DEBUG') && OUT::warning($error);
        return false;
    }
    OUT::onFail('500', $error);
}

function onUnhandledException($e){
    $code = $e->getCode();
    $msg =  $e->getMessage();
    $ack = "";
    if ($e instanceof _ClientFail)  $ack = $e->extra;
    if (empty($code)) $code = '500';
    $err = array('file'=>$e->getFile(), 'line'=>$e->getLine(), 'code'=> $e->getCode());
    $stack []= $err;
    $stack []= $e->getTrace();
    OUT::onFail($code, $msg, $ack, $stack);
}


