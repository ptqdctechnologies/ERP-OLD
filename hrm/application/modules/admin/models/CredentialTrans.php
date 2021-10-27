<?php
class Admin_Model_CredentialTrans extends Zend_Db_Table_Abstract
{
    protected $_name = 'credential_transaction';
    private $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getOutstandingDocs($uid = '')
    {
        $sql = "
            SELECT * FROM (
                SELECT b.trano,b.tgl,b.reason,b.data
                FROM
                    workflow_trans a RIGHT JOIN credential_transaction b
                ON
                    a.item_id = b.trano
                WHERE
                    a.item_id IS NULL
                AND
                    b.uid_requestor = '$uid'
                ORDER BY b.tgl DESC
            ) b
            GROUP BY b.trano
        ";
        $fetch = $this->db->query($sql);
        if ($fetch)
            $fetch = $fetch->fetchAll();
        else
            $fetch = array();
        return $fetch;
    }
}
?>