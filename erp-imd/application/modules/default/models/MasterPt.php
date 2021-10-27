<?php

class Default_Models_MasterPt extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_pt';

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function __name(){
        return $this->_name;
    }
}

?>