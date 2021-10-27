<?php

class Default_Models_MasterCustomer extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_customer';

    protected $_primary = 'cus_kode';
    protected $_cus_kode;
    protected $_cus_nama;
	protected $db,$DEFAULT;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');

        $models = array(
            "MasterProject"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);
    }
    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function getCustomerFromProject($prjKode='')
    {
    	$sql = "SELECT
    	 			b.*
    			FROM
    				master_project a
    			LEFT JOIN
    				master_customer b
    			ON
    				a.cus_kode = b.cus_kode
    			WHERE
    				a.prj_kode = '$prjKode'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetch();
    	
    	return $result;
    }

    public function getName($cusKode='')
    {
        $fetch = $this->fetchRow("cus_kode = '$cusKode'");
        if ($fetch)
        {
            return $fetch['cus_nama'];
        }
    }

    public function getListProject($cusKode='')
    {
        $fetch = $this->db->select()
            ->from(array("a" => $this->_name),array(
                "cus_kode",
                "cus_nama"
            ))
            ->joinLeft(array("b" => $this->DEFAULT->MasterProject->__name()),"a.cus_kode = b.cus_kode",array(
                "prj_kode",
                "prj_nama"
            ))
            ->where("a.cus_kode = '$cusKode'");

        $fetch = $this->db->fetchAll($fetch);
        if ($fetch)
        {
            $data = array();
            foreach($fetch as $k => $v)
            {
                $data[] = $v['prj_kode'];
            }

            return $data;
        }
    }
    
    public function getCustomerFromCuskode($cusKode='')
    {
            $sql = "SELECT
                                     *
                            FROM
                                    master_customer
                            WHERE
                                    cus_kode= '$cusKode'";
            $fetch = $this->db->query($sql);
            $result = $fetch->fetch();
            
            return $result;
    }
    
}
?>