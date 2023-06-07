<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

IN::mayHave('offset');
IN::mayHave('limit');
IN::mayHave('cleanup');

// prepare tables if not exists
$sql = "create table if not exists _logs (`id` int not null auto_increment primary key, 
        `device` tinytext null default null comment '来源设备，供区分信息来源。',
        `type` tinytext null comment '类别',
        `level` int null default 0 comment '级别，类似警告，错误，调试等。',
        `txt` varchar(4096) null comment '日志记录',
        `ut` datetime default current_timestamp on update current_timestamp)ENGINE=InnoDB DEFAULT CHARSET=utf8";
DB::runSql($sql);

$where = (intval($_cleanup) > 0) ? "where id < $_cleanup" : "where datediff(now(), ut) > 3";
$sql = "delete from _logs $where";
DB::runSql($sql);

if (empty($_limit)) $_limit = 100;

$limit = (empty($_offset)) ? "limit $_limit" : "limit $_offset, $_limit"; 

$sql = "select * from _logs order by id desc $limit";
$data = DB::getList($sql);

echo "<html><head><title>remote logs</title><style>th,td{width:64px;text-align:center;}</style></head><body> <h1 style='text-align:center;'> 日志记录 </h1><hr>";

echo "<table><tr><th>序号</th><th>时间</th><th>类型</th><th>级别</th><th>设备</th><th style='width:800px;text-align:left;'>日志</th></tr>";

foreach($data as $line){
        echo "<tr><td>{$line['id']}</td><td>{$line['ut']}</td><td>{$line['type']}</td><td>{$line['level']}</td><td>{$line['device']}</td><td style='width:800px;text-align:left;'>{$line['txt']}</td></tr>";
}

echo "</table>";
echo "</body></html>";

exit;
