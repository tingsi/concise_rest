<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

IN::mayHave('device');
IN::mayHave('type');
IN::mayHave('level');
IN::mustHave('txt');

$sql = "insert into _logs (`device`, `type`, `level`, `txt`) values (:device, :type, :level, :txt)";
$param = array(':device'=>"'$_device'", ':type'=>"'$_type", ':level'=>$_level, ':txt'=>"$_txt");

DB::runSql($sql, $param);

OUT::done(Array('id'=>DB::lastID()));
