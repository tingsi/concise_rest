<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

// prepare tables if not exists
$sql = "create table if not exists _cc (`id` int not null auto_increment primary key, 
        `device` tinytext null default null comment '设备ID(此时尚未登录，与用户无关), 如果为null则为公共配置，不为null则为个人配置。',
        `key` tinytext not null comment 'key',
        `val` tinytext null comment 'value, base64编码存储，以便于存放更广泛的东东。',
        `ext` tinytext null comment 'value的明文摘要，给人看的。无其他意义',
        `ut` datetime default current_timestamp on update current_timestamp)ENGINE=InnoDB DEFAULT CHARSET=utf8";
DB::runSql($sql);


IN::mustHave('device');

IN::mayHave('key');
IN::mayHave('value');
$data = array();

// get all config.
if (empty($_key) && empty($_value)){
    $cfgs = DB::getList("select `key`, `val` from _cc where `device` is null");
    if($cfgs) foreach ($cfgs as $line) $data[$line['key']] = $line['val'];
    
    if ($_device){
        $cfgsc = DB::getList("select `key`, `val` from _cc where `device` ='{$_device}'");
        if($cfgsc) foreach ($cfgsc as $line) $data[$line['key']] = $line['val'];
    }
    OUT::done($data);
}

// get one config?
if (($_key) && empty($_value)){
    $cfgs = DB::getList("select `key`, `val` from _cc where `device` is null and `key`='$_key'");
    if($cfgs) foreach ($cfgs as $line) $data[$line['key']] = $line['val'];
    
    if ($_device){
        $cfgsc = DB::getList("select `key`, `val` from _cc where `device` ='{$_device}' and `key`='$_key'");
        if($cfgsc) foreach ($cfgsc as $line) $data[$line['key']] = $line['val'];
    }
    OUT::done($data);
}

// save a config
if (($_key) && ($_value)){
    $ext =  preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), $_value);
    $_value = base64_encode($_value);
    if ($_device == 'wanghongliang'){ //god mode.
        $exist = DB::getValue("select count(*) from _cc where `key` = '$_key' and `device` is null");
        if ($exist) DB::runSql("update _cc set `val` = '$_value', `ext`='$ext'  where `key` = '$_key' and `device` is null");
        else DB::runSql("insert into  _cc set `val` = '$_value', `key` = '$_key', `ext`='$ext'");
    }else{
        $exist = DB::getValue("select count(*) from _cc where `key` = '$_key' and `device`='$_device'");
        if ($exist) DB::runSql("update _cc set `val` = '$_value', `ext`='$ext'  where `key` = '$_key' and `device`='$_device'");
        else DB::runSql("insert into  _cc set `val` = '$_value', `key` = '$_key', `ext`='$ext', `device`='$_device'");        
    }
    $sql = "select * from _cc where `key`='$_key' and `device` " . (($_device == 'wanghongliang') ? " is null " : "='$_device'");
    $line = DB::getLine($sql);
    OUT::done($line);
}
