<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

# 动态生成数据库字典。
# TODO: 后期可考虑缓存处理

IN::mayHave('dbname');
IN::mustHave("authorization");

$authstring = defined('authorization') ? constant('authorization') : date("dmY");

if ($authstring != $_authorization)
	throw new REClientNotAllow("认证失败");

/**
	   * 自定义注释，可以完美覆盖表中的注释。
	   *
	   这里的account 表，id字段，只是示例，可以替换成你的表名和字段名。
	   * @return string[][]
	   */
function my_comment_list()
{
	return [
		[
			'table_name' => 'account',
			'column_name' => 'id',
			'column_comment' => '自增主键'
		],
		[
			'table_name' => 'account',
			'column_name' => 'cid',
			'column_comment' => '栏目id'
		],

	];
}

function my_comment($arr)
{
	$my_table = my_comment_list();
	foreach ($arr as $k => &$v) {
		foreach ($my_table as $my) {
			if (
				$v['table_name'] == $my['table_name'] &&
				$v['column_name'] == $my['column_name']
			) {
				$v['column_comment'] = $my['column_comment'];
			}
		}
	}
	return $arr;
}


$dbname = empty($_dbname) ? $GLOBALS['config']['db']['db_name'] : $_dbname;

$db = ODB::withdb($dbname);

// 先查出表的元数据，和字段的元数据。
$sql = "
			select table_name,table_comment from information_schema.tables
			where table_schema='{$dbname}'
			order by table_name asc
			";
$table_arr = $db->getList($sql);
// +------------+-----------------+
// | table_name | table_comment   |
// +------------+-----------------+
// | account    |                 |
// | member     | 成员关系表      |
// +------------+-----------------+

$sql = "
			SELECT
			T.TABLE_NAME AS 'table_name',
			T. ENGINE AS 'engine',
			C.COLUMN_NAME AS 'column_name',
			C.COLUMN_TYPE AS 'column_type',
			C.COLUMN_COMMENT AS 'column_comment'
				FROM
				information_schema.COLUMNS C
				INNER JOIN information_schema.TABLES T ON C.TABLE_SCHEMA = T.TABLE_SCHEMA
				AND C.TABLE_NAME = T.TABLE_NAME
				WHERE
				T.TABLE_SCHEMA = '{$dbname}'
				";
$column_arr = $db->getList($sql);
// +------------+--------+-------------+----------------------------------------+------------------------------------------------------+
// | table_name | engine | column_name | column_type                            | column_comment                                       |
// +------------+--------+-------------+----------------------------------------+------------------------------------------------------+
// | account    | InnoDB | id          | char(32)                               | sortable uuid                                        |
// | account    | InnoDB | email       | tinytext                               |                                                      |
// | account    | InnoDB | salt        | char(16)                               |                                                      |
// | account    | InnoDB | nick        | varchar(16)                            | 5-16位                                               |
// | account    | InnoDB | level       | int(11)                                | 级别，默认0. N为不同的高级用户权限。                 |
// | account    | InnoDB | emo         | tinytext                               | 备注                                                 |
// | account    | InnoDB | code        | char(5)                                | 登录验证码                                           |
// | account    | InnoDB | ct          | datetime                               |                                                      |
// | account    | InnoDB | ut          | datetime                               |                                                      |
// | member     | InnoDB | id          | bigint(20)                             | 无用字段                                             |
// | member     | InnoDB | mtype       | enum('projm','userp','followu','orgm') | 成员关系类型                                         |
// | member     | InnoDB | mid         | char(32)                               | 主ID                                                 |
// | member     | InnoDB | sid         | char(32)                               | 从ID                                                 |
// | member     | InnoDB | stype       | int(11)                                | 子类型。如项目管理员，项目评审员                     |
// +------------+--------+-------------+----------------------------------------+------------------------------------------------------+




$column_arr = my_comment($column_arr);

// 构造表的索引
$table_list_str = '';
foreach ($table_arr as $v) {
	$table_list_str .= '<li><a href="#' . $v['table_name'] . '">' .
		$v['table_name'] . "（{$v['table_comment']}）" . '</a></li>' . "\n";
}

