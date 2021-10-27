<?php
class ProjectManagement_Models_AFEh extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_afeh';
    private $db;
     protected $_primary = 'trano';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function checkSwitching($trano='')
    {
        $success = false;
        $cekAfe = $this->fetchRow("trano = '$trano'");
        if ($cekAfe)
        {
            if($cekAfe['is_switching'] == 1)
            {
                $success = true;
            }
        }

        return $success;

    }

    public function __name()
    {
        return $this->_name;
    }

}
?>