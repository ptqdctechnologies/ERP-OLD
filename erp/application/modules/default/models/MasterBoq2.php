<?php

class Default_Models_MasterBoq2 extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_boq2h';

    protected $_primary = 'trano';
    protected $_sit_kode;
    protected $_sit_nama;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }


    public function __name()
    {
        return $this->_name;
    }
}

?>