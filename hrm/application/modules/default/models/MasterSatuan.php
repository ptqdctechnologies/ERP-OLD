 <?php

class Default_Models_MasterSatuan extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_satuan';

    protected $_primary = 'sat_kode';
    protected $_kode_brg;
    protected $_nama_brg;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>
