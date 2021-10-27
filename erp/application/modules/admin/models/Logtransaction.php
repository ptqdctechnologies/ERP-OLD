<?php
class Admin_Models_Logtransaction extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_transaction';
    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');

    }

    public function saveLog($trano='',$action='UPDATE',$arrayBefore=array(),$arrayAfter=array())
    {
        $jsonLog = Zend_Json::encode($arrayBefore);
        $jsonLog2 = Zend_Json::encode($arrayAfter);
        $arrayLog = array (
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "action" => $action,
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->insert($arrayLog);
    }

}
?>