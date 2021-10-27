<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/2/11
 * Time: 1:48 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Default_Models_LogPrint extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_print';

    protected $db;

    public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function insertLog($data=array())
    {
        $data['uid'] = QDC_User_Session::factory()->getCurrentUID();
        $data['date'] = date("Y-m-d H:i:s");
        $data['com_IP'] = $_SERVER["REMOTE_ADDR"];
        $data["com_name"] = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        $this->insert($data);

        return true;
    }

    public function getNumberPrinted($trano='',$type='')
    {
        $select = $this->db->select()
            ->from(array($this->_name),array(
                "print_count" => new Zend_Db_Expr("COUNT(*)")
            ))
            ->where("trano=?",$trano);
        if ($type)
            $select = $select->where("type=?",$type);

        $data = $this->db->fetchOne($select);

        return $data;
    }

}