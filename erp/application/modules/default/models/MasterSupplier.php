<?php

class Application_Model_DbTable_MasterSupplier extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_suplier';

    protected $_primary = 'sup_kode';
    protected $_sup_kode;
    protected $_sup_nama;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>
