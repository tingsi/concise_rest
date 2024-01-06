<?php

//语言检测
//NOTE: apt-get install php-gettext php-intl
function setLocalLang()
{
    $locale = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'zh_CN';
    putenv("LANG=" . $locale);
    setlocale(LC_ALL, $locale);
    bindtextdomain('language', AROOT . '/locale');
    bind_textdomain_codeset('language', 'UTF-8');
    textdomain('language');
}


// 解析转义字符，如\r\n。
function transcribe($aList, $aIsTopLevel = true)
{
    $gpcList = array();
    $isMagic = false; // always false after php 7.4; get_magic_quotes_gpc();

    foreach ($aList as $key => $value) {
        if (is_array($value)) {
            $decodedKey = ($isMagic && !$aIsTopLevel) ? stripslashes($key) : $key;
            $decodedValue = transcribe($value, false);
        } else {
            $decodedKey = stripslashes($key);
            $decodedValue = ($isMagic) ? stripslashes($value) : $value;
        }
        $gpcList[$decodedKey] = $decodedValue;
    }
    return $gpcList;
}

$_GET = transcribe($_GET);
$_POST = transcribe($_POST);

//默认情况下包含了 $_GET，$_POST 和 $_COOKIE 的数组。
$_REQUEST = transcribe($_REQUEST);


function v($str)
{
    return isset($_REQUEST[$str]) ? $_REQUEST[$str] : false;
}

function z($str)
{
    return strip_tags($str);
}

function c($str)
{
    return isset($GLOBALS['config'][$str]) ? $GLOBALS['config'][$str] : false;
}

function g($str)
{
    return isset($GLOBALS[$str]) ? $GLOBALS[$str] : false;
}


// directory name generate.
// 构造目录结构，如 p('aa','/bb/','dd') => 'aa/bb/dd';
function p()
{
    $paths = func_get_args();
    array_walk($paths, function (&$v, $k) {
        if ($k == 0)
            $v = rtrim($v, " \t\n\r\0\x0B\\\/"); //  保留第一个的根。
        else
            $v = trim($v, " \t\n\r\0\x0B\\\/");
    });
    return implode(DS, $paths);
}

function is_ajax_request()
{
    $headers = apache_request_headers();
    // this is case_senstive bug. IE send lowcase key;safri send UCase key, Firefox send orignal case.
    $headers = array_change_key_case($headers);
    return isset($headers['x-requested-with']) && ($headers['x-requested-with'] == 'XMLHttpRequest');
}

if (!function_exists('apache_request_headers')) {
    function apache_request_headers()
    {
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $out[$key] = $value;
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}

function is_mobile_request()
{
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';

    $mobile_browser = '0';

    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        $mobile_browser++;

    if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;

    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;

    if (isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;

    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
    $mobile_agents = array(
        'w3c ',
        'acs-',
        'alav',
        'alca',
        'amoi',
        'audi',
        'avan',
        'benq',
        'bird',
        'blac',
        'blaz',
        'brew',
        'cell',
        'cldc',
        'cmd-',
        'dang',
        'doco',
        'eric',
        'hipt',
        'inno',
        'ipaq',
        'java',
        'jigs',
        'kddi',
        'keji',
        'leno',
        'lg-c',
        'lg-d',
        'lg-g',
        'lge-',
        'maui',
        'maxo',
        'midp',
        'mits',
        'mmef',
        'mobi',
        'mot-',
        'moto',
        'mwbp',
        'nec-',
        'newt',
        'noki',
        'oper',
        'palm',
        'pana',
        'pant',
        'phil',
        'play',
        'port',
        'prox',
        'qwap',
        'sage',
        'sams',
        'sany',
        'sch-',
        'sec-',
        'send',
        'seri',
        'sgh-',
        'shar',
        'sie-',
        'siem',
        'smal',
        'smar',
        'sony',
        'sph-',
        'symb',
        't-mo',
        'teli',
        'tim-',
        'tosh',
        'tsm-',
        'upg1',
        'upsi',
        'vk-v',
        'voda',
        'wap-',
        'wapa',
        'wapi',
        'wapp',
        'wapr',
        'webc',
        'winw',
        'winw',
        'xda',
        'xda-'
    );

    if (in_array($mobile_ua, $mobile_agents))
        $mobile_browser++;

    if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        $mobile_browser++;

    // Pre-final check to reset everything if the user is on Windows
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
        $mobile_browser = 0;

    // But WP7 is also Windows, with a slightly different characteristic
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
        $mobile_browser++;

    if ($mobile_browser > 0)
        return true;
    else
        return false;
}