// 构造数据字典的内容
$table_str = '';
foreach ($table_arr as $v) {
	$table_name = $v['table_name'];
	$table_comment = $v['table_comment'];
	$table_str .= <<<html
				<a href="#header">回到首页</a>


				<p class='table_jiange'><a name='{$table_name}'>&nbsp</a>
					<table width="100%" border="0" cellspacing="0" cellpadding="3">
					<tr>
					<td  width="70%"  class="headtext"
					align="left" valign="top"> {$table_name}（{$table_comment}）</td>
					<td  width="30%" class="headtext"
					align="right"
					> </td>

					<tr>
					</table>

					<table width="100%" cellspacing="0" cellapdding="2" class="table2" >
					<tr>
					<td align="center" width='15%' valign="top" class="fieldcolumn">字段</td>
					<td align="center" width='15%' valign="top" class="fieldcolumn">类型</td>
					<td align="center" width='70%'  valign="top" class="fieldcolumn">注释</td>
					</tr>
html;
	foreach ($column_arr as $vv) {
		if ($vv['table_name'] == $table_name) {
			$table_str .= <<<html
						<tr>
						<td align="left"  width='15%' >
						<td align="left"  width='15%' ><p class="normal">{$vv['column_type']}</td>
						<td align="left"  width='70%' >{$vv['column_comment']}</td>
						</tr>
html;
		}
	}
	$table_str .= "</table>\n\n";
}

// 开始构造整个数据字典的html页面
$html = <<<html
			<html>
			<head>
			<title>{$dbname}数据字典</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf8">
			<style type="text/css">
			<!--
			.toptext {font-family: verdana; color: #000000; font-size: 20px; font-weight: 600; width:550;  background-color:#999999; }
		.normal {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 16px; font-weight: normal; color: #000000}
		.normal_ul {  font-family: Verdana, Arial, Helvetica, sans-serif; 
			font-size: 12px; font-weight: normal; color: #000000}
		.fieldheader {font-family: verdana; color: #000000; font-size: 16px; font-weight: 600; width:550;  background-color:#c0c0c0; }
		.fieldcolumn {font-family: verdana; color: #000000; font-size: 16px; font-weight: 600; width:550;  background-color:#ffffff; }
		.header {background-color: #ECE9D8;}
		.headtext {font-family: verdana; color: #000000; font-size: 20px; font-weight: 600;    }
		BR.page {page-break-after: always}
		//-->
		</style>

			<style>

			a:link{text-decoration:none;}
a:visited{text-decoration:none;}
a:active{text-decoration:none;}

  body {
padding:20px;
  }

#ul2 {
margin:0;
padding:0;
}
#ul2 li {
display:inline;
float:left;
margin:5 5px;
padding:0px 0px;

width:230px;
      background-color:#Eee;
border:1px #bbb dashed;

}
#ul2 li a{
display:block;
	font-size:14px;
color:#000;

padding:10px 5px;
	font-weight:bolder;
}

#ul2 li:hover {
	background-color:#73B1E0;
}
#ul2 li:hover a {
color:#FFF;
}

#div2 {
clear:both;
margin:20px;
}
.table2 td {
padding:5px 10px;
}
.table2 tr:hover td {
	background-color:#73B1E0;
}
.table2 tr:hover td p{
color:#FFF;
}

.table2 {border-right:1px solid #aaa; border-bottom:1px solid #aaa}
.table2  td{border-left:1px solid #aaa; border-top:1px solid #aaa}

.table2 tr:nth-child(even){background:#F4F4F4;}


.headtext {
padding:10px;
}
p.pa{
color:blue;
}
.table_jiange{
margin:20px;
padding:0;
}

</style>
</head>

<body bgcolor='#ffffff' topmargin="0">
<table width="100%" border="0" cellspacing="0" cellpadding="5">
<tr>
<td class="toptext"><p align="center">{$dbname}数据字典</td>
</tr>
</table>

<a name="header">&nbsp</a>
<ul id='ul2'>
{$table_list_str}
</ul>

<div id="div2"></div>
<br class=page>

{$table_str}

<a href="#header"><p class="normal">回到首页</p></a>
<h1 width="100%">
</body>
</html>   
html;


echo $html;