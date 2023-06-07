<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb
#设置保存文件名称，是否要求固定名称，若未设置，则按时间产生一个文件名：201503122627321.ext
?>

<html><head><title>upload files</title></head><body>

<h1><center>upload a files</cneter></h1><hr>

<form method="POST" enctype="multipart/form-data">
<input type="submit" value="send"/>
<input type="hidden" name="fixname" value="test.demo" />
<input type="file" name="upfile" id="upfile" />
</form>
</body>

</html>
