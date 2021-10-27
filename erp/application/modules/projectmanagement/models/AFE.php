<?php
class ProjectManagement_Models_AFE extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_afed';
    private $db;
    protected $_primary = 'trano';
    protected $const;

    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function __name()
    {
        return $this->_name;
    }
    
    function getCustomerOrder($prjKode, $sitKode) {
        $sql = "SELECT COALESCE(a.total,0) AS total, COALESCE(a.totalusd,0) AS totalusd, SUM(COALESCE(b.totaltambah,0)) AS totaltambah, SUM(COALESCE(b.totaltambahusd,0)) AS totaltambahusd
                    FROM transengineer_boq2h a LEFT JOIN transengineer_kboq2h b
                    ON a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode
                    WHERE
    				a.prj_kode='$prjKode' AND a.sit_kode='$sitKode'";
        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }


}
?>