<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

const  DEFAULT_LIMIT = 1000;

interface IChangable
{
    function onChange(RawField &$bf);
}

abstract class RawField
{
    protected ?string $fieldval;
    protected string $fieldname;
    private IChangable $ic;
    public function __construct(IChangable $ic, string $fieldname)
    {
        $this->ic = $ic;
        $this->fieldname = $fieldname;
        $this->fieldval = null; // 注，默认null，以null为判断是否有值依据，以支持 "0", "false", ""等。
    }
    public function __toString()
    {
        return "{$this->fieldname}={$this->fieldval}";
    }
#@deprecated: 含义不明确不好理解，还是拆成 get,set算了。
    public function value($val = null)
    {
        if (is_null($val))
            return $this->fieldval;
        $this->fieldval = $val;
        $this->ic->onChange($this);
    }
    public function set($val){
        $this->fieldval = $val;
        $this->ic->onChange($this);
    }
    public function val():?string {
        return $this->fieldval;
    }

    public function fromArray($arr): void
    {
        if (array_key_exists($this->fieldname, $arr)) {
            $this->fieldval = $arr[$this->fieldname];
            $this->ic->onChange($this);
        }
    }
    abstract public function where(): string;
    abstract public function updateval(): string;
    public function updatekey(): string
    {
        return "`$this->fieldname`";
    }
    public function key(): string
    {
        return $this->fieldname;
    }
    // abstract public function insertval(): string;

}

class StringField extends RawField
{
    public function where(): string
    {
        return "`{$this->fieldname}` = \"{$this->fieldval}\"";
    }
    public function updateval(): string
    {
        return "\"{$this->fieldval}\"";
    }
}

class IntField extends RawField
{
    public function where(): string
    {
        return "`{$this->fieldname}` = {$this->fieldval}";
    }
    public function updateval(): string
    {
        return "{$this->fieldval}";
    }
}

/**
 * Summary of RawTable
 */
abstract class RawTable implements IChangable
{
    private ?string $database;
    protected $tablename;
    private $wheresql = "";
    private $limit = DEFAULT_LIMIT;

    // 需要被子类实现的虚函数
    abstract protected function getPrimaryKey(): ?RawField;

    private $changedFields = array();
    public function onChange(RawField &$bf)
    {
        if (!in_array($bf, $this->changedFields))
            $this->changedFields[] = $bf;
    }
    /**
     * Summary of __construct
     * @param string $tablename 表名称
     * @param ?string $database 可选数据库
     */
    public function __construct($tablename, $database = null)
    {
        $this->tablename = $tablename;
        $this->database = $database;
    }
    public function reset()
    {
        $this->wheresql = "";
        $this->limit = DEFAULT_LIMIT;
        $this->changedFields = [];
    }
    public function clear()
    {
        foreach ($this as $v) {
            if ($v instanceof RawField && !is_null($v->value()))
                $v->set("");
        }
    }
    /*
        @param array  $fs
        @return class BaseTable
    */
    public function where(...$fs)
    {
        $wa = array_map(fn(RawField $f): string => $f->where(), $fs);
        $this->wheresql = " where " . join(' and ', $wa);
        return $this;
    }

    # @return a clone of $this for a row data;
    public function selectOne(): array
    {
        $sql = "select * from " . $this->tablename;
        if ($this->wheresql)
            $sql .= $this->wheresql;
        if ($this->limit)
            $sql .= " limit {$this->limit}";
        $this->reset();
        if ($this->database)
            $data = ODB::withdb($this->database)->getLine($sql);
        else
            $data = DB::getLine($sql);

        return $data;
    }

    # @return array() of list rows;
    # 暂时需要自己解析.此处避免对大量行数做解析以减小不必要的开支。
    public function select() :array
    {
        $sql = "select * from " . $this->tablename;
        if ($this->wheresql)
            $sql .= $this->wheresql;
        if ($this->limit)
            $sql .= " limit {$this->limit}";
        $this->reset();
        if ($this->database)
            return ODB::withdb($this->database)->getList($sql);
        else
            return DB::getList($sql);
    }
    public function limit($limit)
    {
        $this->limit = intval($limit);
        return $this;
    }
    # @return true
    # 注，此处总是返回true。如果出错了，直接通过异常抛出。
    public function insert()
    {
        // 插入时，忽略变更检查，总是插入全部字段。
        $changed = array();
        foreach ($this as $v) {
            if ($v instanceof RawField && !is_null($v->value()))
                $changed []= $v;
        }
        
        $ks = array_map(fn(RawField $cf): string => $cf->updatekey(), $changed);
        $ksr = array_map(fn(RawField $cf): string => ':' . $cf->key(), $changed);
        $vs = array_map(fn(RawField $cf) => $cf->value(), $changed);
        $sql = "insert ignore into " . $this->tablename;
        $sql .= "(" . join(',', $ks) . ") values (" . join(',', $ksr) . ")";
        $param = array_combine($ksr, $vs);
        $this->reset();
        if ($this->database)
            return ODB::withdb($this->database)->runSql($sql, $param);
        else
            return DB::runSql($sql, $param);
    }
    public function getlastid(): int
    {
        if ($this->database)
            return ODB::withdb($this->database)->lastID();
        else
            return DB::lastID();
    }
    # @return true|false
    public function update()
    {
        if (!$this->wheresql)
            return false;
        $ua = array_map(fn(RawField $cf): string => $cf->where(), $this->changedFields);
        $sql = "update " . $this->tablename;
        $sql .= " set " . join(',', $ua);
        if ($this->wheresql)
            $sql .= $this->wheresql;
        $this->reset();

        if ($this->database)
            return ODB::withdb($this->database)->runSql($sql);
        else
            return DB::runSql($sql);
    }
    # @return true|false
    public function delete()
    {
        if (!$this->wheresql)
            return false;
        $sql = "delete from " . $this->tablename . $this->wheresql;
        $this->reset();
        if ($this->database)
            return ODB::withdb($this->database)->runSql($sql);
        else
            return DB::runSql($sql);
    }
    public function toArray()
    {
        $res = array();
        foreach ($this as $v) {
            if ($v instanceof RawField && !is_null($v->value()))
                $res[$v->key()] = $v->value();
        }
        return $res;
    }
    # 注，无论成功与否，都会清空原值。
    public function fromArray($data)
    {
        $this->clear();
        foreach ($this as $v) {
            if ($v instanceof RawField) {
                $v->fromArray($data);
                $this->onChange($v);
            }
        }
    }
}
