<?php

class Default_Models_MasterSite extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_site';

    protected $_primary = 'sit_kode';
    protected $_sit_kode;
    protected $_sit_nama;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>
