<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb

abstract class Model{
    protected $tablename = "";
    public function __construct($tablename)
    {
        $this->tablename = $tablename;
    }
}
