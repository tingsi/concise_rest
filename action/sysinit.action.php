<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 

# check database ...


# prepare database;

$err = array();
$ok  = array();
$ign = array();

DB::setDB();

$sql = "create table if not exists _sqls (`id` int not null auto_increment primary key, `fsql` tinytext, `md5` tinytext, dt timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
DB::runSql($sql);
$rows = DB::getList("select * from _sqls");

$path = p(AROOT, "sqls");
$files = array_diff(scandir($path), array('..', '.'));
sort($files);

foreach($files as $file){
    $fpath = p($path, $file);
    $sql = file_get_contents($fpath);
    $md5 = md5($sql);

    $_sql = "insert into _sqls (fsql, md5) values ('$file', '$md5')";

    $exist = DB::getLine("select * from _sqls where fsql = '$file' or md5 = '$md5'");
    if (empty($exist)){
        if (DB::runBatchSql($sql)) {
            DB::runSql($_sql);
            $ok []= "<li>successful: $file</li>";
        }
        else $err []= "<li>error : $file</li>";
    }
    else $ign []= "<li>ignored : $file</li>";
}

$ok = join($ok); $fail = join($err); $ignore=join($ign);


$html = <<<"HTML"
<html>
<head><title>system init</title></head>
<body>
<center>成功的结果</center><hr>
$ok
<center>失败的结果</center><hr>
$fail
<center>忽略的结果</center><hr>
$ignore

</body></html>


HTML;

echo $html;
exit;