 <?php

class Default_Models_MasterValuta extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_valuta';

    protected $_primary = 'val_kode';
    protected $_kode_brg;
    protected $_nama_brg;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>
