<?php

class Admin_Models_WorkflowItemType extends Zend_Db_Table_Abstract
{
	protected $_name ='workflow_item_type';
	
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
	
	public function createTimesheet($dataArray,$addArray)
    {
    	$rv = array_filter($dataArray,'is_array');
   	 	if(count($rv)>0)
   	 	{
	   	 	foreach($dataArray as $key => $val)
	    	{
	    		$fields = array();
    			$values = array();
	    		if (is_array($val))
	    		{
	    			foreach($val as $key2 => $val2)
	    			{
			    		$fields[] = $key2;
			    		if (!is_numeric($val2))	
			    		{
		    				if ($key2 == 'due_date')
		    				{
		    					$val2 = str_replace('/','-',$val2);
	    						$val2 = date('Y-m-d', strtotime($val2));
    							$dataArray[$key][$key2] = $val2;
		    				}
			    			$values[] = "'" . $val2 . "'";
			    		}
			    		else
			    			$values[] = $val2;
	    			}
	    			
	    			foreach ($addArray as $add => $valAdd)
	    			{
	    				$fields[] = $add;
	    				$values[] = $valAdd;
	    			}
	    			
	    			$fieldsSql = implode(',',$fields);
			    	$valuesSql = implode(',',$values);
			    	
			    	$sql = "INSERT INTO
			    				projectmanagement_timesheet_detail
			    				( $fieldsSql )
			    			VALUES
			    				( $valuesSql );";
			    	$this->db->query($sql);
			    	$lastID = $this->db->lastInsertId();
	    			$dataArray[$key]['id'] = $lastID;
	    		}
	    	}
    	}
    	else
    	{
    		$fields = array();
    		$values = array();
    		foreach($dataArray as $key => $val)
    		{
    			$fields[] = $key;
	    		if (!is_numeric($val))	
	    		{
    				if ($key == 'due_date')
    				{
    					$val = str_replace('/','-',$val);
    					$val = date('Y-m-d', strtotime($val));
    					$dataArray[$key] = $val;
    				}
	    			$values[] = "'" . $val . "'";
	    		}
	    		else
	    			$values[] = $val;
    		}
    		
    		foreach ($addArray as $add => $valAdd)
    		{
    			$fields[] = $add;
    			$values[] = $valAdd;
    		}
	    	
	    	$fieldsSql = implode(',',$fields);
	    	$valuesSql = implode(',',$values);
	    	
	    	$sql = "INSERT INTO
	    				projectmanagement_timesheet_detail
	    				( $fieldsSql )
	    			VALUES
	    				( $valuesSql );";
	    	$this->db->query($sql);
	    	$lastID = $this->db->lastInsertId();
	    	$dataArray['id'] = $lastID;	
    	}
    	
    	return $dataArray;
    	
    }
    
    public function getByID($workflowTypeID='')
    {
    	$sql = "SELECT *
    			FROM
    				workflow_item_type
    			WHERE
    				workflow_item_type_id=$workflowTypeID";
	    $fetch = $this->db->query($sql);
	    $return = $fetch->fetch();
	    return $return;
    }

    public function __name()
    {
        return $this->_name;
    }
}

?>