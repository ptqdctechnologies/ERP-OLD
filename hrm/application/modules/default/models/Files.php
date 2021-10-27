<?php
class Default_Models_Files extends Zend_Db_Table_Abstract
{
    protected $_name = 'files';
    protected $_primary = 'id';
    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>