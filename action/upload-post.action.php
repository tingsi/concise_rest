<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb
#设置保存文件名称，是否要求固定名称，若未设置，则按时间产生一个文件名：201503122627321.ext
IN::mayHave("fixname", "/\w+/");

$cameFrom = "";
if (array_key_exists("HTTP_REFERER", $_SERVER)){
	$cameFrom = $_SERVER["HTTP_REFERER"];
}

if (empty($_FILES))  throw new REClientNotGood("请选择需要上传的文件。");

$uploadFile = reset($_FILES);

date_default_timezone_set('Asia/Shanghai');
$filename = $_fixname;
//$filename || $filename = date('YmdHis') . substr(uniqid(), 6, 6) . $ext;
$filename || $filename = date('Y/m/dHis') . $uploadFile['name'];

#设置目录+文件完整路径  
$dst_path = p(AROOT, 'uploads', $filename);

error_log("SOURCE:" . print_r($uploadFile, true));
error_log("DEST:" . $dst_path);

#创建文件夹  
if (!file_exists(dirname($dst_path)))  mkdir(dirname($dst_path), 0777, true);

if (move_uploaded_file($uploadFile['tmp_name'], $dst_path)) {
	$url = Constant('SITE_URL') . "uploads/$filename";
    if ($cameFrom) header("Location: $cameFrom");
    OUT::done(array('file' => $url));
} else
    throw new REServerFail("文件转移失败");
