<?php

class Default_Models_RequestPaymentInvoiceH extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_rpih';

    protected $_primary = 'trano';

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}

?>
