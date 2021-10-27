<?php
class Admin_Model_Workflowstructure extends Zend_Db_Table_Abstract
{
	protected $_name ='workflow_structure';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
	
	public function getTreeRouteGroup($workflowItemId='')
	{

		$sql = "SELECT ws.* FROM workflow_structure ws
				WHERE 
					ws.workflow_item_id = $workflowItemId
				GROUP BY ws.level
				ORDER BY ws.level ASC";
      
    	$return = $this->db->fetchAll($sql);
        return $return;
	}
	
	public function cekInWorkflow($workflowItemId='',$workflow_structure_id,&$hasil='')
	{
		$sql = "SELECT * FROM  workflow w
				WHERE 
					w.workflow_item_id = $workflowItemId
				AND
					w.workflow_structure_id = $workflow_structure_id
				GROUP BY w.workflow_id";
    	$return = $this->db->fetchAll($sql);
    	if ($return && count($return) > 0)
        {
            $hasil = $this->db->fetchRow($sql);
	        return true;
        }
	    else
	    	return false;    
	}
	
	public function getChildInWorkflow($workflowItemId='',$workflow_structure_id)
	{
		$sql = "SELECT w.* FROM  workflow w
                LEFT JOIN workflow_structure ws
                ON w.workflow_structure_id = ws.id AND w.workflow_item_id = ws.workflow_item_id
				WHERE 
					w.workflow_item_id = $workflowItemId
				AND w.next = $workflow_structure_id
				AND ws.id IS NOT NULL
				GROUP BY w.workflow_structure_id";
    	$return = $this->db->fetchAll($sql);
    	
    	return $return;    
	}
	
	public function getTreeRoute($workflowItemId='',$level='')
	{
		$sql = "SELECT ws.*,ml.uid FROM workflow_structure ws
				LEFT JOIN master_role mr
				ON ws.master_role_id = mr.id
				LEFT JOIN master_login ml
				ON mr.id_user = ml.id
				WHERE 
					ws.workflow_item_id = $workflowItemId
					AND
					ws.level = $level
				ORDER BY ws.level ASC";
    	$return = $this->db->fetchAll($sql);
        return $return;
	}
}
?>