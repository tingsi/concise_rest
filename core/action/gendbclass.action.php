<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

# 根据数据库生成访问层基础类。只用于开发阶段。

IN::mayHave('dbname');
IN::mayHave('prefix');
IN::mustHave("authorization");

$authstring = defined('authorization') ? constant('authorization') : date("dmY");

if ($authstring != $_authorization)
	throw new REClientNotAllow("认证失败");


$dbname = $_dbname ? $_dbname : $GLOBALS['config']['db']['db_name'];

$prefix = "t{$_prefix}";
$mprefix = "m{$_prefix}";
$date = new DateTime();

$db = ODB::withdb($dbname);

// 先查出表的元数据，和字段的元数据。
$sql = "
			select table_name,table_comment from information_schema.tables
			where table_schema='{$dbname}'
			order by table_name asc
			";
$tables = $db->getList($sql);
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
			C.COLUMN_KEY AS 'column_key',
			C.COLUMN_COMMENT AS 'column_comment'
				FROM
				information_schema.COLUMNS C
				INNER JOIN information_schema.TABLES T ON C.TABLE_SCHEMA = T.TABLE_SCHEMA
				AND C.TABLE_NAME = T.TABLE_NAME
				WHERE
				T.TABLE_SCHEMA = '{$dbname}'
				";
$columns = $db->getList($sql);
// +------------+--------+-------------+----------------------------------------+------------+------------------------------------------------------+
// | table_name | engine | column_name | column_type                            | column_key | column_comment                                       |
// +------------+--------+-------------+----------------------------------------+------------+------------------------------------------------------+
// | account    | InnoDB | id          | char(32)                               | PRI        | sortable uuid                                        |
// | account    | InnoDB | email       | tinytext                               | UNI        |                                                      |
// | account    | InnoDB | salt        | char(16)                               |            |                                                      |
// | account    | InnoDB | nick        | varchar(16)                            |            | 5-16位                                               |
// | account    | InnoDB | level       | int(11)                                |            | 级别，默认0. N为不同的高级用户权限。                 |
// | account    | InnoDB | emo         | tinytext                               |            | 备注                                                 |
// | account    | InnoDB | code        | char(5)                                |            | 登录验证码                                           |
// | account    | InnoDB | ct          | datetime                               |            |                                                      |
// | account    | InnoDB | ut          | datetime                               |            |                                                      |
// | member     | InnoDB | id          | bigint(20)                             | PRI        | 无用字段                                             |
// | member     | InnoDB | mtype       | enum('projm','userp','followu','orgm') | MUL        | 成员关系类型                                         |
// | member     | InnoDB | mid         | char(32)                               | MUL        | 主ID                                                 |
// | member     | InnoDB | sid         | char(32)                               |            | 从ID                                                 |
// | member     | InnoDB | stype       | int(11)                                |            | 子类型。如项目管理员，项目评审员                     |
// +------------+--------+-------------+----------------------------------------+------------+------------------------------------------------------+


foreach ($tables as $table) {
	$table_name = $table['table_name'];
	//如果table_name 以下划线开头，表明是系统表，此处忽略掉
	if (0 == strpos($table_name, '_'))  continue;

	$table_comment = $table['table_comment'];
	// 原始字段定义类
	$mfilename = p(AROOT, 'lib', 'class', strtolower("{$prefix}{$table_name}.class.php"));
	$classname = ucfirst($prefix) . ucfirst(strtolower($table_name));

	// 用户修订模型类
	$m2filename = p(AROOT, 'lib', 'class', strtolower("{$mprefix}{$table_name}.class.php"));
	$m2classname = ucfirst($mprefix) . ucfirst(strtolower($table_name));


	$pk = '';
	$fieldinit = '';
	$line = <<<EOT
<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

/**
 * 此类由whlrest自动生成，请勿编辑。如需新增函数功能，请在 {$mfilename} 中修改。
 * 数据库表原始表字段类。
 * 数据库表: {$dbname} . {$table_name}
 * 注释说明: {$table_comment}
 * 生成日期： {$date}
 * @author whlrest
 */
class {$classname} extends RawTable {

EOT;

	foreach ($columns as $column) {
		if ($column['table_name'] != $table_name)
			continue;
		$column_name = $column['column_name'];
		$column_type = (0 == substr_compare($column['column_type'], 'int', 0, 3)) ? 'IntField' : 'StringField';

		$ck = empty($column['column_key']) ? '' : ", Key： {$column['column_key']}";
		$fielddef = <<<EOT
	/**
	 * @var $column_type
	 * 字段： $column_name
	 * 类型： {$column['column_type']}{$ck}
	 * 注释： {$column['column_comment']}
	 */
	public $column_type \${$column_name};

EOT;
		$fieldinit .= <<<EOT
		\$this->{$column_name} = new {$column_type}(\$this, "{$column_name}");

EOT;
		if (empty($pk) && ($column['column_key'] == 'PRI'))
			$pk = $column_name;

		$line .= $fielddef;
	}

	$line .= "	const TABLENAME = \"{$table_name}\";\n";

	$cons = <<<EOT
	/**
	 * 构造器
	 * @param mixed \$database
	 */
	public function __construct(\$database = "$dbname")
	{
		parent::__construct(self::TABLENAME, \$database);

EOT;
	$line .= $cons;
	$line .= $fieldinit;
	$line .= "\n}\n";

	if ($pk) {

		$line .= <<<EOT
	/**
	 * 列出所有项
	 * @return array
	 */
	public function list{$table_name}s():array
	{
		return \$this->where(\$this->{$pk})->select();
	}
	/**
	 * 根据ID获取一个项
	 * @param mixed \$id
	 * @return ConfM
	 */
	public function get{$table_name}(\$id): ConfM
	{
		\$this->key->set(\$id);
		return \$this->where(\$this->{$pk}, \$this->key)->selectOne();
	}
}

EOT;

		## 写入文件
		$f = fopen($mfilename, 'w');
		fwrite($f, $line);
		fclose($f);

	}

	// 更新模型类
	// $m2filename = p(AROOT, 'lib', 'class', strtolower("{$mprefix}{$table_name}.class.php"));
	// $m2classname = ucfirst($mprefix) . ucfirst(strtolower($table_name)); 
$mclass = <<<EOT
<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

/**
 * 此类由whlrest自动生成，用于扩展 {$mfilename}的功能。
 * 数据库表原的模型类。
 * 数据库表： {$dbname} . {$table_name}
 * 注释说明： {$table_comment}
 * 生成日期： {$date}
 * @author whlrest
 */
class {$m2classname} extends {$classname} {
	/**
	 * 构造器
	 */
	public function __construct()
	{
		parent::__construct();
	}
}

EOT;
	if (!file_exists($m2filename))
	{
		$f = fopen($m2filename, 'w');
		fwrite($f, $mclass);
		fclose($f);
	}
}
OUT::done($tables);
