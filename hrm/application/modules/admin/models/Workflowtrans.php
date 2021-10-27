<?php
class Admin_Models_Workflowtrans extends Zend_Db_Table_Abstract
{
	protected $_name ='workflow_trans';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}

    public function __name()
    {
        return $this->_name;
    }
	
	public function getDocumentStatus($workflowID='',$itemID='',$uid='')
	{
		$result = $this->fetchRow('workflow_id = ' . $workflowID . ' AND item_id = \'' . $itemID . '\'');
		return $result['approve'];
	}
	
	public function getDocumentType($workflowTransID='')
	{
		$sql = "SELECT wt.approve,wt.comment,wt.item_id,wt.uid,wit.name,wit.workflow_item_type_id FROM
					workflow_trans wt
				LEFT JOIN
					workflow_item wi
				ON wi.workflow_item_id = wt.workflow_item_id
				LEFT JOIN
					workflow_item_type wit
				ON wit.workflow_item_type_id = wi.workflow_item_type_id
				WHERE 
					wt.workflow_trans_id = $workflowTransID" ;
		$fetch = $this->db->query($sql);
		$result = $fetch->fetch();
		$comment = str_replace("\n","",$result['comment']);
        $comment = str_replace("\r","",$comment);
        $comment = str_replace("\"","",$comment);
        $comment = str_replace("'","",$comment);
        $result['comment'] = $comment;
        
		return $result;
	}

    public function getDocumentTypeByTrano($trano='')
	{
		$sql = "SELECT wt.approve,wt.comment,wt.item_id,wt.uid,wit.name,wit.workflow_item_type_id FROM
					workflow_trans wt
				LEFT JOIN
					workflow_item wi
				ON wi.workflow_item_id = wt.workflow_item_id
				LEFT JOIN
					workflow_item_type wit
				ON wit.workflow_item_type_id = wi.workflow_item_type_id
				WHERE
					wt.item_id = '$trano'
                LIMIT 1" ;
        
		$fetch = $this->db->query($sql);
		$result = $fetch->fetch();
		$comment = str_replace("\n","",$result['comment']);
        $comment = str_replace("\r","",$comment);
        $comment = str_replace("\"","",$comment);
        $comment = str_replace("'","",$comment);
        $result['comment'] = $comment;

		return $result;
	}

    public function getDocumentSubmitter($trano)
    {
        $sql = "SELECT * FROM workflow_trans
                WHERE
                    item_id = '$trano'
                AND
                    approve IN (100,150)
                ORDER BY date ASC LIMIT 1";
        $fetch = $this->db->query($sql);
		if ($fetch)
        {
            $result = $fetch->fetch();

            $comment = str_replace("\n","",$result['comment']);
            $comment = str_replace("\r","",$comment);
            $comment = str_replace("\"","",$comment);
            $comment = str_replace("'","",$comment);
            $result['comment'] = $comment;
        }
        return $result;
    }

}