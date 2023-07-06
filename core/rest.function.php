<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

/** 预定义常规错误码，最大兼容HTTP规范
 *
    100-199:  转移，升级，过期作废，等。
    200-299： 为了兼容HTTP, 暂时略过
    300-399:  重定向，URL跳转。
    400-499： 用户端错误。
    500-599： 服务器段错误。
 *
 */


class IN
{
    // 输入校验, eg:
    // 需要 3到6位字符串： mustHave('name', '/\w{3,6}/');
    // 需要 3到6位数字  ： mustHave('id', '/\d{3,6}/');
    static function mustHave($field, $match = '', $comment = '')
    {
        $headers = self::prepare();
        $val = '';
        if (array_key_exists($field, $headers))
            $val = $headers[$field];
        //if (empty($val)) throw new REClientExpectFail("require field: $field");
        //允许传递空，0，false等值。主要是false，常用到。
        else
            throw new REClientExpectFail("require field: $field");

        if ($match && (!preg_match($match, $val)))
            throw new REClientExpectFail("field format error: $field");

        $GLOBALS["_{$field}"] = $val; // fix undefined variables;
    }

    static function mayHave($field, $match = '', $comment = '')
    {
        $headers = self::prepare();
        $val = '';
        if (array_key_exists($field, $headers))
            $val = $headers[$field];

        $GLOBALS["_{$field}"] = $val; // fix undefined variables;
        if ($val && $match && (!preg_match($match, $val)))
            throw new REClientExpectFail("field format error: $field");
    }
    static function get($key)
    {
        $headers = self::prepare();
        $val = false;
        if (array_key_exists($key, $headers))
            $val = $headers[$key];
        return $val;
    }
    static function prepare()
    {
        static $fields = null;
        if ($fields == null) {
            $fields = array_merge(getallheaders(), $_REQUEST);
            // for 'Content-Type: application/json' or "application/json;charset=UTF-8"
            if (array_key_exists("CONTENT_TYPE", $_SERVER) && strcmp($_SERVER["CONTENT_TYPE"], 'application/json') >= 0) {
                if (in_array(strtolower($_SERVER["REQUEST_METHOD"]) , ['post', 'put', 'patch'])) {
                    $json = file_get_contents('php://input');
                    if ($json) {
                        $obj = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
                        $fields = array_merge($fields, $obj);
                    }
                }
            }
            // $fields = array_map(fn($v) => trim($v), $fields);
            array_walk_recursive($fields, fn(&$v, $k) => $v = trim($v));
            //TODO: 进行输入项统一规范化检查处理。
        }
        return $fields;
    }
}

class OUT
{
    static $_res = array('code' => '200', 'msg' => 'OK', 'ack' => 'ackok', 'data' => array());

    // 出错输出
    static function onFail($code, $msg, $extra = NULL, $stacks = NULL)
    {
        self::$_res['code'] = $code;
        self::$_res['msg'] = $msg;
        $extra && self::$_res['ack'] = $extra;
        defined('DEBUG') && $stacks && self::$_res['stacks'] = $stacks;
        http_response_code($code);
        self::end();
    }
    // 警告输出
    public static function warning($info)
    {
        self::$_res['msg'] .= "$info";
    }
    // 正常输出
    public static function done($data, $ack = '')
    {
        if ($ack)
            self::$_res['ack'] = $ack;
        self::$_res['data'] = $data;
        self::end();
    }
    private static function end()
    {
        defined('DEBUG') && self::$_res['debug'] = array('get' => $_GET, 'post' => $_POST, 'files' => $_FILES);
        array_walk_recursive(self::$_res, function (&$val, $key) {
            $val = urlencode($val);
        });
        $res = json_encode(self::$_res);
        $res = urldecode($res);
        defined('DEBUG') && error_log($res);
        exit($res);
    }
}
