<?php
//Use Zend_Db_Table Only for SELECT & DELETE only, not recommended for INSERT & UPDATE
class ProjectManagement_Models_TimesheetDetail extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_timesheet_detail';
    protected $_primary = 'id';
	protected $db;

	
	public function getTimesheetByID($idTimesheet='')
	{
		$this->db = Zend_Registry::get('db');
		if (is_array($idTimesheet))
		{
			$idTimesheet = implode(',',$idTimesheet);
			$sql = "SELECT
						*
					FROM
		    				projectmanagement_timesheet_detail
		    			WHERE
		    				( id IN ($idTimesheet) )";
	    	$fetch = $this->db->query($sql);
	    	$result = $fetch->fetchAll();
		}
		else
		{
			$sql = "SELECT
						*
					FROM
		    				projectmanagement_timesheet_detail
		    			WHERE
		    				( id = $idTimesheet )";
	    	$fetch = $this->db->query($sql);
	    	$result = $fetch->fetch();
		}
    	return $result;
	}
	
	public function cekTimesheetByIdUser($idTimesheet='',$userID='')
	{
		$this->db = Zend_Registry::get('db');
		$fetch = $this->getTimesheetByID($idTimesheet);
		$rv = array_filter($fetch,'is_array');
		$isFound = false;
   	 	if(count($rv)>0)
   	 	{
   	 		for($i=0;$i<count($fetch);$i++)
   	 		{
   	 			if($fetch[$i]['user_id'] == $userID)
   	 			{
   	 				$isFound = true;
   	 				break;
   	 			}	
   	 		}
   	 	}
   	 	else
   	 	{
   	 		if($fetch['user_id'] == $userID)
   	 			$isFound = true;
   	 	}
   	 	return $isFound;
	}
	
    public function createTimesheet($dataArray,$addArray)
    {
    	$this->db = Zend_Registry::get('db');
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
    
	public function updateTimesheet($dataArray,$criteriaArray)
    {
    	$this->db = Zend_Registry::get('db');
    	$values = array();
    	$updateArray = $dataArray;
    	
    	$rv = array_filter($dataArray,'is_array');
   	 	if(count($rv)>0)
   	 	{
	    	foreach($updateArray as $key => $val)
	    	{
	    		if (is_array($val))
	    		{
	    			foreach($val as $key2 => $val2)
	    			{
			    		if (!is_numeric($val))	
			    		{
			    			if ($key2 == 'due_date')
		    				{
		    					$val2 = str_replace('/','-',$val2);
	    						$val2 = date('Y-m-d', strtotime($val2));
    							$dataArray[$key][$key2] = $val2;
	    					}
			    			if ($key2 == 'id')
		    				{
		    					$criteriaArray[] = "id=$val";
		    					unset($updateArray[$key]['id']); 
    							continue;
		    				}
			    			$values[] = "$key2 = '" . $val2 . "'";
			    		}
			    		else
			    		{
				    		if ($key2 == 'id')
		    				{
		    					$criteriaArray[] = "id=$val";
		    					unset($updateArray[$key]['id']); 
    							continue;
		    				}
			    			$values[] = "$key2 = $val2";	
			    		}
	    			}
	    			$values[] = 'modified_date=NOW()';
    	
			    	$criteriasSql = implode(' AND ',$criteriaArray);
			    	$valuesSql = implode(',',$values);
			    	
			    	$sql = "UPDATE
			    				projectmanagement_timesheet_detail
			    				SET $valuesSql 
			    			WHERE
			    				( $criteriasSql )";
			    	$this->db->query($sql);
	    		}
	    	}
   	 	}
   	 	else
   	 	{
    		$values = array();
    		foreach($updateArray as $key => $val)
    		{
	    		if (!is_numeric($val))	
	    		{
    				if ($key == 'due_date')
    				{
    					$val = str_replace('/','-',$val);
    					$val = date('Y-m-d', strtotime($val));
    					$dataArray[$key] = $val;
    				}
	    			if ($key == 'id')
    				{
    					$criteriaArray[] = "id=$val";
    					unset($updateArray['id']); 
    					continue;
    				}
	    			$values[] = "$key = '" . $val . "'";
	    		}
	    		else
	    		{
	    			if ($key == 'id')
    				{
    					$criteriaArray[] = "id=$val";
    					unset($updateArray['id']); 
    					continue;
    				}
	    			$values[] = "$key = '" . $val . "'";
	    		}
    		}
	    	
	    	$valuesSql = implode(',',$values);
	    	
	    	$values[] = 'modified_date=NOW()';
	    	$criteriasSql = implode(' AND ',$criteriaArray);
	    	$valuesSql = implode(',',$values);
	    	
	    	$sql = "UPDATE
	    				projectmanagement_timesheet_detail
	    				SET $valuesSql 
	    			WHERE
	    				( $criteriasSql )";
	    	
	    	$this->db->query($sql);
   	 	}
   	 	return $dataArray;
    	
    }
    
    public function deleteTimesheet($idTimesheet='', $userID='')
    {
    	$this->db = Zend_Registry::get('db');
    	if ($this->cekTimesheetByIdUser($idTimesheet,$userID))
    	{
    		if (is_array($idTimesheet))
    		{
    			$idTimesheet = implode(',',$idTimesheet);
    			$sqlCriteria = " id IN ($idTimesheet)";
    		}
    		else
    			$sqlCriteria = " id = $idTimesheet";
	    	$sql = "DELETE FROM
		    				projectmanagement_timesheet_detail
		    			WHERE
		    				( $sqlCriteria AND user_id=$userID )";
	    	$this->db->query($sql);
	    	return true;
    	}
    	return false;
    }
    
	public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
}

?>