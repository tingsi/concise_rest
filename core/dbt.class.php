<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb


interface IChangable
{
    function onChange(RawField &$bf);
}

abstract class RawField
{
    protected $fieldval;
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
    public function value($val = null)
    {
        if (is_null($val))
            return $this->fieldval;
        $this->fieldval = $val;
        $this->ic->onChange($this);
    }
    public function fromArray($arr):void
    {
        if (array_key_exists($this->fieldname, $arr)) {
            $this->fieldval = $arr[$this->fieldname];
            $this->ic->onChange($this);
        }
    }
    abstract public function where():string;
    abstract public function updateval():string;
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
    public function where():string
    {
        return "`{$this->fieldname}` = \"{$this->fieldval}\"";
    }
    public function updateval():string
    {
        return "\"{$this->fieldval}\"";
    }
}

class IntField extends RawField
{
    public function where():string
    {
        return "`{$this->fieldname}` = {$this->fieldval}";
    }
    public function updateval():string
    {
        return "{$this->fieldval}";
    }
}

/**
 * Summary of RawTable
 */
class RawTable implements IChangable
{
    private ?string $database;
    protected $tablename;
    private $wheresql = "";
    private $changedFields = array();
    public function onChange(RawField &$bf)
    {
        if (! in_array($bf, $this->changedFields))
            $this->changedFields []= $bf;
    }
    /**
     * Summary of __construct
     * @param string $tablename 表名称
     * @param ?string $database 可选数据库
     */
    public function __construct($tablename, $database=null)
    {
        $this->tablename = $tablename;
        $this->database = $database;
    }
    public function reset()
    {
        $this->wheresql = "";
        $this->changedFields = [];
    }
/*
    @param array  $fs
    @return class BaseTable
*/
    public function where(...$fs)
    {
        $wa = array_map(fn (RawField $f): string => $f->where(), $fs);
        $this->wheresql = " where " . join(' and ', $wa);
        return $this;
    }

    # @return $this a row data;
    public function selectOne():RawTable
    {
        $sql = "select * from " . $this->tablename;
        if ($this->wheresql)  $sql .= $this->wheresql;
        $this->reset();
        if ($this->database)
            $data = ODB::withdb($this->database)->getLine($sql);
        else
            $data = DB::getLine($sql);
        foreach ($this as &$k) {
            if ($k instanceof RawField) {
                $k->fromArray($data);
            }
        }
        return $this;
    }

    # @return array() of list rows;
    public function select()
    {
        $sql = "select * from " . $this->tablename;
        if ($this->wheresql)  $sql .= $this->wheresql;
        $this->reset();
        if ($this->database)
            return ODB::withdb($this->database)->getList($sql);
        else
            return DB::getList($sql);
    }
    # @return true|false
    public function insert(RawField ...  $rf)
    {
        $changed = $this->changedFields;
        if ($rf)
            $changed = array_merge($changed, $rf);
        $ks = array_map(fn(RawField &$cf): string => $cf->updatekey(), $changed);
        $vs = array_map(fn(RawField &$cf): string => $cf->updateval(), $changed);
        $sql = "insert into " . $this->tablename;
        $sql .= "(" . join(',', $ks) . ") values (" . join(',', $vs) . ")";
        $this->reset();
        if ($this->database)
            return ODB::withdb($this->database)->runSql($sql);
        else
            return DB::runSql($sql);
    }
    public function getlastid():int
    {
        if ($this->database)
            return ODB::withdb($this->database)->lastID();
        else
            return DB::lastID();
    }
    # @return true|false
    public function update()
    {
        $ua = array_map(fn(RawField &$cf): string => $cf->where(), $this->changedFields);
        $sql = "update " . $this->tablename;
        $sql .= " set " . join(',', $ua);
        if ($this->wheresql)  $sql .= $this->wheresql;
        $this->reset();

        if ($this->database)
            return ODB::withdb($this->database)->runSql($sql);
        else
            return DB::runSql($sql);
    }
    # @return true|false
    public function delete()
    {
        if (!$this->wheresql) return false;
        $sql = "delete from " . $this->tablename . $this->wheresql;
        $this->reset();
        if ($this->database)
            return ODB::withdb($this->database)->runSql($sql);
        else
            return DB::runSql($sql);
    }
    public function toArray(){
        $res = array();
        foreach ($this as $k => $v) {
            if ($v instanceof RawField)
                if ($v->value() != null)
                    $res[$v->key()] = $v->value();
        }
        return $res;
    }
}
