 <?php

class Default_Models_UploadFile extends Zend_Db_Table_Abstract
{
    protected $_name = 'upload_file';

    protected $_primary = 'id';

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>
