<?php
class QDC_Adapter_TempTable {

    const NO_TABLE=false;
    const NO_RECORD=false;

    public $tableName;
    public $cols;
    public $colsIndeks;
    public $data, $add_data;
    public $db;
    private $limitRow = 1000;
    private $isArrayMulti = false;

    public function __construct($params = '')
    {
        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $this->db = Zend_Registry::get("db");
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function create()
    {
        if (!$this->cols)
            $this->cols = $this->getColumns();

        $this->cols = $this->addColsAdditional($this->cols);

        $this->createTable();
        $this->insertRow();
    }

    public function append()
    {
        if (!$this->checkTable())
        {
            if (!$this->cols)
                $this->cols = $this->getColumns();

            $this->cols = $this->addColsAdditional($this->cols);

            $this->createTable();
        }

        $this->cols = $this->getColumnsFromExisting();
        $this->insertRow();
    }

    private function getColumnsFromExisting()
    {
        $tmp = array();
        $result = $this->db->describeTable($this->tableName);
        foreach($result as $v)
        {
            if ($v['COLUMN_NAME'] == 'id')
                continue;
            $type = $v['DATA_TYPE'];
            if ($v['PRECISION'] != null)
            {
                $p = $v['PRECISION'];
                if ($v['SCALE'] != null)
                {
                    $p .= "," . $v['SCALE'];
                }
                $type = $v['DATA_TYPE'] . "($p)";
            }
            if ($v['LENGTH'] != null)
            {
                $p = $v['LENGTH'];
                $type = $v['DATA_TYPE'] . "($p)";
            }

            $tmp[] = array(
                "column_name" => $v['COLUMN_NAME'],
                "type" => $type
            );
        }

        return $tmp;
    }

    private function getColumns()
    {
        if (!$this->data)
            return false;

        $tmp = $this->data;
        if (is_array($this->data[0]))
        {
            $tmp = $this->data[0];
            $this->isArrayMulti = true;
        }

        $tcols = array_keys($tmp);
        $cols = array();
        foreach($tcols as $v)
        {
            if (is_numeric($tmp[$v]))
            {
                $type = 'INT';
                if ($tmp[$v] > 1000000)
                {
                    $type = "DECIMAL(22,2)";
                }
                $cols[] = array(
                    "column_name" => $v,
                    "type" => $type
                );
            }
            else
            {
                $cols[] = array(
                    "column_name" => $v,
                    "type" => "VARCHAR(255)"
                );
            }
        }

        $cols = $this->addColsAdditional($cols);
        return $cols;
    }

    private function getColumnsSQL()
    {
        if (!$this->cols)
            return '';

        $el = array();
        foreach($this->cols as $v)
        {
            $el[] = "`" . $v['column_name'] . "`";
        }

        return implode(",",$el);
    }

    private function createTable()
    {
        if (!$this->cols)
            return false;

        $sql = "";
        $el = array();
        $el[] = "`id` int(11) NOT NULL AUTO_INCREMENT"; // First field
        foreach($this->cols as $v)
        {
            $el[] = "`" . $v['column_name'] . "` {$v['type']} NULL";
        }
        $el[] = "PRIMARY KEY (`id`)"; //Set primary key

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS `" . $this->tableName . "`;
            CREATE TEMPORARY TABLE `" . $this->tableName . "` (" .
            implode(",",$el) .
            ");";

        $this->db->query($sql);
        $el = array();
        if ($this->colsIndeks)
        {
            $i = 1;
            foreach($this->colsIndeks as $v)
            {
                $el[] = "ADD INDEX `idx" . $i . "`(`" . $v . "`)";
                $i++;
            }

            $sql = "
                ALTER TABLE `{$this->tableName}`
            " . implode(",",$el) . ";";

            $this->db->query($sql);
        }
    }

    private function insertRow()
    {
        $row = array();
        $els = array();

        $this->isArrayMulti();
        if ($this->isArrayMulti)
        {
            $total = count($this->data);
            $i = 1;
            foreach($this->data as $r)
            {
                //Split into chunks...
                $el = array();
                foreach($r as $k => $v)
                {
                    $f = $v;
                    if (!is_numeric($v))
                    {
                        $f = "'" . $v . "'";
                    }
                    $el[] = $f;
                }

                if (count($el) == 0)
                    continue;
                if ($this->add_data)
                {
                    foreach($this->add_data as $k2 => $v2)
                    {
                        $f = "'" . $v2 . "'";
                        $el[] = $f;
                    }
                }
                $els[] = "(" . implode(",",$el) . ")";

                if (($i % $this->limitRow == 0) || $i == $total)
                {
                    $colsSql = $this->getColumnsSQL();
                    $sql = "
                        INSERT INTO `" . $this->tableName . "`
                    (" . $colsSql . ") VALUES " . implode(",",$els) . ";";

                    try {
                        $this->db->query($sql);
                    }
                    catch (Exception $e)
                    {
                        echo $sql . "<br>";
                        echo $e->getMessage();
                        die;
                    }
                    $els = array();
                }

                $i++;
            }
        }
        else
        {
            $colsSql = $this->getColumnsSQL();
            $el = array();
            foreach($this->data as $k => $v)
            {
                $f = $v;
                if (!is_numeric($v))
                {
                    $f = "'" . $v . "'";
                }
                $el[] = $f;
            }

            if (count($el) > 0)
            {

                if ($this->add_data)
                {
                    foreach($this->add_data as $k2 => $v2)
                    {
                        $f = "'" . $v2 . "'";
                        $el[] = $f;
                    }
                }
                $sql = "
                            INSERT INTO `" . $this->tableName . "`
                        (" . $colsSql . ") VALUES (" . implode(",",$el) . ");";

                try {
                    $this->db->query($sql);
                }
                catch (Exception $e)
                {
                    echo $sql . "<br>";
                    echo $e->getMessage();
                    die;
                }
            }
        }
    }

    private function checkTable()
    {
        try{
            $result = $this->db->describeTable($this->tableName); //throws exception
            if (empty($result)) return self::NO_RECORD;
        }catch(Exception $e){
            return self::NO_TABLE;
        }

        return true;
    }

    private function isArrayMulti()
    {
        if (is_array($this->data[0]))
        {
            $tmp = $this->data[0];
            $this->isArrayMulti = true;
        }
    }

    private function addColsAdditional($cols=array())
    {
        if ($this->add_data)
        {
            foreach($this->add_data as $k => $v)
            {
                $cols[] = array(
                    "column_name" => $k,
                    "type" => "VARCHAR(255)"
                );
            }
        }

        return $cols;
    }

    public function insertFromQuery($sqlInsert='')
    {
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS `" . $this->tableName . "`;
            CREATE TEMPORARY TABLE `" . $this->tableName . "` " .
            $sqlInsert;

        $this->db->query($sql);
    }

    public function dumpData($tableName='',$die=true)
    {
//        var_dump($this->db->fetchAll($this->db->select()->from(array($tableName))));
        if ($die)
            die;
    }

    public function fetchAll()
    {
        return $this->db->fetchAll($this->db->select()->from(array($this->tableName)));
    }

    public function dropTable()
    {
        $sql = "DROP TEMPORARY TABLE IF EXISTS `" . $this->tableName . "`;";
        $this->db->query($sql);
    }
}
?>